<?php
/**
 * Derives a sponsor name from an uploaded file name (pure, no WordPress calls).
 *
 * @package HscSponsoren
 */

declare(strict_types=1);

namespace Hsc\Sponsoren;

/**
 * Sponsor name helper for the bulk upload.
 */
final class Sponsor_Title {

	/**
	 * File name without folder and extension, e.g. "Muster Bau.png" becomes "Muster Bau".
	 *
	 * @param string $file_name Client file name.
	 */
	public static function from_filename( string $file_name ): string {
		$base = basename( str_replace( '\\', '/', $file_name ) );
		$name = preg_replace( '/\.[A-Za-z0-9]{1,5}$/', '', $base );

		return trim( (string) $name );
	}
}
