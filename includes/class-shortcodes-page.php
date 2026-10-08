<?php
/**
 * Admin page that documents the shortcodes of the plugin.
 *
 * @package HscSponsoren
 */

declare(strict_types=1);

namespace Hsc\Sponsoren;

use WP_Term;

/**
 * Submenu "Shortcodes" below "Sponsoren": what each shortcode does, its attributes and copyable examples.
 */
final class Shortcodes_Page {

	public const SLUG = 'hsc-sponsor-shortcodes';

	private const CAPABILITY = 'edit_posts';

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
	}

	/**
	 * Adds the submenu entry.
	 */
	public function add_menu(): void {
		add_submenu_page(
			'edit.php?post_type=' . Post_Type::POST_TYPE,
			__( 'Shortcodes', 'hsc-sponsoren' ),
			__( 'Shortcodes', 'hsc-sponsoren' ),
			self::CAPABILITY,
			self::SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Renders the page.
	 */
	public function render(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		echo '<div class="wrap"><h1>' . esc_html__( 'Shortcodes', 'hsc-sponsoren' ) . '</h1>';
		echo '<p>' . esc_html__( 'Füge einen Shortcode in einen Textblock oder ein Shortcode-Modul der Seite ein. Du kannst ihn beliebig oft pro Seite verwenden.', 'hsc-sponsoren' ) . '</p>';

		$this->render_slider();
		$this->render_grid();

		echo '</div>';
	}

	/**
	 * Documentation of the slider shortcode.
	 */
	private function render_slider(): void {
		$tag       = Slider_Shortcode::TAG;
		$attribute = Slider_Shortcode::ATTR_CATEGORY;
		$variant   = Slider_Shortcode::ATTR_VARIANT;
		$terms     = $this->terms();
		$example   = array() === $terms ? 'premium' : $terms[0]->slug;
		?>
		<div class="card" style="max-width: 800px;">
			<h2 style="margin-top: 0;"><code>[<?php echo esc_html( $tag ); ?>]</code></h2>
			<p><?php esc_html_e( 'Zeigt die Logos aller Sponsoren einer Kategorie als endloses, automatisch laufendes Band. Die Reihenfolge ist bei jedem Seitenaufruf zufällig. Sponsoren ohne Bild werden übersprungen.', 'hsc-sponsoren' ); ?></p>

			<h3><?php esc_html_e( 'Attribute', 'hsc-sponsoren' ); ?></h3>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Attribut', 'hsc-sponsoren' ); ?></th>
						<th><?php esc_html_e( 'Pflicht', 'hsc-sponsoren' ); ?></th>
						<th><?php esc_html_e( 'Beschreibung', 'hsc-sponsoren' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><code><?php echo esc_html( $attribute ); ?></code></td>
						<td><?php esc_html_e( 'Ja', 'hsc-sponsoren' ); ?></td>
						<td><?php esc_html_e( 'Kürzel (Slug) oder ID der Kategorie.', 'hsc-sponsoren' ); ?></td>
					</tr>
					<tr>
						<td><code><?php echo esc_html( $variant ); ?></code></td>
						<td><?php esc_html_e( 'Nein', 'hsc-sponsoren' ); ?></td>
						<td>
							<?php
							printf(
								/* translators: 1: premium, 2: standard */
								esc_html__( '%1$s (Standard): eine Reihe großer Karten mit farbigen Logos. %2$s: drei gegenläufige Reihen kleiner Karten mit grauen Logos (Farbe bei Hover), auf dem Handy vier Logos pro Reihe.', 'hsc-sponsoren' ),
								'<code>' . esc_html( Slider_Variant::PREMIUM ) . '</code>',
								'<code>' . esc_html( Slider_Variant::STANDARD ) . '</code>'
							);
							?>
						</td>
					</tr>
				</tbody>
			</table>

			<h3><?php esc_html_e( 'Beispiel', 'hsc-sponsoren' ); ?></h3>
			<p><code style="user-select: all;"><?php echo esc_html( sprintf( '[%s %s="%s"]', $tag, $attribute, $example ) ); ?></code></p>
			<p><code style="user-select: all;"><?php echo esc_html( sprintf( '[%s %s="%s" %s="%s"]', $tag, $attribute, $example, $variant, Slider_Variant::STANDARD ) ); ?></code></p>

			<h3><?php esc_html_e( 'Deine Kategorien', 'hsc-sponsoren' ); ?></h3>
			<?php if ( array() === $terms ) : ?>
				<p><?php esc_html_e( 'Es gibt noch keine Kategorien. Lege zuerst unter Sponsoren > Kategorien eine an.', 'hsc-sponsoren' ); ?></p>
			<?php else : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Kategorie', 'hsc-sponsoren' ); ?></th>
							<th><?php esc_html_e( 'Premium', 'hsc-sponsoren' ); ?></th>
							<th><?php esc_html_e( 'Standard', 'hsc-sponsoren' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $terms as $term ) : ?>
							<tr>
								<td><?php echo esc_html( $term->name ); ?></td>
								<td><code style="user-select: all;"><?php echo esc_html( sprintf( '[%s %s="%s"]', $tag, $attribute, $term->slug ) ); ?></code></td>
								<td><code style="user-select: all;"><?php echo esc_html( sprintf( '[%s %s="%s" %s="%s"]', $tag, $attribute, $term->slug, $variant, Slider_Variant::STANDARD ) ); ?></code></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<h3><?php esc_html_e( 'Gut zu wissen', 'hsc-sponsoren' ); ?></h3>
			<ul style="list-style: disc; padding-left: 1.5em;">
				<li><?php esc_html_e( 'Der Slider hat keinen eigenen Hintergrund. Er nimmt den Hintergrund des Bereichs, in dem er steht.', 'hsc-sponsoren' ); ?></li>
				<li><?php esc_html_e( 'Die Geschwindigkeit bleibt gleich, egal wie viele Sponsoren in der Kategorie sind. Bei wenigen Sponsoren wiederholt sich die Liste.', 'hsc-sponsoren' ); ?></li>
				<li><?php esc_html_e( 'Auf dem Handy sind bei Premium drei Logos gleichzeitig sichtbar.', 'hsc-sponsoren' ); ?></li>
				<li><?php esc_html_e( 'Bilder laden erst, wenn sie in die Nähe des sichtbaren Bereichs kommen.', 'hsc-sponsoren' ); ?></li>
				<li><?php esc_html_e( 'Nutzt die Website einen Seiten-Cache, bleibt die zufällige Reihenfolge bis zum nächsten Leeren des Caches gleich.', 'hsc-sponsoren' ); ?></li>
			</ul>
		</div>
		<?php
	}

	/**
	 * Documentation of the grid shortcode.
	 */
	private function render_grid(): void {
		$tag       = Grid_Shortcode::TAG;
		$attribute = Grid_Shortcode::ATTR_CATEGORY;
		$items     = Grid_Shortcode::ATTR_ITEMS;
		$tablet    = Grid_Shortcode::ATTR_TABLET;
		$mobile    = Grid_Shortcode::ATTR_MOBILE;
		$terms     = $this->terms();
		$example   = array() === $terms ? 'premium' : $terms[0]->slug;
		?>
		<div class="card" style="max-width: 800px;">
			<h2 style="margin-top: 0;"><code>[<?php echo esc_html( $tag ); ?>]</code></h2>
			<p><?php esc_html_e( 'Zeigt die Sponsoren einer Kategorie als Raster. Unter jedem Logo steht der Name als Text (gut für Suchmaschinen). Die Reihenfolge ist die, die du unter Sponsoren > Reihenfolge festlegst.', 'hsc-sponsoren' ); ?></p>

			<h3><?php esc_html_e( 'Attribute', 'hsc-sponsoren' ); ?></h3>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Attribut', 'hsc-sponsoren' ); ?></th>
						<th><?php esc_html_e( 'Pflicht', 'hsc-sponsoren' ); ?></th>
						<th><?php esc_html_e( 'Beschreibung', 'hsc-sponsoren' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><code><?php echo esc_html( $attribute ); ?></code></td>
						<td><?php esc_html_e( 'Ja', 'hsc-sponsoren' ); ?></td>
						<td><?php esc_html_e( 'Kürzel (Slug) oder ID der Kategorie.', 'hsc-sponsoren' ); ?></td>
					</tr>
					<tr>
						<td><code><?php echo esc_html( $items ); ?></code></td>
						<td><?php esc_html_e( 'Nein', 'hsc-sponsoren' ); ?></td>
						<td>
							<?php
							printf(
								/* translators: 1: default, 2: minimum, 3: maximum */
								esc_html__( 'Anzahl Spalten am Desktop (ab 1025px; Standard %1$d, erlaubt %2$d bis %3$d). Mehr Spalten = kleinere Logos.', 'hsc-sponsoren' ),
								(int) Sponsor_Grid::DEFAULT_COLUMNS,
								(int) Sponsor_Grid::MIN_COLUMNS,
								(int) Sponsor_Grid::MAX_COLUMNS
							);
							?>
						</td>
					</tr>
					<tr>
						<td><code><?php echo esc_html( $tablet ); ?></code></td>
						<td><?php esc_html_e( 'Nein', 'hsc-sponsoren' ); ?></td>
						<td><?php esc_html_e( 'Spalten auf dem Tablet (601 bis 1024px). Ohne Angabe: wie Desktop, höchstens 3.', 'hsc-sponsoren' ); ?></td>
					</tr>
					<tr>
						<td><code><?php echo esc_html( $mobile ); ?></code></td>
						<td><?php esc_html_e( 'Nein', 'hsc-sponsoren' ); ?></td>
						<td><?php esc_html_e( 'Spalten auf dem Handy (bis 600px). Ohne Angabe: wie Desktop, höchstens 2.', 'hsc-sponsoren' ); ?></td>
					</tr>
				</tbody>
			</table>

			<h3><?php esc_html_e( 'Beispiel', 'hsc-sponsoren' ); ?></h3>
			<p><code style="user-select: all;"><?php echo esc_html( sprintf( '[%s %s="%s" %s="6" %s="4" %s="2"]', $tag, $attribute, $example, $items, $tablet, $mobile ) ); ?></code></p>

			<h3><?php esc_html_e( 'Gut zu wissen', 'hsc-sponsoren' ); ?></h3>
			<ul style="list-style: disc; padding-left: 1.5em;">
				<li><?php esc_html_e( 'Hauptsponsor: grid-items="1" oder "2" für große Logos, Gönner: 6 bis 8 für kleine.', 'hsc-sponsoren' ); ?></li>
				<li><?php esc_html_e( 'Sponsoren ohne Bild erscheinen nur mit Namen. Mit Website ist die Zelle ein Link auf die gespeicherte Adresse, immer in einem neuen Tab.', 'hsc-sponsoren' ); ?></li>
				<li><?php esc_html_e( 'Logos sind grau und werden bei Hover farbig. Das Raster hat keinen eigenen Hintergrund.', 'hsc-sponsoren' ); ?></li>
			</ul>
		</div>
		<?php
	}

	/**
	 * All categories.
	 *
	 * @return array<WP_Term>
	 */
	private function terms(): array {
		$terms = get_terms(
			array(
				'taxonomy'   => Post_Type::TAXONOMY,
				'hide_empty' => false,
			)
		);

		return is_array( $terms ) ? array_values( array_filter( $terms, static fn( $term ): bool => $term instanceof WP_Term ) ) : array();
	}
}
