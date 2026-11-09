<?php

declare(strict_types=1);

namespace youvision\think\barcode;

use InvalidArgumentException;
use Picqer\Barcode\Barcode as PicqerBarcode;
use Picqer\Barcode\BarcodeGenerator;
use Picqer\Barcode\Exceptions\BarcodeException;
use Picqer\Barcode\Renderers\DynamicHtmlRenderer;
use Picqer\Barcode\Renderers\HtmlRenderer;
use Picqer\Barcode\Renderers\JpgRenderer;
use Picqer\Barcode\Renderers\PngRenderer;
use Picqer\Barcode\Renderers\SvgRenderer;
use ReflectionClass;
use think\Response;
use Throwable;

class Barcode
{
    protected string $code;

    protected array $config;

    public function __construct(string $code = '', array $config = [])
    {
        $defaults = require __DIR__ . '/../config/barcode.php';

        $this->config = array_merge($defaults, $config);
        $this->code = $code;
    }

    public static function make(string $code = '', array $config = []): static
    {
        return new static($code, array_merge(static::config(), $config));
    }

    public static function config(): array
    {
        if (!function_exists('app')) {
            return [];
        }

        try {
            $config = app('config')->get('barcode', []);
        } catch (Throwable) {
            return [];
        }

        return is_array($config) ? $config : [];
    }

    public function code(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function type(string $type): static
    {
        $this->config['type'] = $type;

        return $this;
    }

    public function format(string $format): static
    {
        $this->config['format'] = $format;

        return $this;
    }

    public function width_factor(int|float $factor): static
    {
        if ($factor <= 0) {
            throw new InvalidArgumentException('条码模块宽度必须大于 0');
        }

        $this->config['width_factor'] = $factor;

        return $this;
    }

    public function width(int|float|null $width): static
    {
        if ($width !== null && $width <= 0) {
            throw new InvalidArgumentException('条码宽度必须大于 0');
        }

        $this->config['width'] = $width;

        return $this;
    }

    public function height(int|float $height): static
    {
        if ($height <= 0) {
            throw new InvalidArgumentException('条码高度必须大于 0');
        }

        $this->config['height'] = $height;

        return $this;
    }

    public function foreground_color(int|string|array $color): static
    {
        $this->config['foreground'] = $color;

        return $this;
    }

    public function background_color(int|string|array|null $color): static
    {
        $this->config['background'] = $color;

        return $this;
    }

    public function image_driver(?string $driver): static
    {
        $this->config['image_driver'] = $driver;

        return $this;
    }

    public function use_gd(): static
    {
        return $this->image_driver('gd');
    }

    public function use_imagick(): static
    {
        return $this->image_driver('imagick');
    }

    public function svg_type(string $type): static
    {
        $this->config['svg_type'] = $type;

        return $this;
    }

    public function barcode(): PicqerBarcode
    {
        if (trim($this->code) === '') {
            throw new InvalidArgumentException('条码内容不能为空');
        }

        $generator = new class extends BarcodeGenerator {
            public function build(string $code, string $type): PicqerBarcode
            {
                return $this->getBarcodeData($code, $type);
            }
        };

        try {
            return $generator->build($this->code, $this->resolve_type((string) $this->config['type']));
        } catch (BarcodeException $e) {
            throw new InvalidArgumentException('条形码生成失败：' . $e->getMessage(), 0, $e);
        }
    }

    public function render(): string
    {
        $barcode = $this->barcode();
        $renderer = $this->renderer();

        $renderer->setForegroundColor($this->normalize_color($this->config['foreground'] ?? [0, 0, 0]));
        $renderer->setBackgroundColor($this->background_color_value());

        return $renderer->render($barcode, $this->pixel_width($barcode), $this->pixel_height());
    }

    public function to_string(): string
    {
        return $this->render();
    }

    public function data_uri(): string
    {
        $format = $this->resolve_format();

        if ($format === 'html' || $format === 'dynamic_html') {
            throw new InvalidArgumentException('HTML 格式不支持 Data URI');
        }

        return 'data:' . $this->mime_type() . ';base64,' . base64_encode($this->render());
    }

    public function mime_type(): string
    {
        return match ($this->resolve_format()) {
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            'html', 'dynamic_html' => 'text/html',
            default => throw new InvalidArgumentException('不支持的输出格式：' . $this->resolve_format()),
        };
    }

    public function extension(): string
    {
        return match ($this->resolve_format()) {
            'png' => 'png',
            'jpg' => 'jpg',
            'svg' => 'svg',
            'html', 'dynamic_html' => 'html',
            default => throw new InvalidArgumentException('不支持的输出格式：' . $this->resolve_format()),
        };
    }

    public function filename(string $name = 'barcode'): string
    {
        if ($name === '') {
            $name = 'barcode';
        }

        return str_contains($name, '.') ? $name : $name . '.' . $this->extension();
    }

    public function save(string $path): string
    {
        if ($path === '') {
            throw new InvalidArgumentException('保存路径不能为空');
        }

        if (str_ends_with($path, '/') || str_ends_with($path, '\\') || is_dir($path)) {
            $path = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . $this->filename();
        }

        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new InvalidArgumentException('无法创建目录：' . $directory);
        }

        if (file_put_contents($path, $this->render()) === false) {
            throw new InvalidArgumentException('无法写入文件：' . $path);
        }

        return $path;
    }

