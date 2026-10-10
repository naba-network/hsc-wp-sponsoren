<?php
/**
 * Shortcode that shows the sponsors of one category as a static grid, name below the logo.
 *
 * @package HscSponsoren
 */

declare(strict_types=1);

namespace Hsc\Sponsoren;

use WP_Term;

/**
 * [hsc-sponsoren-grid kategorie="premium" grid-items="4" grid-items-tablet="3" grid-items-mobile="2" hide-name="true"]
 *
 * The sponsor name is real text below the logo (good for SEO and screen readers).
 * With hide-name the name stays in the markup but is only visible to screen readers; sponsors without a logo still show it.
 * Order is the manual order of the category. Sponsors without a logo show the name only.
 * A sponsor with a website links to it exactly as stored, always in a new tab.
 */
final class Grid_Shortcode {

	public const TAG           = 'hsc-sponsoren-grid';
	public const ATTR_CATEGORY = 'kategorie';
	public const ATTR_ITEMS    = 'grid-items';
	public const ATTR_TABLET   = 'grid-items-tablet';
	public const ATTR_MOBILE   = 'grid-items-mobile';
	public const ATTR_HIDE     = 'hide-name';
	public const STYLE_HANDLE  = 'hsc-sponsoren-grid';
	public const SCRIPT_HANDLE = 'hsc-sponsoren-grid';

	/**
	 * Image size used for the logos.
	 */
	private const IMAGE_SIZE = 'medium';

	/**
	 * Creates the shortcode.
	 *
	 * @param string         $plugin_url URL of the plugin folder, with trailing slash.
	 * @param string         $version    Plugin version, used for cache busting.
	 * @param Category_Order $order      Manual order per category.
	 */
	public function __construct(
		private readonly string $plugin_url,
		private readonly string $version,
		private readonly Category_Order $order
	) {}

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_assets' ) );
		add_shortcode( self::TAG, array( $this, 'render' ) );
	}

	/**
	 * Registers stylesheet and script; they are only loaded on pages that use the shortcode.
	 */
	public function register_assets(): void {
		wp_register_script( self::SCRIPT_HANDLE, $this->plugin_url . 'assets/grid.js', array(), $this->version, true );
		wp_register_style( self::STYLE_HANDLE, $this->plugin_url . 'assets/grid.css', array(), $this->version );
	}

	/**
	 * Renders the grid.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes (WordPress passes an empty string when there are none).
	 */
	public function render( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				self::ATTR_CATEGORY => '',
				self::ATTR_ITEMS    => '',
				self::ATTR_TABLET   => '',
				self::ATTR_MOBILE   => '',
				self::ATTR_HIDE     => '',
			),
			is_array( $atts ) ? $atts : array(),
			self::TAG
		);

		$term = $this->find_term( (string) $atts[ self::ATTR_CATEGORY ] );
		if ( ! $term instanceof WP_Term ) {
			return $this->notice( __( 'Sponsoren-Grid: Kategorie nicht gefunden. Prüfe das Attribut "kategorie".', 'hsc-sponsoren' ) );
		}

		$hide_name = Sponsor_Grid::is_enabled( (string) $atts[ self::ATTR_HIDE ] );
		$items     = '';
		foreach ( $this->order->ordered_ids( $term->term_id ) as $id ) {
			$items .= $this->render_item( $id, $hide_name );
		}
		if ( '' === $items ) {
			return '';
		}

		wp_enqueue_style( self::STYLE_HANDLE );
		wp_enqueue_script( self::SCRIPT_HANDLE );

		$cols = Sponsor_Grid::responsive( (string) $atts[ self::ATTR_ITEMS ], (string) $atts[ self::ATTR_TABLET ], (string) $atts[ self::ATTR_MOBILE ] );

		return sprintf(
			'<div class="hsc-grid hsc-grid--reveal" role="list" style="--hsc-cols:%1$d;--hsc-cols-t:%4$d;--hsc-cols-m:%5$d" aria-label="%2$s">%3$s</div><noscript><style>.hsc-grid--reveal .hsc-grid__item{opacity:1;transform:none}</style></noscript>',
			$cols['desktop'],
			esc_attr( sprintf( /* translators: %s: category name */ __( 'Sponsoren: %s', 'hsc-sponsoren' ), $term->name ) ),
			$items,
			$cols['tablet'],
			$cols['mobile']
		);
	}

	/**
	 * One grid cell: logo and name, linked to the website when there is one. Unpublished sponsors give nothing.
	 *
	 * @param int  $id        Sponsor ID.
	 * @param bool $hide_name Show the name to screen readers only (ignored without a logo).
	 */
	private function render_item( int $id, bool $hide_name ): string {
		if ( 'publish' !== get_post_status( $id ) ) {
			return '';
		}

		$name  = get_the_title( $id );
		$image = $this->logo( $id, $name );
		$class = $hide_name && '' !== $image ? 'hsc-grid__name hsc-grid__name--hidden' : 'hsc-grid__name';
		$inner = $image . '<span class="' . $class . '">' . esc_html( $name ) . '</span>';
		$site  = trim( (string) get_post_meta( $id, Post_Type::META_WEBSITE, true ) );

		if ( '' !== $site ) {
			$inner = '<a class="hsc-grid__link" href="' . esc_url( $site ) . '" target="_blank" rel="noopener noreferrer">' . $inner . '</a>';
		}

		return '<div class="hsc-grid__item" role="listitem">' . $inner . '</div>';
	}

	/**
	 * Logo markup, or an empty string when the sponsor has no image. The name below the logo is the visible label,
	 * so the image gets an empty alt (no double reading).
	 *
	 * @param int    $id   Sponsor ID.
	 * @param string $name Sponsor name.
	 */
	private function logo( int $id, string $name ): string {
		$image_id = (int) get_post_thumbnail_id( $id );
		if ( 0 === $image_id ) {
			return '';
		}

		$image = wp_get_attachment_image(
			$image_id,
			self::IMAGE_SIZE,
			false,
			array(
				'class'    => 'hsc-grid__img',
				'alt'      => '',
				'title'    => $name,
				'loading'  => 'lazy',
				'decoding' => 'async',
				'sizes'    => '(max-width: 600px) 45vw, 240px',
			)
		);

		return '' === $image ? '' : '<span class="hsc-grid__logo">' . $image . '</span>';
	}

	/**
	 * Finds the category by slug or by ID.
	 *
	 * @param string $raw Value of the attribute.
	 */
	private function find_term( string $raw ): ?WP_Term {
		$raw = trim( $raw );
		if ( '' === $raw ) {
			return null;
		}

		$term = get_term_by( 'slug', $raw, Post_Type::TAXONOMY );
		if ( ! $term instanceof WP_Term && ctype_digit( $raw ) ) {
			$term = get_term( (int) $raw, Post_Type::TAXONOMY );
		}

		return $term instanceof WP_Term ? $term : null;
	}

	/**
	 * Hint that only editors see; visitors get nothing instead of an error.
	 *
	 * @param string $message Already translated text.
	 */
	private function notice( string $message ): string {
		return current_user_can( 'edit_posts' ) ? '<p class="hsc-grid-notice">' . esc_html( $message ) . '</p>' : '';
	}
}
