<?php
/**
 * Bulk upload: drop many images, get one sponsor per image in a chosen category.
 *
 * @package HscSponsoren
 */

declare(strict_types=1);

namespace Hsc\Sponsoren;

use WP_Term;

/**
 * Submenu "Massen-Upload" plus the ajax handler that creates one sponsor per uploaded image.
 */
final class Bulk_Upload {

	public const SLUG   = 'hsc-sponsor-bulk';
	public const ACTION = 'hsc_sponsor_bulk_upload';

	private const CAPABILITY  = 'upload_files';
	private const FIELD_TERM  = 'term_id';
	private const FIELD_FILE  = 'file';
	private const NONCE_FIELD = 'nonce';

	/**
	 * Admin page hook suffix, empty until the menu is added.
	 *
	 * @var string
	 */
	private string $hook_suffix = '';

	/**
	 * Creates the tool.
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
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_ajax_' . self::ACTION, array( $this, 'handle_upload' ) );
	}

	/**
	 * Adds the submenu entry.
	 */
	public function add_menu(): void {
		$hook = add_submenu_page(
			'edit.php?post_type=' . Post_Type::POST_TYPE,
			__( 'Massen-Upload', 'hsc-sponsoren' ),
			__( 'Massen-Upload', 'hsc-sponsoren' ),
			self::CAPABILITY,
			self::SLUG,
			array( $this, 'render' )
		);

		$this->hook_suffix = is_string( $hook ) ? $hook : '';
	}

