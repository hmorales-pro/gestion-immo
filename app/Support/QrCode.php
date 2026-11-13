<?php

namespace App\Support;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/** QR codes as inline SVG: pure PHP, no image extension, nothing to maintain. */
class QrCode
{
    public static function svg(string $text, int $size = 320): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size, margin: 1, fill: Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(15, 23, 42))),
            new SvgImageBackEnd,
        );

        $svg = (new Writer($renderer))->writeString($text);

        // Drop the XML declaration so the SVG can be inlined in HTML.
        return trim(preg_replace('/^<\?xml[^>]*>\s*/', '', $svg));
    }
}
