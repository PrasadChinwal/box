<?php

namespace PrasadChinwal\Box;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use PrasadChinwal\Box\Storage\BoxFlysystemAdapter;

class BoxServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/box.php' => config_path('box.php'),
        ], 'box-config');

        Storage::extend('box', function (Application $app, array $config) {
            $box = $app->make('box');
            $adapter = new BoxFlysystemAdapter(
                $box,
                $config['folder_id'] ?? null
            );

            return new FilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config
            );
        });
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/box.php', 'box-config'
        );
        $this->app->singleton('box', function () {
            return new Box();
        });
    }
}
