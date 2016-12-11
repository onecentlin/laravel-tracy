<?php

namespace Recca0120\LaravelTracy;

use Illuminate\Support\Arr;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use Recca0120\LaravelTracy\Exceptions\Handler;
use Recca0120\LaravelTracy\Middleware\Dispatch;
use Recca0120\Terminal\TerminalServiceProvider;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Recca0120\LaravelTracy\Middleware\AppendDebugbar;

class LaravelTracyServiceProvider extends ServiceProvider
{
    /**
     * boot.
     *
     * @method boot
     *
     * @param \Illuminate\Contracts\Http\Kernel $kernel
     */
    public function boot(Kernel $kernel)
    {
        if ($this->app->runningInConsole() === true) {
            $this->publishes([
                __DIR__.'/../config/tracy.php' => $this->app->configPath().'/tracy.php',
            ], 'config');

            return;
        }

        if ($this->app['config']['tracy']['enabled'] === true) {
            $this->app->extend(ExceptionHandler::class, function ($exceptionHandler, $app) {
                return $app->make(Handler::class, [
                    'exceptionHandler' => $exceptionHandler,
                ]);
            });
            $kernel->prependMiddleware(Dispatch::class);
            $kernel->pushMiddleware(AppendDebugbar::class);
        }
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tracy.php', 'tracy');

        $this->app->singleton(Debugbar::class, function ($app) {
            $config = Arr::get($app['config'], 'tracy', []);
            // if (Arr::get($config, 'useLaravelSession', false) === true) {
            //     $handler = $this->app['session']->driver()->getHandler();
            //     session_set_save_handler(new SessionHandlerWrapper($handler), true);
            // }

            return new Debugbar($config, $app['request'], $app);
        });

        $this->app->singleton(BlueScreen::class, BlueScreen::class);

        if ($this->app['config']['tracy.panels.terminal'] === true) {
            $this->app->register(TerminalServiceProvider::class);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [ExceptionHandler::class];
    }
}