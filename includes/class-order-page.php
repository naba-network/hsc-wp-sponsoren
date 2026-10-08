<?php
/**
 * Admin page to sort the sponsors of one category.
 *
 * @package HscSponsoren
 */

declare(strict_types=1);

namespace Hsc\Sponsoren;

use WP_Term;

/**
 * Submenu "Reihenfolge" below "Sponsoren": pick a category, drag the sponsors into order, save.
 */
final class Order_Page {

	public const SLUG   = 'hsc-sponsor-order';
	public const ACTION = 'hsc_sponsor_save_order';

	private const CAPABILITY  = 'edit_others_posts';
	private const FIELD_TERM  = 'term_id';
	private const FIELD_ORDER = 'hsc_order';
	private const NONCE_FIELD = 'hsc_order_nonce';
	private const QUERY_SAVED = 'hsc_saved';

	/**
	 * Admin page hook suffix, empty until the menu is added.
	 *
	 * @var string
	 */
	private string $hook_suffix = '';

	/**
	 * Creates the page.
	 *
	 * @param Category_Order $order      Order storage.
	 * @param string         $plugin_url URL of the plugin folder, with trailing slash.
	 * @param string         $version    Plugin version, used for cache busting.
	 */
	public function __construct(
		private readonly Category_Order $order,
		private readonly string $plugin_url,
		private readonly string $version
	) {}

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle_save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Adds the submenu entry.
	 */
	public function add_menu(): void {
		$hook = add_submenu_page(
			'edit.php?post_type=' . Post_Type::POST_TYPE,
			__( 'Reihenfolge', 'hsc-sponsoren' ),
			__( 'Reihenfolge', 'hsc-sponsoren' ),
			self::CAPABILITY,
			self::SLUG,
			array( $this, 'render' )
		);

		$this->hook_suffix = is_string( $hook ) ? $hook : '';
	}

