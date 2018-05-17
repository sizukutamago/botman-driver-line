<?php
/**
 * Created by PhpStorm.
 * User: sizukutamago
 * Date: 2018/05/17
 * Time: 18:55
 */

namespace Sizukutamago\Botman\Line\Providers;

use Illuminate\Support\ServiceProvider;

class LineDriverServiceProvider extends ServiceProvider
{
    protected $defer = false;

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../stubs/line.php' => config_path('botman/line.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Get the services provided by the provider.
     *
     * @return string[]
     */
    public function provides()
    {
        return [];
    }
}