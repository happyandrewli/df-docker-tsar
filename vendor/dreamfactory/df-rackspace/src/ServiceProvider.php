<?php
namespace DreamFactory\Core\Rackspace;

use DreamFactory\Core\Enums\ServiceTypeGroups;
use DreamFactory\Core\Rackspace\Models\OpenStackObjectStorageConfig;
use DreamFactory\Core\Rackspace\Models\RackspaceCloudFilesConfig;
use DreamFactory\Core\Rackspace\Services\OpenStackObjectStore;
use DreamFactory\Core\Services\ServiceManager;
use DreamFactory\Core\Services\ServiceType;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        // Add our service types.
        $this->app->resolving('df.service', function (ServiceManager $df) {
            $df->addType(
                new ServiceType([
                    'name'            => 'rackspace_cloud_files',
                    'label'           => 'Rackspace Cloud Files',
                    'description'     => 'File service supporting Rackspace Cloud Files Storage system.',
                    'group'           => ServiceTypeGroups::FILE,
                    'config_handler'  => RackspaceCloudFilesConfig::class,
                    'factory'         => function ($config) {
                        return new OpenStackObjectStore($config);
                    },
                ])
            );
            $df->addType(
                new ServiceType([
                    'name'            => 'openstack_object_storage',
                    'label'           => 'OpenStack Object Storage',
                    'description'     => 'File service supporting OpenStack Object Storage system.',
                    'group'           => ServiceTypeGroups::FILE,
                    'config_handler'  => OpenStackObjectStorageConfig::class,
                    'factory'         => function ($config) {
                        return new OpenStackObjectStore($config);
                    },
                ])
            );
        });
    }

    public function boot()
    {
        // add migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
