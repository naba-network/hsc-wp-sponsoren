<?php

declare(strict_types=1);

use Hsc\Sponsoren\Slider_Items;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SliderItemsTest extends TestCase
{
    /**
     * @return array<string, array{list<string>, int, list<string>}>
     */
    public static function repeatProvider(): array
    {
        return [
            'empty list stays empty'              => [[], 5, []],
            'enough items are left alone'         => [['a', 'b', 'c'], 3, ['a', 'b', 'c']],
            'minimum zero changes nothing'        => [['a', 'b'], 0, ['a', 'b']],
            'whole list is repeated in order'     => [['a', 'b'], 5, ['a', 'b', 'a', 'b', 'a', 'b']],
            'single item fills up'                => [['a'], 3, ['a', 'a', 'a']],
            'gaps in the keys are closed'         => [[3 => 'a', 7 => 'b'], 2, ['a', 'b']],
        ];
    }

    /**
     * @param array<int, string> $items
     * @param list<string>       $expected
     */
    #[DataProvider('repeatProvider')]
    public function testRepeatToFill(array $items, int $minimum, array $expected): void
    {
        self::assertSame($expected, Slider_Items::repeat_to_fill($items, $minimum));
    }

    /**
     * @return array<string, array{string, list<string>}>
     */
    public static function splitProvider(): array
    {
        return [
            'single value'            => ['premium', ['premium']],
            'two values with spaces'  => ['premium, sponsoren', ['premium', 'sponsoren']],
            'empty entries dropped'   => [' ,premium,, ', ['premium']],
            'duplicates dropped'      => ['a,b,a', ['a', 'b']],
            'empty string'            => ['', []],
        ];
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('splitProvider')]
    public function testSplitList(string $raw, array $expected): void
    {
        self::assertSame($expected, Slider_Items::split_list($raw));
    }
}
