<?php
/**
 * List helpers for the sponsor slider (pure, no WordPress calls).
 *
 * @package HscSponsoren
 */

declare(strict_types=1);

namespace Hsc\Sponsoren;

/**
 * Prepares the list of slider cards.
 */
final class Slider_Items {

	/**
	 * Repeats the list until it has at least $minimum entries, so one copy of the band is wider than the screen.
	 *
	 * The order is kept: the whole list is appended again, never single entries. An empty list stays empty.
	 *
	 * @template T
	 * @param array<int, T> $items   Cards in display order.
	 * @param int           $minimum Wanted minimum count.
	 * @return array<int, T>
	 */
	public static function repeat_to_fill( array $items, int $minimum ): array {
		$items = array_values( $items );
		if ( array() === $items ) {
			return array();
		}

		$copies = max( 1, (int) ceil( $minimum / count( $items ) ) );

		return array_merge( ...array_fill( 0, $copies, $items ) );
	}

	/**
	 * Splits a comma separated attribute value into trimmed, unique, non-empty entries.
	 *
	 * @param string $raw Attribute value, e.g. "premium, sponsoren".
	 * @return array<int, string>
	 */
	public static function split_list( string $raw ): array {
		$parts = array_map( 'trim', explode( ',', $raw ) );

		return array_values( array_unique( array_filter( $parts, static fn( string $part ): bool => '' !== $part ) ) );
	}
}
