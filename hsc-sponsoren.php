<?php
/**
 * Plugin Name:       HSC Sponsoren
 * Description:       Manages the sponsors and partners of the club as a single source of truth.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            HSC Hohenems
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       hsc-sponsoren
 *
 * @package HscSponsoren
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HSC_SPONS_VERSION', '0.1.0' );
define( 'HSC_SPONS_FILE', __FILE__ );
define( 'HSC_SPONS_GITHUB_REPO', 'naba-network/hsc-wp-sponsoren' );

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}
require_once __DIR__ . '/includes/class-updater.php';
require_once __DIR__ . '/includes/class-sponsor-fields.php';
require_once __DIR__ . '/includes/class-post-type.php';
require_once __DIR__ . '/includes/class-meta-box.php';
require_once __DIR__ . '/includes/class-admin-list.php';

$hsc_spons_updater = new \Hsc\Sponsoren\Updater(
	HSC_SPONS_FILE,
	HSC_SPONS_GITHUB_REPO,
	'hsc-sponsoren'
);
$hsc_spons_updater->register();

( new \Hsc\Sponsoren\Post_Type() )->register();
( new \Hsc\Sponsoren\Meta_Box() )->register();
( new \Hsc\Sponsoren\Admin_List() )->register();

// Rewrite rules are not needed (no public URLs), but keep the plugin tidy on activation.
register_activation_hook(
	HSC_SPONS_FILE,
	static function (): void {
		( new \Hsc\Sponsoren\Post_Type() )->register_types();
		flush_rewrite_rules();
	}
);
register_deactivation_hook( HSC_SPONS_FILE, 'flush_rewrite_rules' );
