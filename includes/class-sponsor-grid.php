<?php
/**
 * Column handling of the sponsor grid (pure, no WordPress calls).
 *
 * @package HscSponsoren
 */

declare(strict_types=1);

namespace Hsc\Sponsoren;

/**
 * The attributes "grid-items", "grid-items-tablet" and "grid-items-mobile" say how many sponsors sit next to each other
 * per screen size. Tablet and mobile fall back to at most 3 and 2 columns when they are not set.
 */
final class Sponsor_Grid {

	public const DEFAULT_COLUMNS = 4;
	public const MIN_COLUMNS     = 1;
	public const MAX_COLUMNS     = 8;
	public const TABLET_FALLBACK = 3;
	public const MOBILE_FALLBACK = 2;

	/**
	 * Number of columns for the raw attribute value.
	 *
	 * Empty or non-numeric input gives the fallback, numbers outside the range are clamped.
	 *
	 * @param string $raw      Value of the attribute.
	 * @param int    $fallback Value for empty or invalid input.
	 */
	public static function columns( string $raw, int $fallback = self::DEFAULT_COLUMNS ): int {
		$value = trim( $raw );
		if ( 1 !== preg_match( '/^-?[0-9]+$/', $value ) ) {
			return $fallback;
		}

		return max( self::MIN_COLUMNS, min( self::MAX_COLUMNS, (int) $value ) );
	}

	/**
	 * Columns for desktop, tablet and mobile.
	 *
	 * An empty tablet value is "desktop, but at most 3"; an empty mobile value is "desktop, but at most 2".
	 *
	 * @param string $desktop Raw "grid-items".
	 * @param string $tablet  Raw "grid-items-tablet".
	 * @param string $mobile  Raw "grid-items-mobile".
	 * @return array{desktop: int, tablet: int, mobile: int}
	 */
	public static function responsive( string $desktop, string $tablet, string $mobile ): array {
		$wide = self::columns( $desktop );

		return array(
			'desktop' => $wide,
			'tablet'  => self::columns( $tablet, min( $wide, self::TABLET_FALLBACK ) ),
			'mobile'  => self::columns( $mobile, min( $wide, self::MOBILE_FALLBACK ) ),
		);
	}
}