    public function save_to_dir(string $directory, string $name = 'barcode'): string
    {
        if ($directory === '') {
            throw new InvalidArgumentException('保存目录不能为空');
        }

        return $this->save(rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . $this->filename($name));
    }

    public function response(?string $filename = null, bool $download = false): Response
    {
        $content = $this->render();
        $format = $this->resolve_format();

        $headers = [
            'Content-Type' => $this->mime_type() . (in_array($format, ['html', 'dynamic_html'], true) ? '; charset=utf-8' : ''),
            'Content-Length' => (string) strlen($content),
        ];

        if ($download) {
            $headers['Content-Disposition'] = $this->disposition($this->filename($filename ?: 'barcode'), 'attachment');
        }

        return Response::create($content, 'html', 200)->header($headers);
    }

    public function download(string $filename = 'barcode'): Response
    {
        return $this->response($filename, true);
    }

    public function png(): Response
    {
        return $this->format('png')->response();
    }

    public function jpg(): Response
    {
        return $this->format('jpg')->response();
    }

    public function svg(): Response
    {
        return $this->format('svg')->response();
    }

    public function html(): Response
    {
        return $this->format('html')->response();
    }

    public function __toString(): string
    {
        return $this->render();
    }

    protected function renderer(): PngRenderer|JpgRenderer|SvgRenderer|HtmlRenderer|DynamicHtmlRenderer
    {
        $format = $this->resolve_format();

        $renderer = match ($format) {
            'png' => new PngRenderer(),
            'jpg' => new JpgRenderer(),
            'svg' => new SvgRenderer(),
            'html' => new HtmlRenderer(),
            'dynamic_html' => new DynamicHtmlRenderer(),
            default => throw new InvalidArgumentException('不支持的输出格式：' . $format),
        };

        if ($renderer instanceof PngRenderer) {
            $driver = strtolower(trim((string) $this->config['image_driver']));

            if ($driver === 'gd') {
                $renderer->useGd();
            } elseif ($driver === 'imagick') {
                $renderer->useImagick();
            } elseif ($driver !== '') {
                throw new InvalidArgumentException('不支持的图片驱动：' . $driver);
            }
        }

        if ($renderer instanceof SvgRenderer) {
            $svg_type = strtolower(trim((string) $this->config['svg_type']));

            if ($svg_type === '' || $svg_type === 'standalone') {
                $renderer->setSvgType(SvgRenderer::TYPE_SVG_STANDALONE);
            } elseif ($svg_type === 'inline') {
                $renderer->setSvgType(SvgRenderer::TYPE_SVG_INLINE);
            } else {
                throw new InvalidArgumentException('不支持的 SVG 输出类型：' . $svg_type);
            }
        }

        return $renderer;
    }

