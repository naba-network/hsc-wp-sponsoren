<?php
/**
 * Look variants of the sponsor slider (pure, no WordPress calls).
 *
 * @package HscSponsoren
 */

declare(strict_types=1);

namespace Hsc\Sponsoren;

/**
 * Premium: one row of large cards. Standard: three counter-running rows of small grey cards.
 * Plain constants instead of an enum because the plugin supports PHP 8.0.
 */
final class Slider_Variant {

	public const PREMIUM  = 'premium';
	public const STANDARD = 'standard';

	/**
	 * Rows per variant and how many cards one copy of a row needs to be wider than the widest screen
	 * (card width + gap: 220px for premium, 124px for standard; 2640px wide).
	 */
	private const SETTINGS = array(
		self::PREMIUM  => array(
			'rows'      => 1,
			'min_cards' => 12,
		),
		self::STANDARD => array(
			'rows'      => 3,
			'min_cards' => 22,
		),
	);

	/**
	 * Settings of a variant, or null when the name is unknown.
	 *
	 * @param string $name Value of the attribute, empty means premium.
	 * @return array{rows: int, min_cards: int}|null
	 */
	public static function settings( string $name ): ?array {
		$name = '' === trim( $name ) ? self::PREMIUM : strtolower( trim( $name ) );

		return self::SETTINGS[ $name ] ?? null;
	}

	/**
	 * Normalized variant name (empty means premium), or null when unknown.
	 *
	 * @param string $name Value of the attribute.
	 */
	public static function normalize( string $name ): ?string {
		$name = '' === trim( $name ) ? self::PREMIUM : strtolower( trim( $name ) );

		return isset( self::SETTINGS[ $name ] ) ? $name : null;
	}

	/**
	 * All variant names.
	 *
	 * @return array<int, string>
	 */
	public static function names(): array {
		return array_keys( self::SETTINGS );
	}
}
