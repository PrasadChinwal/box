<?php

namespace PrasadChinwal\Box;

use Illuminate\Support\ServiceProvider;
use PrasadChinwal\Box\File\BoxFile;
use PrasadChinwal\Box\Folder\BoxFolder;

class BoxServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/box.php' => config_path('box.php'),
        ], 'box-config');
    }

    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(Box::class, function () {
            return new Box();
        });

        $this->app->bind('box-file', fn() => new BoxFile(new Box()));
        $this->app->bind('box-folder', fn() => new BoxFolder(new Box()));
        $this->app->bind('box-user', fn() => new BoxUser(new Box()));
        $this->app->bind('box-collaboration', fn() => new BoxCollaboration(new Box()));
    }
}
