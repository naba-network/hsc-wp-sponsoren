<?php

declare(strict_types=1);

use Hsc\Sponsoren\Sponsor_Order;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SponsorOrderTest extends TestCase
{
    /**
     * @return array<string, array{list<int>, int, list<int>}>
     */
    public static function appendProvider(): array
    {
        return [
            'new id goes to the bottom' => [[3, 1], 7, [3, 1, 7]],
            'known id keeps its place'  => [[3, 1, 7], 1, [3, 1, 7]],
            'empty list'                => [[], 5, [5]],
        ];
    }

    /**
     * @param list<int> $order
     * @param list<int> $expected
     */
    #[DataProvider('appendProvider')]
    public function testAppend(array $order, int $id, array $expected): void
    {
        self::assertSame($expected, Sponsor_Order::append($order, $id));
    }

    /**
     * @return array<string, array{list<int>, int, list<int>}>
     */
    public static function removeProvider(): array
    {
        return [
            'middle id is removed and list is reindexed' => [[3, 1, 7], 1, [3, 7]],
            'unknown id changes nothing'                 => [[3, 1], 9, [3, 1]],
            'last id leaves an empty list'               => [[4], 4, []],
        ];
    }

    /**
     * @param list<int> $order
     * @param list<int> $expected
     */
    #[DataProvider('removeProvider')]
    public function testRemove(array $order, int $id, array $expected): void
    {
        self::assertSame($expected, Sponsor_Order::remove($order, $id));
    }

    /**
     * @return array<string, array{list<int>, list<int>, list<int>}>
     */
    public static function reconcileProvider(): array
    {
        return [
            'same members keep the stored order'      => [[3, 1, 7], [1, 3, 7], [3, 1, 7]],
            'deleted sponsor is dropped'              => [[3, 1, 7], [1, 7], [1, 7]],
            'missing member goes to the bottom'       => [[3, 1], [1, 3, 9], [3, 1, 9]],
            'several missing members keep given order' => [[3], [3, 8, 5], [3, 8, 5]],
            'no stored order uses member order'       => [[], [4, 2, 6], [4, 2, 6]],
            'duplicates are removed'                  => [[3, 3, 1], [1, 3, 1], [3, 1]],
            'empty category'                          => [[3, 1], [], []],
        ];
    }

    /**
     * @param list<int> $order
     * @param list<int> $members
     * @param list<int> $expected
     */
    #[DataProvider('reconcileProvider')]
    public function testReconcile(array $order, array $members, array $expected): void
    {
        self::assertSame($expected, Sponsor_Order::reconcile($order, $members));
    }

    /**
     * @return array<string, array{mixed, list<int>}>
     */
    public static function normalizeProvider(): array
    {
        return [
            'clean ids'                  => [[3, 1, 7], [3, 1, 7]],
            'numeric strings from form'  => [['3', '1'], [3, 1]],
            'duplicates removed'         => [[3, '3', 1], [3, 1]],
            'zero and negatives dropped' => [[0, -2, 4], [4]],
            'text and floats dropped'    => [['abc', 1.5, '2x', 5], [5]],
            'nested arrays dropped'      => [[[1], 2], [2]],
            'not an array'               => ['3,1,7', []],
            'empty string from meta'     => ['', []],
            'null'                       => [null, []],
        ];
    }

    /**
     * @param mixed     $value
     * @param list<int> $expected
     */
    #[DataProvider('normalizeProvider')]
    public function testNormalize($value, array $expected): void
    {
        self::assertSame($expected, Sponsor_Order::normalize($value));
    }
}
