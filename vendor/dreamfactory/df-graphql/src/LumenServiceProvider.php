<?php namespace DreamFactory\Core\GraphQL;

use Illuminate\Support\Facades\Facade;

class LumenServiceProvider extends ServiceProvider
{
    /**
     * Get the active router.
     *
     * @return Router
     */
    protected function getRouter()
    {
        return property_exists($this->app, 'router') ? $this->app->router : $this->app;
    }

    /**
     * Bootstrap publishes
     *
     * @return void
     */
    protected function bootPublishes()
    {
        $configPath = __DIR__ . '/../config';
        $this->mergeConfigFrom($configPath . '/config.php', 'graphql');
    }

    /**
     * Bootstrap router
     *
     * @return void
     */
    protected function bootRouter()
    {
        if ($this->app['config']->get('graphql.routes')) {
            include __DIR__.'/../routes/routes.php';
        }
    }

    /**
     * Register facade
     *
     * @return void
     */
    public function register()
    {
        static $registered = false;
        // Check if facades are activated
        if (Facade::getFacadeApplication() == $this->app && !$registered) {
            class_alias(\DreamFactory\Core\GraphQL\Facades\GraphQL::class, 'GraphQL');
            $registered = true;
        }

        parent::register();
    }
}
