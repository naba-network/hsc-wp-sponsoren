<?php

declare(strict_types=1);

use Hsc\Sponsoren\Sponsor_Title;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SponsorTitleTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function titleProvider(): array
    {
        return [
            'plain png'                 => ['Muster Bau.png', 'Muster Bau'],
            'uppercase extension'       => ['Logo.JPG', 'Logo'],
            'dots inside the name stay' => ['Mueller.und.Soehne.webp', 'Mueller.und.Soehne'],
            'no extension'              => ['Sponsor', 'Sponsor'],
            'umlauts stay'              => ['Bäckerei Müller.jpeg', 'Bäckerei Müller'],
            'unix path is dropped'      => ['/tmp/folder/Alpha.png', 'Alpha'],
            'windows path is dropped'   => ['C:\\logos\\Beta.png', 'Beta'],
            'spaces are trimmed'        => ['  Gamma .png', 'Gamma'],
            'only extension'            => ['.png', ''],
            'empty'                     => ['', ''],
        ];
    }

    #[DataProvider('titleProvider')]
    public function testFromFilename(string $fileName, string $expected): void
    {
        self::assertSame($expected, Sponsor_Title::from_filename($fileName));
    }
}