    protected function resolve_format(): string
    {
        $format = strtolower(trim((string) $this->config['format']));

        return match ($format) {
            'jpeg', 'jpe' => 'jpg',
            'dynamichtml', 'dynamic-html' => 'dynamic_html',
            default => $format,
        };
    }

    protected function pixel_width(PicqerBarcode $barcode): float
    {
        $width = $this->config['width'] ?? null;

        if ($width !== null && (float) $width > 0) {
            return (float) $width;
        }

        $factor = (float) ($this->config['width_factor'] ?? 0);

        if ($factor <= 0) {
            throw new InvalidArgumentException('条码模块宽度必须大于 0');
        }

        return $barcode->getWidth() * $factor;
    }

    protected function pixel_height(): float
    {
        $height = (float) ($this->config['height'] ?? 0);

        if ($height <= 0) {
            throw new InvalidArgumentException('条码高度必须大于 0');
        }

        return $height;
    }

    protected function resolve_type(string $type): string
    {
        $type = trim($type);

        if ($type === '') {
            return BarcodeGenerator::TYPE_CODE_128;
        }

        static $aliases = null;

        if ($aliases === null) {
            $aliases = [];

            foreach ((new ReflectionClass(BarcodeGenerator::class))->getConstants() as $name => $value) {
                if (!is_string($value) || !str_starts_with($name, 'TYPE_')) {
                    continue;
                }

                $aliases[strtolower(str_replace('_', '', substr($name, 5)))] = $value;
                $aliases[strtolower($value)] = $value;
            }
        }

        $normalized = strtolower((string) preg_replace('/[^a-z0-9]+/i', '', $type));

        if (!isset($aliases[$normalized])) {
            throw new InvalidArgumentException('不支持的条码类型：' . $type);
        }

        return $aliases[$normalized];
    }


    protected function background_color_value(): ?array
    {
        $color = $this->config['background'] ?? null;

        if ($color === null || $color === '') {
            return null;
        }

        if (is_string($color) && in_array(strtolower(trim($color)), ['transparent', 'none'], true)) {
            return null;
        }

        return $this->normalize_color($color);
    }

    protected function normalize_color(int|string|array $color): array
    {
        if (is_array($color)) {
            $channels = array_values($color);

            if (count($channels) < 3) {
                throw new InvalidArgumentException('颜色数组至少需要包含红、绿、蓝三个分量');
            }

            return [
                $this->channel($channels[0]),
                $this->channel($channels[1]),
                $this->channel($channels[2]),
            ];
        }

        if (is_int($color)) {
            return [($color >> 16) & 0xFF, ($color >> 8) & 0xFF, $color & 0xFF];
        }

        $value = strtolower(trim($color));

        $named = [
            'black' => [0, 0, 0],
            'white' => [255, 255, 255],
            'red' => [255, 0, 0],
            'green' => [0, 255, 0],
            'blue' => [0, 0, 255],
            'yellow' => [255, 255, 0],
            'gray' => [128, 128, 128],
            'grey' => [128, 128, 128],
        ];

        if (isset($named[$value])) {
            return $named[$value];
        }

        $hex = ltrim($value, '#');

        if (preg_match('/^[0-9a-f]{3}$/', $hex) === 1) {
            $hex = (string) preg_replace('/(.)/', '$1$1', $hex);
        }

        if (preg_match('/^[0-9a-f]{6}$/', $hex) !== 1) {
            throw new InvalidArgumentException('无法识别的颜色值：' . $color);
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    protected function channel(mixed $value): int
    {
        if (!is_numeric($value)) {
            throw new InvalidArgumentException('颜色分量必须是 0-255 的整数');
        }

        $channel = (int) $value;

        if ($channel < 0 || $channel > 255) {
            throw new InvalidArgumentException('颜色分量必须是 0-255 的整数');
        }

        return $channel;
    }

    protected function disposition(string $filename, string $type): string
    {
        $fallback = preg_replace('/[^\x20-\x7E]/', '_', $filename) ?: 'barcode';
        $encoded = rawurlencode($filename);

        return sprintf(
            '%s; filename="%s"; filename*=UTF-8\'\'%s',
            $type,
            addcslashes($fallback, '"\\'),
            $encoded,
        );
    }
}