	/**
	 * Loads script and style on this page only.
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public function enqueue( string $hook ): void {
		if ( '' === $this->hook_suffix || $hook !== $this->hook_suffix ) {
			return;
		}

		wp_enqueue_style( 'hsc-sponsor-bulk', $this->plugin_url . 'assets/bulk.css', array(), $this->version );
		wp_enqueue_script( 'hsc-sponsor-bulk', $this->plugin_url . 'assets/bulk.js', array(), $this->version, true );
		wp_localize_script(
			'hsc-sponsor-bulk',
			'hscSponsorBulk',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'action'  => self::ACTION,
				'nonce'   => wp_create_nonce( self::ACTION ),
				'i18n'    => array(
					'waiting'   => __( 'Wartet …', 'hsc-sponsoren' ),
					'uploading' => __( 'Lädt hoch …', 'hsc-sponsoren' ),
					'done'      => __( 'Angelegt', 'hsc-sponsoren' ),
					'failed'    => __( 'Fehler', 'hsc-sponsoren' ),
					'network'   => __( 'Verbindung fehlgeschlagen.', 'hsc-sponsoren' ),
					'notImage'  => __( 'Keine Bilddatei.', 'hsc-sponsoren' ),
					/* translators: 1: number of created sponsors, 2: number of dropped files */
					'summary'   => __( '%1$d von %2$d Sponsoren angelegt.', 'hsc-sponsoren' ),
				),
			)
		);
	}

	/**
	 * Renders the page.
	 */
	public function render(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => Post_Type::TAXONOMY,
				'hide_empty' => false,
			)
		);
		$terms = is_array( $terms ) ? array_values( array_filter( $terms, static fn( $term ): bool => $term instanceof WP_Term ) ) : array();

		echo '<div class="wrap"><h1>' . esc_html__( 'Sponsoren per Massen-Upload anlegen', 'hsc-sponsoren' ) . '</h1>';

		if ( array() === $terms ) {
			echo '<p>' . esc_html__( 'Es gibt noch keine Kategorien. Lege zuerst unter Sponsoren > Kategorien eine an.', 'hsc-sponsoren' ) . '</p></div>';
			return;
		}
		?>
		<p><?php esc_html_e( 'Wähle eine Kategorie und ziehe die Bilder in das Feld. Pro Bild entsteht ein Sponsor mit dem Dateinamen als Namen. Er landet ganz unten in der Kategorie.', 'hsc-sponsoren' ); ?></p>
		<p>
			<label for="hsc-bulk-term"><strong><?php esc_html_e( 'Kategorie', 'hsc-sponsoren' ); ?></strong></label>
			<select id="hsc-bulk-term">
				<option value=""><?php esc_html_e( '– Kategorie wählen –', 'hsc-sponsoren' ); ?></option>
				<?php foreach ( $terms as $term ) : ?>
					<option value="<?php echo esc_attr( (string) $term->term_id ); ?>"><?php echo esc_html( $term->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<div id="hsc-bulk-drop" class="hsc-bulk-drop is-disabled">
			<p><?php esc_html_e( 'Bilder hierher ziehen', 'hsc-sponsoren' ); ?></p>
			<p>
				<label class="button" for="hsc-bulk-files"><?php esc_html_e( 'oder Dateien auswählen', 'hsc-sponsoren' ); ?></label>
				<input type="file" id="hsc-bulk-files" accept="image/*" multiple hidden>
			</p>
			<p class="description">
				<?php
				/* translators: %s: maximum upload size, e.g. "2 MB" */
				echo esc_html( sprintf( __( 'Maximale Dateigröße: %s', 'hsc-sponsoren' ), size_format( wp_max_upload_size() ) ) );
				?>
			</p>
		</div>
		<p id="hsc-bulk-summary" aria-live="polite"></p>
		<ul id="hsc-bulk-queue" class="hsc-bulk-queue"></ul>
		</div>
		<?php
	}

	/**
	 * Ajax: creates one sponsor from one uploaded image.
	 */
	public function handle_upload(): void {
		check_ajax_referer( self::ACTION, self::NONCE_FIELD );
		if ( ! current_user_can( self::CAPABILITY ) || ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Du darfst das nicht.', 'hsc-sponsoren' ) ), 403 );
		}

		$term_id = isset( $_POST[ self::FIELD_TERM ] ) ? absint( wp_unslash( $_POST[ self::FIELD_TERM ] ) ) : 0;
		$term    = get_term( $term_id, Post_Type::TAXONOMY );
		if ( ! $term instanceof WP_Term ) {
			wp_send_json_error( array( 'message' => __( 'Kategorie nicht gefunden.', 'hsc-sponsoren' ) ), 400 );
		}

		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- the file is validated below and handled by media_handle_upload().
		$file = $_FILES[ self::FIELD_FILE ] ?? null;
		// phpcs:enable
		if ( ! is_array( $file ) || ! isset( $file['tmp_name'], $file['name'], $file['error'] ) || UPLOAD_ERR_OK !== (int) $file['error'] ) {
			wp_send_json_error( array( 'message' => __( 'Upload fehlgeschlagen (Datei zu groß?).', 'hsc-sponsoren' ) ), 400 );
		}

		$name  = sanitize_text_field( wp_unslash( (string) $file['name'] ) );
		$check = wp_check_filetype_and_ext( (string) $file['tmp_name'], $name );
		if ( ! is_string( $check['type'] ) || ! str_starts_with( $check['type'], 'image/' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine erlaubte Bilddatei.', 'hsc-sponsoren' ) ), 415 );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$title = Sponsor_Title::from_filename( $name );
		$id    = wp_insert_post(
			array(
				'post_type'   => Post_Type::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => '' !== $title ? $title : __( 'Sponsor', 'hsc-sponsoren' ),
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			wp_send_json_error( array( 'message' => $id->get_error_message() ), 500 );
		}

		wp_set_object_terms( $id, array( $term->term_id ), Post_Type::TAXONOMY );

		$attachment_id = media_handle_upload( self::FIELD_FILE, $id );
		if ( is_wp_error( $attachment_id ) ) {
			// No half-created sponsor without image.
			wp_delete_post( $id, true );
			wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ), 500 );
		}
		set_post_thumbnail( $id, $attachment_id );

		wp_send_json_success(
			array(
				'id'       => $id,
				'title'    => get_the_title( $id ),
				'editLink' => get_edit_post_link( $id, 'raw' ),
			)
		);
	}
}
