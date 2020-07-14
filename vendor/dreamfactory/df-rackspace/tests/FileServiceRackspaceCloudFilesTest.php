<?php

use DreamFactory\Core\Enums\Verbs;

class FileServiceRackspaceCloudFilesTest extends \DreamFactory\Core\Testing\FileServiceTestCase
{
    protected static $staged = false;

    protected $serviceId = 'ros';

    public function stage()
    {
        parent::stage();

        Artisan::call('migrate', ['--path' => 'vendor/dreamfactory/df-rackspace/database/migrations/']);
        if (!$this->serviceExists('ros')) {
            \DreamFactory\Core\Models\Service::create(
                [
                    "name"        => "ros",
                    "label"       => "Rackspace Cloud Files service",
                    "description" => "Rackspace Cloud Files service for unit test",
                    "is_active"   => true,
                    "type"        => "rackspace_cloud_files",
                    "config"      => [
                        'username'    => env('ROS_USERNAME'),
                        'password'    => env('ROS_PASSWORD'),
                        'tenant_name' => env('ROS_TENANT_NAME'),
                        'api_key'     => env('ROS_API_KEY'),
                        'url'         => env('ROS_URL'),
                        'region'      => env('ROS_REGION'),
                        'container'   => env('ROS_CONTAINER')
                    ]
                ]
            );
        }
    }

    public function testPOSTZipFileFromUrlWithExtractAndClean()
    {
        $rs = $this->makeRequest(
            Verbs::POST,
            static::FOLDER_1 . '/f2/',
            ['url' => $this->getBaseUrl() . '/testfiles.zip', 'extract' => 'true', 'clean' => 'true']
        );
        $content = json_encode($rs->getContent(), JSON_UNESCAPED_SLASHES);

        $this->assertEquals('{"name":"' .
            static::FOLDER_1 .
            '/f2","path":"' .
            static::FOLDER_1 .
            '/f2/"}',
            $content);
        $this->makeRequest(Verbs::DELETE, static::FOLDER_1 . '/', ['force' => 1]);
    }
}
