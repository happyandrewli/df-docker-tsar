<?php
namespace DreamFactory\Core\MongoLogs;

use Illuminate\Routing\Router;

use Illuminate\Support\Facades\Route;
use Spatie\HttpLogger\Middlewares\HttpLogger;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function boot()
    {
        if (env('LOGSDB_ENABLED') != 'true') {
            return;
        }

        $configPath = __DIR__ . '/../config/http-logger.php';
        if (function_exists('config_path')) {
            $publishPath = config_path('http-logger.php');
        } else {
            $publishPath = base_path('config/http-logger.php');
        }
        $this->publishes([$configPath => $publishPath], 'config');
        // add migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->addMiddleware();
    }

    public function register()
    {
        if (env('LOGSDB_ENABLED') != 'true') {
            return;
        }

        $this->mergeConfigFrom(__DIR__ . '/../config/logs-db.php', 'database.connections');
        $this->mergeConfigFrom(__DIR__ . '/../config/http-logger.php', 'http-logger');
    }

    /**
     * Register any middleware aliases.
     *
     * @return void
     */
    protected function addMiddleware()
    {
        // the method name was changed in Laravel 5.4
        if (method_exists(Router::class, 'aliasMiddleware')) {
            Route::aliasMiddleware('df.http_logger', HttpLogger::class);
        } else {
            /** @noinspection PhpUndefinedMethodInspection */
            Route::middleware('df.http_logger', HttpLogger::class);
        }

        Route::pushMiddlewareToGroup('df.api', 'df.http_logger');
    }
}
