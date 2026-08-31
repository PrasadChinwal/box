<?php

namespace PrasadChinwal\Box;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\FilesystemAdapter as LaravelFilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;

class BoxServiceProvider extends ServiceProvider
{
    public const CONFIG_TAG = 'box-config';

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/box.php' => config_path('box.php'),
        ], self::CONFIG_TAG);

        Storage::extend('box', function (Application $app, array $config) {
            $adapter = new BoxFileAdapter(
                folderId: (string) ($config['folder_id'] ?? config('box.folder_id', '0')),
                prefix: $config['prefix'] ?? '',
            );

            return new LaravelFilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config
            );
        });
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/box.php', 'box'
        );
        $this->app->singleton('box', function () {
            return new Box;
        });
    }
}
