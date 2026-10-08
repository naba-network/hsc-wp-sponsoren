<?php

declare(strict_types=1);

use Hsc\Sponsoren\Sponsor_Fields;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SponsorFieldsTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function websiteProvider(): array
    {
        return [
            'https url stays'              => ['https://example.com/path', 'https://example.com/path'],
            'http url stays'               => ['http://example.com', 'http://example.com'],
            'missing scheme gets https'    => ['example.com', 'https://example.com'],
            'surrounding space is trimmed' => ['  https://example.com  ', 'https://example.com'],
            'empty stays empty'            => ['', ''],
            'javascript is rejected'       => ['javascript:alert(1)', ''],
            'mailto is rejected'           => ['mailto:info@example.com', ''],
            'ftp is rejected'              => ['ftp://example.com', ''],
            'scheme without host rejected' => ['https://', ''],
            'text with space is rejected'  => ['not a url', ''],
        ];
    }

    #[DataProvider('websiteProvider')]
    public function testWebsite(string $raw, string $expected): void
    {
        self::assertSame($expected, Sponsor_Fields::website($raw));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function sinceProvider(): array
    {
        return [
            'valid date'            => ['2019-08-01', '2019-08-01'],
            'leap day'              => ['2024-02-29', '2024-02-29'],
            'space is trimmed'      => [' 2019-08-01 ', '2019-08-01'],
            'empty stays empty'     => ['', ''],
            'impossible day'        => ['2023-02-30', ''],
            'wrong format'          => ['01.08.2019', ''],
            'no zero padding'       => ['2019-8-1', ''],
            'garbage'               => ['yesterday', ''],
        ];
    }

    #[DataProvider('sinceProvider')]
    public function testSince(string $raw, string $expected): void
    {
        self::assertSame($expected, Sponsor_Fields::since($raw));
    }
}
