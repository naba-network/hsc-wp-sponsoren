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

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_filter( 'manage_' . Post_Type::POST_TYPE . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . Post_Type::POST_TYPE . '_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( $this, 'render_category_filter' ), 10, 1 );
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
