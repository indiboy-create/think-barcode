<?php

declare(strict_types=1);

namespace youvision\think\barcode;

use think\Facade as ThinkFacade;

/**
 * @method static Barcode code(string $code)
 * @method static Barcode type(string $type)
 * @method static Barcode format(string $format)
 * @method static Barcode width_factor(int|float $factor)
 * @method static Barcode width(int|float|null $width)
 * @method static Barcode height(int|float $height)
 * @method static Barcode foreground_color(int|string|array $color)
 * @method static Barcode background_color(int|string|array|null $color)
 * @method static Barcode image_driver(?string $driver)
 * @method static Barcode use_gd()
 * @method static Barcode use_imagick()
 * @method static Barcode svg_type(string $type)
 * @method static \Picqer\Barcode\Barcode barcode()
 * @method static string render()
 * @method static string to_string()
 * @method static string data_uri()
 * @method static string mime_type()
 * @method static string extension()
 * @method static string filename(string $name = 'barcode')
 * @method static string save(string $path)
 * @method static string save_to_dir(string $directory, string $name = 'barcode')
 * @method static \think\Response response(?string $filename = null, bool $download = false)
 * @method static \think\Response download(string $filename = 'barcode')
 * @method static \think\Response png()
 * @method static \think\Response jpg()
 * @method static \think\Response svg()
 * @method static \think\Response html()
 *
 * @see Barcode
 */
class Facade extends ThinkFacade
{
    protected static function getFacadeClass(): string
    {
        return Barcode::class;
    }
}