	/**
	 * Loads sortable, script and style on this page only.
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public function enqueue( string $hook ): void {
		if ( '' === $this->hook_suffix || $hook !== $this->hook_suffix ) {
			return;
		}

		wp_enqueue_style( 'hsc-sponsor-order', $this->plugin_url . 'assets/order.css', array(), $this->version );
		wp_enqueue_script( 'hsc-sponsor-order', $this->plugin_url . 'assets/order.js', array( 'jquery', 'jquery-ui-sortable' ), $this->version, true );
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

		echo '<div class="wrap"><h1>' . esc_html__( 'Reihenfolge der Sponsoren', 'hsc-sponsoren' ) . '</h1>';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display state.
		if ( isset( $_GET[ self::QUERY_SAVED ] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Reihenfolge gespeichert.', 'hsc-sponsoren' ) . '</p></div>';
		}

		if ( array() === $terms ) {
			echo '<p>' . esc_html__( 'Es gibt noch keine Kategorien. Lege zuerst unter Sponsoren > Kategorien eine an.', 'hsc-sponsoren' ) . '</p></div>';
			return;
		}

		$selected = $this->selected_term( $terms );
		$this->render_picker( $terms, $selected );
		$this->render_list( $selected );

		echo '</div>';
	}

	/**
	 * Saves the submitted order.
	 */
	public function handle_save(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Du darfst das nicht.', 'hsc-sponsoren' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::ACTION, self::NONCE_FIELD );

		$term_id = isset( $_POST[ self::FIELD_TERM ] ) ? absint( wp_unslash( $_POST[ self::FIELD_TERM ] ) ) : 0;
		$term    = get_term( $term_id, Post_Type::TAXONOMY );
		if ( ! $term instanceof WP_Term ) {
			wp_die( esc_html__( 'Kategorie nicht gefunden.', 'hsc-sponsoren' ), '', array( 'response' => 404 ) );
		}

		// Every value is cast to a positive integer by Sponsor_Order::normalize().
		$submitted = isset( $_POST[ self::FIELD_ORDER ] ) ? Sponsor_Order::normalize( wp_unslash( $_POST[ self::FIELD_ORDER ] ) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$this->order->save( $term->term_id, $submitted );

		wp_safe_redirect(
			add_query_arg(
				array(
					'post_type'       => Post_Type::POST_TYPE,
					'page'            => self::SLUG,
					self::FIELD_TERM  => $term->term_id,
					self::QUERY_SAVED => 1,
				),
				admin_url( 'edit.php' )
			)
		);
		exit;
	}

	/**
	 * Category chosen in the picker, the first one by default.
	 *
	 * @param array<WP_Term> $terms All categories, not empty.
	 */
	private function selected_term( array $terms ): WP_Term {
		$wanted = isset( $_GET[ self::FIELD_TERM ] ) ? absint( wp_unslash( $_GET[ self::FIELD_TERM ] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		foreach ( $terms as $term ) {
			if ( $term->term_id === $wanted ) {
				return $term;
			}
		}

		return $terms[0];
	}

	/**
	 * Category dropdown (GET form).
	 *
	 * @param array<WP_Term> $terms    All categories.
	 * @param WP_Term        $selected Current category.
	 */
	private function render_picker( array $terms, WP_Term $selected ): void {
		?>
		<form method="get" class="hsc-order-picker">
			<input type="hidden" name="post_type" value="<?php echo esc_attr( Post_Type::POST_TYPE ); ?>">
			<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>">
			<label for="hsc-order-term"><?php esc_html_e( 'Kategorie', 'hsc-sponsoren' ); ?></label>
			<select id="hsc-order-term" name="<?php echo esc_attr( self::FIELD_TERM ); ?>">
				<?php foreach ( $terms as $term ) : ?>
					<option value="<?php echo esc_attr( (string) $term->term_id ); ?>" <?php selected( $term->term_id, $selected->term_id ); ?>><?php echo esc_html( $term->name ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="submit" class="button"><?php esc_html_e( 'Anzeigen', 'hsc-sponsoren' ); ?></button>
		</form>
		<?php
	}

	/**
	 * Sortable list of the sponsors of the category plus the save form.
	 *
	 * @param WP_Term $term Current category.
	 */
	private function render_list( WP_Term $term ): void {
		$ids = $this->order->ordered_ids( $term->term_id );

		if ( array() === $ids ) {
			echo '<p>' . esc_html__( 'In dieser Kategorie gibt es noch keine Sponsoren.', 'hsc-sponsoren' ) . '</p>';
			return;
		}
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
			<input type="hidden" name="<?php echo esc_attr( self::FIELD_TERM ); ?>" value="<?php echo esc_attr( (string) $term->term_id ); ?>">
			<?php wp_nonce_field( self::ACTION, self::NONCE_FIELD ); ?>
			<p class="description"><?php esc_html_e( 'Ziehe die Einträge in die gewünschte Reihenfolge oder nutze die Pfeile. Neue Sponsoren landen automatisch ganz unten.', 'hsc-sponsoren' ); ?></p>
			<ol class="hsc-order-list" id="hsc-order-list">
				<?php foreach ( $ids as $id ) : ?>
					<li class="hsc-order-item">
						<input type="hidden" name="<?php echo esc_attr( self::FIELD_ORDER ); ?>[]" value="<?php echo esc_attr( (string) $id ); ?>">
						<span class="hsc-order-handle dashicons dashicons-menu" aria-hidden="true"></span>
						<span class="hsc-order-thumb"><?php echo wp_kses_post( get_the_post_thumbnail( $id, array( 40, 40 ) ) ); ?></span>
						<span class="hsc-order-title"><?php echo esc_html( get_the_title( $id ) ); ?></span>
						<button type="button" class="button hsc-order-up" aria-label="<?php esc_attr_e( 'Nach oben', 'hsc-sponsoren' ); ?>">&uarr;</button>
						<button type="button" class="button hsc-order-down" aria-label="<?php esc_attr_e( 'Nach unten', 'hsc-sponsoren' ); ?>">&darr;</button>
					</li>
				<?php endforeach; ?>
			</ol>
			<?php submit_button( __( 'Reihenfolge speichern', 'hsc-sponsoren' ) ); ?>
		</form>
		<?php
	}
}
