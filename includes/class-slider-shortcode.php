<?php
/**
 * Shortcode that shows the sponsors of one category as an endless logo slider.
 *
 * @package HscSponsoren
 */

declare(strict_types=1);

namespace Hsc\Sponsoren;

use WP_Term;

/**
 * [hsc-sponsoren-slider kategorie="premium"]
 *
 * Pure CSS: the band is rendered twice and the CSS moves it by exactly one copy, so the loop is seamless.
 * The run time is "number of cards x seconds per card", so the speed does not depend on the number of sponsors.
 * Several sliders on one page do not interfere: each one carries its own card count and animates on its own.
 */
final class Slider_Shortcode {

	public const TAG           = 'hsc-sponsoren-slider';
	public const ATTR_CATEGORY = 'kategorie';
	public const ATTR_VARIANT  = 'variante';
	public const STYLE_HANDLE  = 'hsc-sponsoren-slider';

	/**
	 * Image size used for the logos (the card shows them at most ~160px wide).
	 */
	private const IMAGE_SIZE = 'medium';

	/**
	 * Creates the shortcode.
	 *
	 * @param string $plugin_url URL of the plugin folder, with trailing slash.
	 * @param string $version    Plugin version, used for cache busting.
	 */
	public function __construct(
		private readonly string $plugin_url,
		private readonly string $version
	) {}

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_assets' ) );
		add_shortcode( self::TAG, array( $this, 'render' ) );
	}

	/**
	 * Registers the stylesheet; it is only loaded on pages that use the shortcode.
	 */
	public function register_assets(): void {
		wp_register_style( self::STYLE_HANDLE, $this->plugin_url . 'assets/slider.css', array(), $this->version );
	}

	/**
	 * Renders the slider.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes (WordPress passes an empty string when there are none).
	 */
	public function render( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				self::ATTR_CATEGORY => '',
				self::ATTR_VARIANT  => Slider_Variant::PREMIUM,
			),
			is_array( $atts ) ? $atts : array(),
			self::TAG
		);

		$variant  = Slider_Variant::normalize( (string) $atts[ self::ATTR_VARIANT ] );
		$settings = Slider_Variant::settings( (string) $atts[ self::ATTR_VARIANT ] );
		if ( null === $variant || null === $settings ) {
			return $this->notice( __( 'Sponsoren-Slider: Variante unbekannt. Erlaubt sind: ', 'hsc-sponsoren' ) . implode( ', ', Slider_Variant::names() ) );
		}

		$term = $this->find_term( (string) $atts[ self::ATTR_CATEGORY ] );
		if ( ! $term instanceof WP_Term ) {
			return $this->notice( __( 'Sponsoren-Slider: Kategorie nicht gefunden. Prüfe das Attribut "kategorie".', 'hsc-sponsoren' ) );
		}

		$cards = $this->cards( $term );
		if ( array() === $cards ) {
			return '';
		}

		wp_enqueue_style( self::STYLE_HANDLE );

		// Every row gets its own random order; odd rows run the other way.
		$rows = '';
		for ( $row = 0; $row < $settings['rows']; $row++ ) {
			$rows .= $this->render_row( $cards, $settings['min_cards'], $variant, 1 === $row % 2 );
		}

		return sprintf(
			'<div class="hsc-slider-group" role="region" aria-label="%1$s">%2$s</div>',
			esc_attr( sprintf( /* translators: %s: category name */ __( 'Sponsoren: %s', 'hsc-sponsoren' ), $term->name ) ),
			$rows
		);
	}

	/**
	 * One animated row. The band is rendered twice; the CSS moves it by exactly one copy for a seamless loop.
	 *
	 * @param array<int, string> $cards     Logo markup.
	 * @param int                $min_cards Minimum number of cards per copy.
	 * @param string             $variant   Variant name.
	 * @param bool               $reverse   Whether the row runs to the right.
	 */
	private function render_row( array $cards, int $min_cards, string $variant, bool $reverse ): string {
		// Random order on every page build; a page cache keeps the order until it is cleared.
		shuffle( $cards );
		$cards = Slider_Items::repeat_to_fill( $cards, $min_cards );

		$band = '';
		foreach ( $cards as $card ) {
			$band .= '<div class="hsc-slider__card" role="listitem">' . $card . '</div>';
		}

		$track = 'hsc-slider__track' . ( $reverse ? ' hsc-slider__track--reverse' : '' );

		// The second copy is only there for the loop, so screen readers skip it.
		return sprintf(
			'<div class="hsc-slider hsc-slider--%1$s" style="--hsc-n:%2$d"><div class="%3$s" role="list">%4$s</div><div class="%3$s" aria-hidden="true">%4$s</div></div>',
			esc_attr( $variant ),
			count( $cards ),
			esc_attr( $track ),
			$band
		);
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
	 * Logo markup of all published sponsors of the category that have an image.
	 *
	 * @param WP_Term $term Category.
	 * @return array<int, string>
	 */
	private function cards( WP_Term $term ): array {
		$ids = get_posts(
			array(
				'post_type'      => Post_Type::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => Post_Type::TAXONOMY,
						'field'    => 'term_id',
						'terms'    => array( $term->term_id ),
					),
				),
			)
		);

		$cards = array();
		foreach ( $ids as $id ) {
			$image_id = (int) get_post_thumbnail_id( $id );
			if ( 0 === $image_id ) {
				continue;
			}
			$image = wp_get_attachment_image(
				$image_id,
				self::IMAGE_SIZE,
				false,
				array(
					'class'     => 'hsc-slider__img',
					'alt'       => get_the_title( $id ),
					'loading'   => 'lazy',
					'decoding'  => 'async',
					'draggable' => 'false',
					'sizes'     => '320px',
				)
			);
			if ( '' !== $image ) {
				$cards[] = $image;
			}
		}

		return $cards;
	}

	/**
	 * Hint that only editors see; visitors get nothing instead of an error.
	 *
	 * @param string $message Already translated text.
	 */
	private function notice( string $message ): string {
		return current_user_can( 'edit_posts' ) ? '<p class="hsc-slider-notice">' . esc_html( $message ) . '</p>' : '';
	}
}
