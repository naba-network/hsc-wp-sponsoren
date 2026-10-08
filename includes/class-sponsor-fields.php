<?php
/**
 * Sanitizing of the custom sponsor fields (pure, no WordPress calls).
 *
 * @package HscSponsoren
 */

declare(strict_types=1);

namespace Hsc\Sponsoren;

use DateTimeImmutable;

/**
 * Normalizes raw form input of the sponsor fields.
 */
final class Sponsor_Fields {

	public const DATE_FORMAT = 'Y-m-d';

	/**
	 * Returns a clean http(s) URL, or an empty string when the input is not usable.
	 *
	 * A missing scheme ("example.com") is completed with https.
	 *
	 * @param string $raw Raw user input.
	 */
	public static function website( string $raw ): string {
		$value = trim( $raw );
		if ( '' === $value ) {
			return '';
		}
		if ( 1 !== preg_match( '#^[a-z][a-z0-9+.-]*:#i', $value ) ) {
			$value = 'https://' . $value;
		}
		// Only web links are allowed (no javascript:, mailto: ...); FILTER_VALIDATE_URL also requires a host.
		if ( 1 !== preg_match( '#^https?://#i', $value ) || false === filter_var( $value, FILTER_VALIDATE_URL ) ) {
			return '';
		}

		return $value;
	}

	/**
	 * Returns the date as "Y-m-d", or an empty string when the input is not a real date.
	 *
	 * @param string $raw Raw user input.
	 */
	public static function since( string $raw ): string {
		$value = trim( $raw );
		$date  = DateTimeImmutable::createFromFormat( '!' . self::DATE_FORMAT, $value );
		if ( false === $date || $date->format( self::DATE_FORMAT ) !== $value ) {
			return '';
		}

		return $value;
	}
}
