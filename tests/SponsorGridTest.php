<?php

declare(strict_types=1);

use Hsc\Sponsoren\Sponsor_Grid;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SponsorGridTest extends TestCase
{
    /**
     * @return array<string, array{string, int}>
     */
    public static function columnsProvider(): array
    {
        return [
            'empty gives default'        => ['', 4],
            'blank gives default'        => ['  ', 4],
            'text gives default'         => ['viele', 4],
            'decimal gives default'      => ['2.5', 4],
            'plain number'               => ['3', 3],
            'spaces are ignored'         => [' 6 ', 6],
            'lower limit'                => ['1', 1],
            'zero is raised to minimum'  => ['0', 1],
            'negative is raised'         => ['-3', 1],
            'upper limit'                => ['8', 8],
            'too big is cut to maximum'  => ['40', 8],
        ];
    }

    #[DataProvider('columnsProvider')]
    public function testColumns(string $raw, int $expected): void
    {
        self::assertSame($expected, Sponsor_Grid::columns($raw));
    }

    public function testColumnsUsesFallbackForInvalidInput(): void
    {
        self::assertSame(2, Sponsor_Grid::columns('', 2));
        self::assertSame(5, Sponsor_Grid::columns('5', 2));
    }

    /**
     * @return array<string, array{string, string, string, array{desktop: int, tablet: int, mobile: int}}>
     */
    public static function responsiveProvider(): array
    {
        return [
            'nothing set'                       => ['', '', '', ['desktop' => 4, 'tablet' => 3, 'mobile' => 2]],
            'small desktop caps tablet/mobile'  => ['1', '', '', ['desktop' => 1, 'tablet' => 1, 'mobile' => 1]],
            'two columns on desktop'            => ['2', '', '', ['desktop' => 2, 'tablet' => 2, 'mobile' => 2]],
            'wide desktop'                      => ['8', '', '', ['desktop' => 8, 'tablet' => 3, 'mobile' => 2]],
            'all set'                           => ['6', '4', '1', ['desktop' => 6, 'tablet' => 4, 'mobile' => 1]],
            'tablet may be wider than desktop'  => ['2', '5', '', ['desktop' => 2, 'tablet' => 5, 'mobile' => 2]],
            'invalid falls back, too big is cut' => ['6', 'x', '99', ['desktop' => 6, 'tablet' => 3, 'mobile' => 8]],
        ];
    }

    /**
     * @param array{desktop: int, tablet: int, mobile: int} $expected
     */
    #[DataProvider('responsiveProvider')]
    public function testResponsive(string $desktop, string $tablet, string $mobile, array $expected): void
    {
        self::assertSame($expected, Sponsor_Grid::responsive($desktop, $tablet, $mobile));
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function enabledProvider(): array
    {
        return [
            'empty is off'      => ['', false],
            'true'              => ['true', true],
            'upper case'        => ['TRUE', true],
            'one'               => ['1', true],
            'yes'               => ['yes', true],
            'ja with spaces'    => [' ja ', true],
            'false'             => ['false', false],
            'zero'              => ['0', false],
            'nein'              => ['nein', false],
            'random text'       => ['vielleicht', false],
        ];
    }

    #[DataProvider('enabledProvider')]
    public function testIsEnabled(string $raw, bool $expected): void
    {
        self::assertSame($expected, Sponsor_Grid::is_enabled($raw));
    }
}
