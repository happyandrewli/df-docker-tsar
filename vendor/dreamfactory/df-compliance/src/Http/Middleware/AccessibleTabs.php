<?php

namespace DreamFactory\Core\Compliance\Http\Middleware;

use Closure;
use DreamFactory\Core\Compliance\Components\RestrictedAdmin;
use DreamFactory\Core\Compliance\Utility\LicenseCheck;
use DreamFactory\Core\Compliance\Utility\MiddlewareHelper;
use DreamFactory\Core\Enums\Verbs;

class AccessibleTabs
{

    protected $request;

    /**
     * @param         $request
     * @param Closure $next
     *
     * @return mixed
     * @throws \Exception
     */
    function handle($request, Closure $next)
    {
        $this->request = $request;

        // Ignore Restricted admin logic for non GOLD subscription
        if (!LicenseCheck::isGoldLicense()) {
            return $next($this->request);
        }

        $response = $next($this->request);
        $method = $this->request->getMethod();

        if ($this->isGetAccessibleTabsRequest($method)) {
            $content = $this->getContentWithAccessibleTabs($response->getOriginalContent());
            $response->setContent($content);
        };

        return $response;
    }

    /**
     * @param $method
     * @return bool
     */
    private function isGetAccessibleTabsRequest($method)
    {
        return $method === Verbs::GET &&
            MiddlewareHelper::requestUrlContains($this->request, 'system/role') &&
            $this->isAccessibleTabsSpecified($this->request->only('accessible_tabs'));
    }

    /**
     * @param $rolesInfo
     * @return bool
     */
    private function getContentWithAccessibleTabs($rolesInfo)
    {
        if (isset($rolesInfo['resource'])) {
            foreach ($rolesInfo['resource'] as $key => $item) {
                $rolesInfo['resource'][$key] = $this->addAccessibleTabs($item);
            }
        } else {
            $rolesInfo = $this->addAccessibleTabs($rolesInfo);
        }
        return $rolesInfo;
    }

    /**
     * @param $options
     * @return bool
     */
    private static function isAccessibleTabsSpecified($options)
    {
        return isset($options["accessible_tabs"]) && $options["accessible_tabs"] && to_bool($options["accessible_tabs"]);
    }

    /**
     * @param $roleInfo
     * @return bool
     */
    private static function addAccessibleTabs($roleInfo)
    {
        if(!isset($roleInfo['error'])) {
            $roleInfo['accessible_tabs'] = RestrictedAdmin::getAccessibleTabsByRoleId($roleInfo["id"]);
        }

        return $roleInfo;
    }
}