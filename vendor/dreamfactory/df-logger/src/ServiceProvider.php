<?php

namespace DreamFactory\Core\Logger;

use DreamFactory\Core\Enums\LicenseLevel;
use DreamFactory\Core\Enums\ServiceTypeGroups;
use DreamFactory\Core\Logger\Handlers\Events\LoggingEventHandler;
use DreamFactory\Core\Logger\Services\Logstash;
use DreamFactory\Core\Logger\Models\LogstashConfig;
use DreamFactory\Core\Services\ServiceManager;
use DreamFactory\Core\Services\ServiceType;
use Event;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        // Add our service types.
        $this->app->resolving('df.service', function (ServiceManager $df) {
            $df->addType(
                new ServiceType([
                    'name'                  => 'logstash',
                    'label'                 => 'Logstash',
                    'description'           => 'Logstash service.',
                    'group'                 => ServiceTypeGroups::LOG,
                    'subscription_required' => LicenseLevel::GOLD,
                    'config_handler'        => LogstashConfig::class,
                    'factory'               => function ($config) {
                        return new Logstash($config);
                    },
                ])
            );
        });
    }

    public function boot()
    {
        // add migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        Event::subscribe(new LoggingEventHandler());
    }
}