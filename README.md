# think-barcode

基于 [picqer/php-barcode-generator](https://github.com/picqer/php-barcode-generator) 的 ThinkPHP 8 条形码生成组件。

- 支持 PNG、JPG、SVG、HTML、动态 HTML 输出
- 支持 CODE 128、CODE 39、EAN-13、UPC-A 等 30 余种一维条码
- 支持前景色、背景色、模块宽度、高度、图片驱动和 SVG 输出模式配置
- 配置自动合并，可通过项目 `config/barcode.php` 覆盖
- 直接返回 ThinkPHP Response，支持浏览器展示、下载与文件保存
- 支持静态入口、依赖注入、Facade 与 `barcode()` 辅助函数

## 安装

```bash
composer require youvision/think-barcode
```

要求 PHP >= 8.2。生成 PNG/JPG 需要开启 GD 或 Imagick 扩展：

```ini
extension=gd
```

组件通过 ThinkPHP 的服务发现自动注册，无需手动配置。

如果项目未生成 `vendor/services.php`，可在 `app/service.php` 中手动注册：

```php
return [
    \youvision\think\barcode\Service::class,
];
```

## 配置

将配置文件发布到项目 `config/barcode.php` 后按需修改：

```bash
php think vendor:publish
```

未发布配置时组件会使用内置默认值，项目配置的优先级高于默认值。完整配置项说明见 [config/barcode.php](config/barcode.php)。

```php
return [
    'format' => 'png',
    'type' => 'code128',
    'width' => null,
    'width_factor' => 2,
    'height' => 60,
    'foreground' => [0, 0, 0],
    'background' => null,
    'image_driver' => null,
    'svg_type' => 'standalone',
];
```

## 使用

### 直接输出图片

```php
use youvision\think\barcode\Barcode;

class Index
{
    public function barcode()
    {
        return Barcode::make('081231723897')->png();
    }
}
```

指定条码类型、高度与颜色：

```php
return Barcode::make('4006381333931')
    ->type('ean13')
    ->height(80)
    ->foreground_color('#333333')
    ->background_color('#FFFFFF')
    ->png();
```

### SVG 输出

```php
return Barcode::make('081231723897')->svg();

// 内联 SVG，可直接嵌入 HTML
return Barcode::make('081231723897')->svg_type('inline')->svg();
```

### HTML 输出

```php
return Barcode::make('081231723897')->html();

// 百分比宽高，适合放入固定尺寸容器
return Barcode::make('081231723897')->format('dynamic_html')->html();
```

HTML 格式返回条码片段，可放入任意容器：

```html
<div style="width: 400px; height: 80px;"><?= Barcode::make('081231723897')->format('dynamic_html')->render() ?></div>
```

### 保存为文件

```php
$path = Barcode::make('081231723897')
    ->save(runtime_path() . 'barcode/thinkphp.png');

$path = Barcode::make('081231723897')
    ->save_to_dir(runtime_path() . 'barcode');
```

`save_to_dir()` 会自动创建目录并生成 `barcode.png`（或与输出格式匹配的扩展名）。

### 下载图片

```php
return Barcode::make('081231723897')->download('thinkphp.png');
```

### Data URI 与字符串

```php
$dataUri = Barcode::make('081231723897')->data_uri();

$content = Barcode::make('081231723897')->to_string();
```

### 辅助函数

```php
return barcode('081231723897')->height(80)->png();

$svg = barcode('081231723897')->type('code39')->format('svg')->render();
```

### Facade

```php
use youvision\think\barcode\Facade;

return Facade::code('081231723897')->height(80)->png();
```

### 依赖注入

```php
use youvision\think\barcode\Barcode;

class Index
{
    public function index(Barcode $barcode)
    {
        return $barcode->code('081231723897')->png();
    }
}
```

也可以使用容器别名 `app('barcode')`。

### 获取 picqer 条码对象

需要访问条码条宽、模块数等原始数据时：

```php
$barcode = Barcode::make('081231723897')->barcode();

$barcode->getBarcode(); // 原始内容
$barcode->getWidth();   // 条码内在宽度（模块数）
$barcode->getBars();    // 条码条集合
```

## 条码类型

类型名称不区分大小写，支持常量名、常量值以及带下划线、横线、空格的写法，例如 `code128`、`CODE_128`、`C128` 等价。

| 类型 | 说明 |
| --- | --- |
| `code128` | CODE 128，支持全字符集，扫描兼容性最好 |
| `code128a` / `code128b` / `code128c` | CODE 128 指定字符集 |
| `code39` | CODE 39，支持数字、字母和部分符号 |
| `code39checksum` | CODE 39 带校验位 |
| `code39e` / `code39echecksum` | CODE 39 扩展及扩展校验 |
| `code93` | CODE 93 |
| `ean13` | EAN-13，13 位商品条码 |
| `ean8` | EAN-8，8 位商品条码 |
| `ean2` / `ean5` | EAN 2 位、5 位附加码 |
| `upca` | UPC-A |
| `upce` | UPC-E |
| `itf14` | ITF-14，又称 GTIN-14 |
| `interleaved25` | 交叉二五码 |
| `standard25` | 标准二五码 |
| `msi` / `msichecksum` | MSI 及带校验 |
| `codabar` | CODABAR |
| `code11` | CODE 11 |
| `postnet` / `planet` | 美国邮政条码 |
| `rms4cc` / `kix` / `imb` | 英国及美国邮政条码 |
| `pharma` / `pharma2t` | 医药品条码 |
| `telepenalpha` / `telepennumeric` | Telepen 条码 |

完整类型可通过 `Picqer\Barcode\BarcodeGenerator::TYPE_*` 常量查看。

## 方法说明

| 方法 | 说明 |
| --- | --- |
| `make(string $code, array $config = [])` | 创建实例，默认读取 `barcode` 配置 |
| `code()` | 设置条码内容 |
| `type()` | 设置条码类型 |
| `format()` | 设置输出格式：`png`、`jpg`、`svg`、`html`、`dynamic_html` |
| `width_factor()` | 设置单个模块宽度倍数 |
| `width()` | 设置最终宽度，传 `null` 恢复自动计算 |
| `height()` | 设置条码高度 |
| `foreground_color()` | 设置前景色 |
| `background_color()` | 设置背景色，传 `null` 或 `transparent` 使用透明背景 |
| `image_driver()` | 设置图片驱动：`gd`、`imagick`、`null` 自动 |
| `use_gd()` / `use_imagick()` | 强制使用 GD / Imagick |
| `svg_type()` | 设置 SVG 输出类型：`standalone`、`inline` |
| `barcode()` | 获取 picqer 的 `Barcode` 对象 |
| `render()` / `to_string()` | 获取条码内容 |
| `data_uri()` | 获取 Base64 Data URI，HTML 格式不支持 |
| `mime_type()` | 获取 MIME 类型 |
| `extension()` | 获取文件扩展名 |
| `filename()` | 生成带扩展名的文件名 |
| `save()` | 保存到指定路径或已存在的目录 |
| `save_to_dir()` | 自动创建目录并保存 |
| `response()` | 返回 ThinkPHP Response，可指定文件名和是否下载 |
| `download()` | 返回下载响应 |
| `png()` / `jpg()` / `svg()` / `html()` | 以指定格式返回响应 |

颜色支持 `[红, 绿, 蓝]`、`#RGB`、`#RRGGBB`、`black`、`white`、`red`、`green`、`blue`、`yellow`、`gray` 等写法。

## 注意事项

- picqer/php-barcode-generator 只生成条码线条，不生成条码下方的文字，如需显示内容请自行在页面或图片上叠加。
- PNG/JPG 的最终宽度最好是条码内在宽度的整数倍，否则条宽可能被分配到小数像素，影响扫描识别。默认按 `width_factor` 自动计算即为整数倍。
- `dynamic_html` 输出使用百分比定位，宽高由外层容器决定，此时 `width`、`width_factor`、`height` 配置不起作用。
- 透明背景在 SVG、HTML、PNG 中生效；JPG 不支持透明，未设置背景色时使用白色背景。

## 目录结构

```
config/barcode.php    配置文件
src/Barcode.php       条形码生成类
src/Service.php       服务提供者
src/Facade.php        Facade
src/helpers.php       辅助函数
tests/verify.php      自检脚本
```

## 许可证

[MIT](LICENSE)