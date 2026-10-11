<?php
/**
 * Sponsor list table: columns and category filter. Search by name is core behavior.
 *
 * @package HscSponsoren
 */

declare(strict_types=1);

namespace Hsc\Sponsoren;

/**
 * Customizes the admin list of sponsors.
 */
final class Admin_List {

	private const COLUMN_IMAGE    = 'hsc_image';
	private const COLUMN_CATEGORY = 'hsc_category';
	private const COLUMN_WEBSITE  = 'hsc_website';
	private const COLUMN_SINCE    = 'hsc_since';
	private const COLUMN_ORDER    = 'hsc_order';
	private const ORDERBY_MANUAL  = 'hsc_order';

	/**
	 * Manual order of the filtered category, loaded on first use.
	 *
	 * @var array<int>|null
	 */
	private ?array $filtered_order = null;

	/**
	 * Creates the list customization.
	 *
	 * @param Category_Order $order Manual order per category.
	 */
	public function __construct( private readonly Category_Order $order ) {}

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_filter( 'manage_' . Post_Type::POST_TYPE . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . Post_Type::POST_TYPE . '_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( $this, 'render_category_filter' ), 10, 1 );
		add_filter( 'manage_edit-' . Post_Type::POST_TYPE . '_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( $this, 'apply_manual_order' ) );
		add_action( 'admin_notices', array( $this, 'maybe_notice_category_needed' ) );
	}

	/**
	 * Makes the order column sortable.
	 *
	 * @param array<string, string|array<int, string|bool>> $columns Sortable columns.
	 * @return array<string, string|array<int, string|bool>>
	 */
	public function sortable_columns( array $columns ): array {
		$columns[ self::COLUMN_ORDER ] = array( self::ORDERBY_MANUAL, false );

		return $columns;
	}

	/**
	 * Sorts the sponsor list by the manual order of the selected category.
	 *
	 * The order only exists per category, so without a category the default sorting stays.
	 *
	 * @param \WP_Query $query Current query.
	 */
	public function apply_manual_order( \WP_Query $query ): void {
		if ( ! $query->is_main_query() || ! is_admin() || self::ORDERBY_MANUAL !== $query->get( 'orderby' ) ) {
			return;
		}

		$ids = $this->filtered_order();
		if ( null === $ids ) {
			$query->set( 'orderby', 'title' );
			return;
		}

		if ( 'desc' === strtolower( (string) $query->get( 'order' ) ) ) {
			$ids = array_reverse( $ids );
		}

		$query->set( 'post__in', array() === $ids ? array( 0 ) : $ids );
		$query->set( 'orderby', 'post__in' );
	}

	/**
	 * Tells the user to pick a category when sorting by order without one.
	 */
	public function maybe_notice_category_needed(): void {
		$screen = get_current_screen();
		if ( null === $screen || 'edit-' . Post_Type::POST_TYPE !== $screen->id ) {
			return;
		}
		if ( ! isset( $_GET['orderby'] ) || self::ORDERBY_MANUAL !== $_GET['orderby'] || null !== $this->filtered_order() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return;
		}

		printf(
			'<div class="notice notice-info"><p>%s</p></div>',
			esc_html__( 'Die Reihenfolge gilt pro Kategorie. Wähle oben eine Kategorie, um danach zu sortieren.', 'hsc-sponsoren' )
		);
	}

	/**
	 * Manual order of the category selected in the filter, or null without a (valid) selection.
	 *
	 * @return array<int>|null
	 */
	private function filtered_order(): ?array {
		if ( null !== $this->filtered_order ) {
			return $this->filtered_order;
		}

		$slug = isset( $_GET[ Post_Type::TAXONOMY ] ) ? sanitize_title( wp_unslash( $_GET[ Post_Type::TAXONOMY ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$term = '' === $slug ? false : get_term_by( 'slug', $slug, Post_Type::TAXONOMY );
		if ( ! $term instanceof \WP_Term ) {
			return null;
		}

		$this->filtered_order = $this->order->ordered_ids( $term->term_id );

		return $this->filtered_order;
	}

	/**
	 * Defines the list columns.
	 *
	 * @param array<string, string> $columns Default columns.
	 * @return array<string, string>
	 */
	public function columns( array $columns ): array {
		return array(
			'cb'                  => $columns['cb'] ?? '',
			self::COLUMN_IMAGE    => __( 'Bild', 'hsc-sponsoren' ),
			'title'               => __( 'Name', 'hsc-sponsoren' ),
			self::COLUMN_CATEGORY => __( 'Kategorie', 'hsc-sponsoren' ),
			self::COLUMN_WEBSITE  => __( 'Website', 'hsc-sponsoren' ),
			self::COLUMN_SINCE    => __( 'Partner seit', 'hsc-sponsoren' ),
			self::COLUMN_ORDER    => __( 'Reihenfolge', 'hsc-sponsoren' ),
		);
	}

	/**
	 * Renders one custom column cell.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Sponsor ID.
	 */
	public function render_column( string $column, int $post_id ): void {
		switch ( $column ) {
			case self::COLUMN_IMAGE:
				echo wp_kses_post( get_the_post_thumbnail( $post_id, array( 60, 60 ) ) );
				break;
			case self::COLUMN_CATEGORY:
				$terms = get_the_terms( $post_id, Post_Type::TAXONOMY );
				if ( is_array( $terms ) ) {
					echo esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) );
				}
				break;
			case self::COLUMN_WEBSITE:
				$url = (string) get_post_meta( $post_id, Post_Type::META_WEBSITE, true );
				if ( '' !== $url ) {
					printf( '<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>', esc_url( $url ), esc_html( $url ) );
				}
				break;
			case self::COLUMN_SINCE:
				$since = (string) get_post_meta( $post_id, Post_Type::META_SINCE, true );
				if ( '' !== $since ) {
					echo esc_html( mysql2date( get_option( 'date_format' ), $since ) );
				}
				break;
			case self::COLUMN_ORDER:
				$position = array_search( $post_id, $this->filtered_order() ?? array(), true );
				if ( false !== $position ) {
					echo esc_html( (string) ( $position + 1 ) );
				}
				break;
		}
	}

	/**
	 * Adds the category dropdown above the list.
	 *
	 * @param string $post_type Post type of the current list.
	 */
	public function render_category_filter( string $post_type ): void {
		if ( Post_Type::POST_TYPE !== $post_type ) {
			return;
		}

		wp_dropdown_categories(
			array(
				'show_option_all' => __( 'Alle Kategorien', 'hsc-sponsoren' ),
				'taxonomy'        => Post_Type::TAXONOMY,
				'name'            => Post_Type::TAXONOMY,
				'value_field'     => 'slug',
				'selected'        => isset( $_GET[ Post_Type::TAXONOMY ] ) ? sanitize_title( wp_unslash( $_GET[ Post_Type::TAXONOMY ] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'hide_empty'      => false,
				'hierarchical'    => true,
			)
		);
	}
}
