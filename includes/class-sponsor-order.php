<?php
/**
 * Ordering of sponsor IDs inside one category (pure, no WordPress calls).
 *
 * @package HscSponsoren
 */

declare(strict_types=1);

namespace Hsc\Sponsoren;

/**
 * List operations on an ordered list of sponsor (post) IDs.
 */
final class Sponsor_Order {

	/**
	 * Puts an ID at the bottom. IDs already in the list keep their place.
	 *
	 * @param array<int> $order Current order.
	 * @param int        $id    Sponsor ID.
	 * @return array<int>
	 */
	public static function append( array $order, int $id ): array {
		if ( in_array( $id, $order, true ) ) {
			return $order;
		}
		$order[] = $id;

		return $order;
	}

	/**
	 * Removes an ID from the list.
	 *
	 * @param array<int> $order Current order.
	 * @param int        $id    Sponsor ID.
	 * @return array<int>
	 */
	public static function remove( array $order, int $id ): array {
		return array_values(
			array_filter(
				$order,
				static fn( int $value ): bool => $value !== $id
			)
		);
	}

	/**
	 * Aligns a stored order with the sponsors that really are in the category.
	 *
	 * Unknown IDs (deleted or moved sponsors) are dropped; members missing from the stored order
	 * (e.g. added before the order existed) are put at the bottom in the given member order.
	 *
	 * @param array<int> $order      Stored order.
	 * @param array<int> $member_ids Sponsors currently in the category.
	 * @return array<int>
	 */
	public static function reconcile( array $order, array $member_ids ): array {
		$members = array_values( array_unique( $member_ids ) );
		$kept    = array_values(
			array_filter(
				array_unique( $order ),
				static fn( int $value ): bool => in_array( $value, $members, true )
			)
		);

		return array_merge( $kept, array_values( array_diff( $members, $kept ) ) );
	}

	/**
	 * Turns stored or submitted data into a clean list of positive, unique IDs.
	 *
	 * @param mixed $value Raw value (term meta or request data); anything that is not an array gives an empty list.
	 * @return array<int>
	 */
	public static function normalize( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$ids = array();
		foreach ( $value as $item ) {
			if ( ! is_scalar( $item ) || 1 !== preg_match( '/^[1-9][0-9]*$/', (string) $item ) ) {
				continue;
			}
			$ids[] = (int) $item;
		}

		return array_values( array_unique( $ids ) );
	}
}
