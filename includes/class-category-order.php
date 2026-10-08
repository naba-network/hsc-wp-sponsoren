<?php
/**
 * Stores the sponsor order per category and keeps it in sync with the sponsors.
 *
 * @package HscSponsoren
 */

declare(strict_types=1);

namespace Hsc\Sponsoren;

use WP_Post;
use WP_Term;

/**
 * The order of one category is a list of sponsor IDs saved as term meta (no extra table).
 * Deleting a category removes its term meta automatically.
 */
final class Category_Order {

	public const META_ORDER = '_hsc_sponsor_order';

	/**
	 * Post statuses that count as "in the list" (everything except trash and auto-drafts).
	 */
	private const STATUSES = array( 'publish', 'future', 'draft', 'pending', 'private' );

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'set_object_terms', array( $this, 'on_set_terms' ), 10, 6 );
		add_action( 'trashed_post', array( $this, 'on_trashed' ) );
		add_action( 'untrashed_post', array( $this, 'on_untrashed' ) );
		add_action( 'before_delete_post', array( $this, 'on_before_delete' ), 10, 2 );
	}

	/**
	 * Sponsor IDs of a category in display order.
	 *
	 * @param int $term_id Category term ID.
	 * @return array<int>
	 */
	public function ordered_ids( int $term_id ): array {
		return Sponsor_Order::reconcile( $this->stored( $term_id ), $this->member_ids( $term_id ) );
	}

	/**
	 * Saves a new order. IDs that are not in the category are ignored, missing ones go to the bottom.
	 *
	 * @param int        $term_id Category term ID.
	 * @param array<int> $ids     Submitted order.
	 */
	public function save( int $term_id, array $ids ): void {
		update_term_meta( $term_id, self::META_ORDER, Sponsor_Order::reconcile( $ids, $this->member_ids( $term_id ) ) );
	}

	/**
	 * Keeps the order in sync when the categories of a sponsor change: new ones go to the bottom.
	 *
	 * @param int        $object_id  Post ID.
	 * @param array<int> $terms      Terms passed to wp_set_object_terms (unused).
	 * @param array<int> $tt_ids     Term taxonomy IDs (unused).
	 * @param string     $taxonomy   Taxonomy slug.
	 * @param bool       $append     Whether terms were appended (unused).
	 * @param array<int> $old_tt_ids Term taxonomy IDs before the change.
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function on_set_terms( int $object_id, $terms, $tt_ids, string $taxonomy, $append, $old_tt_ids ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		if ( Post_Type::TAXONOMY !== $taxonomy || ! $this->is_tracked( $object_id ) ) {
			return;
		}

		$current = $this->term_ids_of( $object_id );
		foreach ( $current as $term_id ) {
			$this->add_to_term( $term_id, $object_id );
		}

		foreach ( (array) $old_tt_ids as $tt_id ) {
			$term = get_term_by( 'term_taxonomy_id', (int) $tt_id, Post_Type::TAXONOMY );
			if ( $term instanceof WP_Term && ! in_array( $term->term_id, $current, true ) ) {
				$this->remove_from_term( $term->term_id, $object_id );
			}
		}
	}

	/**
	 * A trashed sponsor leaves all lists.
	 *
	 * @param int $post_id Post ID.
	 */
	public function on_trashed( int $post_id ): void {
		$this->remove_everywhere( $post_id );
	}

	/**
	 * A restored sponsor goes to the bottom of its categories.
	 *
	 * @param int $post_id Post ID.
	 */
	public function on_untrashed( int $post_id ): void {
		if ( ! $this->is_tracked( $post_id ) ) {
			return;
		}
		foreach ( $this->term_ids_of( $post_id ) as $term_id ) {
			$this->add_to_term( $term_id, $post_id );
		}
	}

	/**
	 * A permanently deleted sponsor leaves all lists.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function on_before_delete( int $post_id, WP_Post $post ): void {
		if ( Post_Type::POST_TYPE === $post->post_type ) {
			$this->remove_everywhere( $post_id );
		}
	}

	/**
	 * Removes a sponsor from the order of each of its categories.
	 *
	 * @param int $post_id Post ID.
	 */
	private function remove_everywhere( int $post_id ): void {
		if ( Post_Type::POST_TYPE !== get_post_type( $post_id ) ) {
			return;
		}
		foreach ( $this->term_ids_of( $post_id ) as $term_id ) {
			$this->remove_from_term( $term_id, $post_id );
		}
	}

	/**
	 * Puts a sponsor at the bottom. Older members missing from the stored order are placed before it.
	 *
	 * @param int $term_id Category term ID.
	 * @param int $post_id Sponsor ID.
	 */
	private function add_to_term( int $term_id, int $post_id ): void {
		$members = array_values( array_diff( $this->member_ids( $term_id ), array( $post_id ) ) );
		$order   = Sponsor_Order::reconcile( $this->stored( $term_id ), $members );

		update_term_meta( $term_id, self::META_ORDER, Sponsor_Order::append( $order, $post_id ) );
	}

	/**
	 * Drops a sponsor from the stored order of one category.
	 *
	 * @param int $term_id Category term ID.
	 * @param int $post_id Sponsor ID.
	 */
	private function remove_from_term( int $term_id, int $post_id ): void {
		update_term_meta( $term_id, self::META_ORDER, Sponsor_Order::remove( $this->stored( $term_id ), $post_id ) );
	}

	/**
	 * Whether the post is a sponsor that belongs in the lists (no auto-drafts, no trash).
	 *
	 * @param int $post_id Post ID.
	 */
	private function is_tracked( int $post_id ): bool {
		return Post_Type::POST_TYPE === get_post_type( $post_id ) && in_array( get_post_status( $post_id ), self::STATUSES, true );
	}

	/**
	 * Category term IDs of a sponsor.
	 *
	 * @param int $post_id Post ID.
	 * @return array<int>
	 */
	private function term_ids_of( int $post_id ): array {
		$ids = wp_get_object_terms( $post_id, Post_Type::TAXONOMY, array( 'fields' => 'ids' ) );

		return is_wp_error( $ids ) ? array() : array_values( array_map( 'intval', $ids ) );
	}

	/**
	 * Stored order of a category.
	 *
	 * @param int $term_id Category term ID.
	 * @return array<int>
	 */
	private function stored( int $term_id ): array {
		return Sponsor_Order::normalize( get_term_meta( $term_id, self::META_ORDER, true ) );
	}

	/**
	 * Sponsors currently in a category, oldest first.
	 *
	 * @param int $term_id Category term ID.
	 * @return array<int>
	 */
	private function member_ids( int $term_id ): array {
		$ids = get_posts(
			array(
				'post_type'        => Post_Type::POST_TYPE,
				'post_status'      => self::STATUSES,
				'numberposts'      => -1,
				'fields'           => 'ids',
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'no_found_rows'    => true,
				'suppress_filters' => true,
				'tax_query'        => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => Post_Type::TAXONOMY,
						'field'    => 'term_id',
						'terms'    => array( $term_id ),
					),
				),
			)
		);

		return array_values( array_map( 'intval', $ids ) );
	}
}
