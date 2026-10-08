<?php

declare(strict_types=1);

use Hsc\Sponsoren\Slider_Variant;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SliderVariantTest extends TestCase
{
    /**
     * @return array<string, array{string, string|null}>
     */
    public static function nameProvider(): array
    {
        return [
            'empty means premium'        => ['', 'premium'],
            'blank means premium'        => ['  ', 'premium'],
            'premium'                    => ['premium', 'premium'],
            'standard'                   => ['standard', 'standard'],
            'case and spaces are ignored' => [' Standard ', 'standard'],
            'unknown name'               => ['gold', null],
        ];
    }

    #[DataProvider('nameProvider')]
    public function testNormalize(string $raw, ?string $expected): void
    {
        self::assertSame($expected, Slider_Variant::normalize($raw));
    }

    public function testSettingsOfKnownVariants(): void
    {
        self::assertSame(['rows' => 1, 'min_cards' => 12], Slider_Variant::settings('premium'));
        self::assertSame(['rows' => 3, 'min_cards' => 22], Slider_Variant::settings('standard'));
        self::assertSame(Slider_Variant::settings('premium'), Slider_Variant::settings(''));
    }

    public function testSettingsOfUnknownVariantIsNull(): void
    {
        self::assertNull(Slider_Variant::settings('gold'));
    }

    public function testNamesListsAllVariants(): void
    {
        self::assertSame(['premium', 'standard'], Slider_Variant::names());
    }
}
