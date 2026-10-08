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
	 * Hook into WordPress.
	 */
	public function register(): void {
		if ( ! class_exists( PucFactory::class ) ) {
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
}
