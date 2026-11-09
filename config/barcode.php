<?php

return [
    // 默认输出格式，可选：png、jpg、svg、html、dynamic_html
    // png/jpg 需要 PHP 的 GD 或 Imagick 扩展；svg/html/dynamic_html 无扩展依赖
    // dynamic_html 使用百分比宽度，适合放入固定尺寸的容器中自适应显示
    'format' => 'png',

    // 默认条码类型，支持大小写和分隔符，如：code128、CODE_128、C128、ean13、EAN13
    // 常用类型：code128、code39、code39e、code93、ean13、ean8、upca、upce、itf14
    // 完整类型见 README 或 picqer/php-barcode-generator 的 BarcodeGenerator::TYPE_* 常量
    'type' => 'code128',

    // 自定义最终宽度，单位像素（SVG/HTML 为用户单位），留空或 null 表示按 width_factor 自动计算
    // 光栅图片（png/jpg）建议保持 null，或设置为条码内在宽度的整数倍，避免条宽像素不均匀
    'width' => null,

    // 单个条码模块（最小条）的宽度倍数，最终宽度 = 条码内在宽度 × width_factor
    // 数值越大图片越宽；光栅图片建议使用正整数
    'width_factor' => 2,

    // 条码高度，单位像素（SVG/HTML 为用户单位）
    'height' => 60,

    // 前景色（条码线条颜色），支持 [红, 绿, 蓝]、#RGB、#RRGGBB、black/white/red/green/blue/yellow/gray
    'foreground' => [0, 0, 0],

    // 背景色，支持 [红, 绿, 蓝]、#RGB、#RRGGBB 及命名颜色
    // null 或 transparent 表示透明背景；jpg 格式在透明时会自动使用白色背景
    'background' => null,

    // 光栅图片渲染驱动，可选：null（自动）、gd、imagick
    // null 时优先使用 Imagick，未安装则回退到 GD
    // 该配置仅对 png/jpg 格式生效
    'image_driver' => null,

    // SVG 输出类型，可选：standalone（独立 SVG 文件）、inline（可直接嵌入 HTML）
    // 该配置仅对 svg 格式生效
    'svg_type' => 'standalone',
];