<?php

declare(strict_types=1);

namespace youvision\think\barcode;

use think\Service as BaseService;

class Service extends BaseService
{
    public function register(): void
    {
        $defaults = require __DIR__ . '/../config/barcode.php';
        $config = $this->app->config->get('barcode', []);
        $config = is_array($config) ? $config : [];

        $this->app->config->set(array_merge($defaults, $config), 'barcode');

        $factory = static function () {
            return Barcode::make();
        };

        $this->app->bind(Barcode::class, $factory);
        $this->app->bind('barcode', $factory);
    }
}