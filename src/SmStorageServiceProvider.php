<?php

namespace Youke\SmStorage;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem as Flysystem;
use Illuminate\Filesystem\FilesystemAdapter;

class SmStorageServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Publication de la config
        $this->publishes([
            __DIR__ . '/../config/sm-storage.php' => config_path('sm-storage.php'),
        ], 'sm-storage-config');

        // Enregistrement du driver
        Storage::extend('sm', function ($app, $config) {
            $adapter = new SmStorageAdapter($config);
            $filesystem = new Flysystem($adapter, $config);

            return new FilesystemAdapter($filesystem, $adapter, $config);
        });
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/sm-storage.php',
            'sm-storage'
        );
    }
}