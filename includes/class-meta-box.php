<?php
/**
 * Meta box with the website and "member since" fields.
 *
 * @package HscSponsoren
 */

declare(strict_types=1);

namespace Hsc\Sponsoren;

use WP_Post;

/**
 * Edit form for the custom sponsor fields.
 */
final class Meta_Box {

	private const NONCE_ACTION = 'hsc_sponsor_save_fields';
	private const NONCE_FIELD  = 'hsc_sponsor_nonce';
	private const FIELD_SITE   = 'hsc_sponsor_website';
	private const FIELD_SINCE  = 'hsc_sponsor_since';

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'add_meta_boxes_' . Post_Type::POST_TYPE, array( $this, 'add' ) );
		add_action( 'save_post_' . Post_Type::POST_TYPE, array( $this, 'save' ) );
	}

	/**
	 * Adds the meta box to the sponsor edit screen.
	 */
	public function add(): void {
		add_meta_box(
			'hsc-sponsor-fields',
			__( 'Sponsor-Details', 'hsc-sponsoren' ),
			array( $this, 'render' ),
			Post_Type::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Renders the fields.
	 *
	 * @param WP_Post $post Sponsor being edited.
	 */
	public function render( WP_Post $post ): void {
		$website = (string) get_post_meta( $post->ID, Post_Type::META_WEBSITE, true );
		$since   = (string) get_post_meta( $post->ID, Post_Type::META_SINCE, true );

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );
		?>
		<p>
			<label for="<?php echo esc_attr( self::FIELD_SITE ); ?>"><strong><?php esc_html_e( 'Website', 'hsc-sponsoren' ); ?></strong></label><br>
			<input type="url" class="widefat" id="<?php echo esc_attr( self::FIELD_SITE ); ?>" name="<?php echo esc_attr( self::FIELD_SITE ); ?>" value="<?php echo esc_attr( $website ); ?>" placeholder="https://example.com">
		</p>
		<p>
			<label for="<?php echo esc_attr( self::FIELD_SINCE ); ?>"><strong><?php esc_html_e( 'Partner seit', 'hsc-sponsoren' ); ?></strong></label><br>
			<input type="date" id="<?php echo esc_attr( self::FIELD_SINCE ); ?>" name="<?php echo esc_attr( self::FIELD_SINCE ); ?>" value="<?php echo esc_attr( $since ); ?>">
		</p>
		<?php
	}

	/**
	 * Saves the fields.
	 *
	 * @param int $post_id Sponsor ID.
	 */
	public function save( int $post_id ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		$nonce = isset( $_POST[ self::NONCE_FIELD ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$website = isset( $_POST[ self::FIELD_SITE ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::FIELD_SITE ] ) ) : '';
		$since   = isset( $_POST[ self::FIELD_SINCE ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::FIELD_SINCE ] ) ) : '';

		update_post_meta( $post_id, Post_Type::META_WEBSITE, Sponsor_Fields::website( $website ) );
		update_post_meta( $post_id, Post_Type::META_SINCE, Sponsor_Fields::since( $since ) );
	}
}
