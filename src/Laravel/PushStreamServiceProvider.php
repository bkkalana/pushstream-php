<?php

namespace PushStream\Laravel;

use Illuminate\Support\ServiceProvider;
use PushStream\PushStream;

class PushStreamServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(PushStream::class, function ($app) {
            $config = config('pushstream');
            
            return new PushStream(
                $config['app_id'],
                $config['app_key'],
                $config['app_secret'],
                ['apiUrl' => $config['api_url'] ?? 'http://localhost:8000']
            );
        });

        $this->app->alias(PushStream::class, 'pushstream');
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/pushstream.php' => config_path('pushstream.php'),
        ], 'pushstream-config');
    }
}
