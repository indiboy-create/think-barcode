<?php

declare(strict_types=1);

use youvision\think\barcode\Barcode;
use youvision\think\barcode\Facade;

require __DIR__ . '/../vendor/autoload.php';

$failures = 0;

$check = static function (string $name, bool $ok) use (&$failures): void {
    echo ($ok ? '[OK]   ' : '[FAIL] ') . $name . PHP_EOL;

    if (!$ok) {
        $failures++;
    }
};

$throws = static function (callable $callback): bool {
    try {
        $callback();
    } catch (InvalidArgumentException) {
        return true;
    }

    return false;
};

$cleanup = static function (string $directory): void {
    if (!is_dir($directory)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }

    rmdir($directory);
};

$code = '081231723897';
$ean = '4006381333931';

$png = Barcode::make($code)->render();
$check('PNG 输出', str_starts_with($png, "\x89PNG"));
$check('PNG MIME 类型', Barcode::make($code)->mime_type() === 'image/png');
$check('PNG 扩展名', Barcode::make($code)->extension() === 'png');

$jpg = Barcode::make($code)->format('jpg')->render();
$check('JPG 输出', str_starts_with($jpg, "\xFF\xD8\xFF"));
$check('JPEG 别名', Barcode::make($code)->format('jpeg')->extension() === 'jpg');

$svg = Barcode::make($code)->format('svg')->render();
$check('SVG 输出', str_contains($svg, '<svg') && str_contains($svg, '</svg>'));
$check('SVG MIME 类型', Barcode::make($code)->format('svg')->mime_type() === 'image/svg+xml');

$inline = Barcode::make($code)->format('svg')->svg_type('inline')->render();
$check('内联 SVG', str_contains($inline, '<svg') && !str_contains($inline, '<?xml'));

$html = Barcode::make($code)->format('html')->render();
$check('HTML 输出', str_contains($html, '<div'));

$dynamic = Barcode::make($code)->format('dynamic_html')->render();
$check('动态 HTML 输出', str_contains($dynamic, '<div') && str_contains($dynamic, '%'));

$barcode_width = Barcode::make($code)->barcode()->getWidth();
$custom = Barcode::make($code)->format('svg')->width_factor(3)->height(80)->render();
$check('width_factor 生效', str_contains($custom, 'width="' . ($barcode_width * 3) . '"'));
$check('height 生效', str_contains($custom, 'height="80"'));

$fixed = Barcode::make($code)->format('svg')->width(123.5)->render();
$check('width 生效', str_contains($fixed, 'width="123.5"'));

$check('use_gd 生效', str_starts_with(Barcode::make($code)->use_gd()->render(), "\x89PNG"));
$check('非法图片驱动异常', $throws(static fn () => Barcode::make($code)->image_driver('vips')->render()));

$colored = Barcode::make($code)->format('svg')->foreground_color('#ff0000')->background_color('white')->render();
$check('前景色生效', str_contains($colored, 'rgb(255,0,0)'));
$check('背景色生效', str_contains($colored, 'rgb(255,255,255)'));

$transparent = Barcode::make($code)->format('svg')->background_color('transparent')->render();
$check('透明背景生效', !str_contains($transparent, '<rect id="background"'));

$uri = Barcode::make($code)->data_uri();
$prefix = 'data:image/png;base64,';
$check('Data URI', str_starts_with($uri, $prefix));
$check('Data URI 内容', str_starts_with((string) base64_decode(substr($uri, strlen($prefix))), "\x89PNG"));

$check('类型别名 code128', str_starts_with(Barcode::make($code)->type('code128')->render(), "\x89PNG"));
$check('类型常量 C128', str_starts_with(Barcode::make($code)->type('C128')->render(), "\x89PNG"));
$check('类型别名 ean13', str_starts_with(Barcode::make($ean)->type('ean13')->render(), "\x89PNG"));
$check('类型别名 ean_13', str_starts_with(Barcode::make($ean)->type('ean_13')->render(), "\x89PNG"));

$directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'think-barcode-' . bin2hex(random_bytes(4));
$file = Barcode::make($code)->save($directory . DIRECTORY_SEPARATOR . 'barcode.png');
$check('保存文件', is_file($file) && filesize($file) > 0);

$nested = Barcode::make($code)->save_to_dir($directory . DIRECTORY_SEPARATOR . 'nested', 'demo');
$check('保存到目录', is_file($nested) && str_ends_with($nested, 'demo.png'));

$response = Barcode::make($code)->png();
$check('Response 内容', $response instanceof think\Response && str_starts_with($response->getContent(), "\x89PNG"));
$check('Response 头', str_contains((string) $response->getHeader('Content-Type'), 'image/png'));

$download = Barcode::make($code)->download('demo.png');
$check('下载响应头', str_contains((string) $download->getHeader('Content-Disposition'), 'attachment'));

$check('辅助函数', function_exists('barcode') && str_starts_with(barcode($code)->render(), "\x89PNG"));
$check('Facade 类', class_exists(Facade::class));

$check('空内容异常', $throws(static fn () => Barcode::make('')->render()));
$check('未知类型异常', $throws(static fn () => Barcode::make($code)->type('unknown')->render()));
$check('未知格式异常', $throws(static fn () => Barcode::make($code)->format('webp')->render()));
$check('非法颜色异常', $throws(static fn () => Barcode::make($code)->foreground_color('#xyz')->render()));
$check('非法宽度异常', $throws(static fn () => Barcode::make($code)->width_factor(0)->render()));

$cleanup($directory);

echo PHP_EOL . ($failures === 0 ? 'ALL PASSED' : $failures . ' FAILED') . PHP_EOL;

exit($failures === 0 ? 0 : 1);