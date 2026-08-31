<?php

namespace PrasadChinwal\Box;

use Illuminate\Support\ServiceProvider;

class BoxServiceProvider extends ServiceProvider
{
    public const CONFIG_TAG = 'box-config';

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/box.php' => config_path('box.php'),
        ], self::CONFIG_TAG);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/box.php', 'box'
        );
        $this->app->singleton('box', function () {
            return new Box();
        });
    }
}
