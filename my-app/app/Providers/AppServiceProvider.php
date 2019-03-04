<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Twitter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
        echo "register ...\n";
        // $this->app->bind('Foo', 'bar');
        app()->bind('foo', function () {
            return 'bar';
        });
        $this->app->bind('foo', function () {
            return 'New Bar';
        });

        $this->app->singleton(Twitter::class, function () {
            return new Twitter('my-api-key');
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
