<?php
/**
 * Self-updater based on plugin-update-checker, reading GitHub releases.
 *
 * @package HscSponsoren
 */

declare(strict_types=1);

namespace Hsc\Sponsoren;

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
use YahnisElsts\PluginUpdateChecker\v5p7\Vcs\GitHubApi;
use YahnisElsts\PluginUpdateChecker\v5p7\Vcs\PluginUpdateChecker;

/**
 * Makes new GitHub releases show up as regular updates in the WordPress admin.
 */
final class Updater {

	/**
	 * Creates the updater.
	 *
	 * @param string $plugin_file Absolute path of the main plugin file.
	 * @param string $repo        GitHub "owner/name". The repository must be public.
	 * @param string $slug        Plugin slug.
	 */
	public function __construct(
		private readonly string $plugin_file,
		private readonly string $repo,
		private readonly string $slug
	) {}

	/**
	 * Whether updates are switched off for this environment.
	 *
	 * In local development the plugin folder is a bind mount of the git repo. A plugin update deletes
	 * that folder first, which would wipe the working copy including .git. Define
	 * HSC_DISABLE_UPDATER as true to opt out anywhere else.
	 */
	public static function is_disabled(): bool {
		if ( defined( 'HSC_DISABLE_UPDATER' ) && true === constant( 'HSC_DISABLE_UPDATER' ) ) {
			return true;
		}

		return in_array( wp_get_environment_type(), array( 'local', 'development' ), true );
	}

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		if ( self::is_disabled() ) {
			return;
		}

		if ( ! class_exists( PucFactory::class ) ) {
			add_action( 'admin_notices', array( $this, 'render_missing_vendor_notice' ) );
			return;
		}

		$checker = PucFactory::buildUpdateChecker(
			sprintf( 'https://github.com/%s/', $this->repo ),
			$this->plugin_file,
			$this->slug
		);
		if ( ! $checker instanceof PluginUpdateChecker ) {
			return;
		}

		// Update from the zip attached to the GitHub release by the CI pipeline.
		// Branch zipballs must not be used: they lack the vendor/ directory.
		$api = $checker->getVcsApi();
		if ( $api instanceof GitHubApi ) {
			$api->enableReleaseAssets();
		}
	}

	/**
	 * Warns admins that updates cannot work because vendor/ is missing.
	 *
	 * This happens when the plugin was installed from the repository source instead of the release zip.
	 */
	public function render_missing_vendor_notice(): void {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html(
				sprintf(
					'Das Plugin „%s“ kann keine Updates erhalten, weil der Ordner vendor/ fehlt. Installiere das Plugin einmalig aus dem ZIP des neuesten GitHub-Releases.',
					$this->slug
				)
			)
		);
	}
}
