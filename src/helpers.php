<?php

declare(strict_types=1);

use youvision\think\barcode\Barcode;

if (!function_exists('barcode')) {
    function barcode(string $code = '', array $config = []): Barcode
    {
        return Barcode::make($code, $config);
    }
}