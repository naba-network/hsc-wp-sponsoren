<?php
/**
 * Sponsor post type and its category taxonomy.
 *
 * @package HscSponsoren
 */

declare(strict_types=1);

namespace Hsc\Sponsoren;

/**
 * One post = one sponsor: title = name, editor = description, featured image = image.
 * The sponsor type (Hauptsponsor, Premium, Sponsoren, Gönner) is a category.
 */
final class Post_Type {

	public const POST_TYPE = 'hsc_sponsor';
	public const TAXONOMY  = 'hsc_sponsor_category';

	public const META_WEBSITE = '_hsc_sponsor_website';
	public const META_SINCE   = '_hsc_sponsor_since';

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_types' ) );
	}

	/**
	 * Registers the post type and the taxonomy.
	 */
	public function register_types(): void {
		register_taxonomy(
			self::TAXONOMY,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Kategorien', 'hsc-sponsoren' ),
					'singular_name' => __( 'Kategorie', 'hsc-sponsoren' ),
					'add_new_item'  => __( 'Neue Kategorie hinzufügen', 'hsc-sponsoren' ),
					'edit_item'     => __( 'Kategorie bearbeiten', 'hsc-sponsoren' ),
					'all_items'     => __( 'Kategorien', 'hsc-sponsoren' ),
					'search_items'  => __( 'Kategorien durchsuchen', 'hsc-sponsoren' ),
				),
				'hierarchical'      => true,
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => false,
				'show_in_rest'      => true,
				'rewrite'           => false,
			)
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'       => array(
					'name'                  => __( 'Sponsoren', 'hsc-sponsoren' ),
					'singular_name'         => __( 'Sponsor', 'hsc-sponsoren' ),
					'add_new'               => __( 'Neuer Sponsor', 'hsc-sponsoren' ),
					'add_new_item'          => __( 'Neuen Sponsor hinzufügen', 'hsc-sponsoren' ),
					'edit_item'             => __( 'Sponsor bearbeiten', 'hsc-sponsoren' ),
					'new_item'              => __( 'Neuer Sponsor', 'hsc-sponsoren' ),
					'view_item'             => __( 'Sponsor ansehen', 'hsc-sponsoren' ),
					'search_items'          => __( 'Sponsoren durchsuchen', 'hsc-sponsoren' ),
					'not_found'             => __( 'Keine Sponsoren gefunden.', 'hsc-sponsoren' ),
					'not_found_in_trash'    => __( 'Keine Sponsoren im Papierkorb.', 'hsc-sponsoren' ),
					'all_items'             => __( 'Alle Sponsoren', 'hsc-sponsoren' ),
					'featured_image'        => __( 'Bild', 'hsc-sponsoren' ),
					'set_featured_image'    => __( 'Bild festlegen', 'hsc-sponsoren' ),
					'remove_featured_image' => __( 'Bild entfernen', 'hsc-sponsoren' ),
					'use_featured_image'    => __( 'Als Bild verwenden', 'hsc-sponsoren' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-heart',
				'supports'     => array( 'title', 'editor', 'thumbnail' ),
				'taxonomies'   => array( self::TAXONOMY ),
				'rewrite'      => false,
			)
		);

		register_post_meta(
			self::POST_TYPE,
			self::META_WEBSITE,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array( Sponsor_Fields::class, 'website' ),
				'auth_callback'     => static fn(): bool => current_user_can( 'edit_posts' ),
			)
		);
		register_post_meta(
			self::POST_TYPE,
			self::META_SINCE,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array( Sponsor_Fields::class, 'since' ),
				'auth_callback'     => static fn(): bool => current_user_can( 'edit_posts' ),
			)
		);
	}
}
