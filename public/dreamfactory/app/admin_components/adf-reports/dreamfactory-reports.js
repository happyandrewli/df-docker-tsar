/**
 * This file is part of DreamFactory (tm)
 *
 * http://github.com/dreamfactorysoftware/dreamfactory
 * Copyright 2012-2017 DreamFactory Software, Inc. <dspsupport@dreamfactory.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
'use strict';


angular.module('dfReports', ['ngRoute', 'dfUtility', 'dfApplication', 'dfHelp'])
    .constant('MOD_REPORT_ROUTER_PATH', '/reports')
    .constant('MOD_REPORT_ASSET_PATH', 'admin_components/adf-reports/')
    .config(['$routeProvider', 'MOD_REPORT_ROUTER_PATH', 'MOD_REPORT_ASSET_PATH',
        function ($routeProvider, MOD_REPORT_ROUTER_PATH, MOD_REPORT_ASSET_PATH) {
            $routeProvider
                .when(MOD_REPORT_ROUTER_PATH, {
                    templateUrl: MOD_REPORT_ASSET_PATH + 'views/main.html',
                    controller: 'ReportsCtrl',
                    resolve: {
                        checkAdmin:['checkAdminService', function (checkAdminService) {
                            return checkAdminService.checkAdmin();
                        }],
                        checkUser:['checkUserService', function (checkUserService) {
                            return checkUserService.checkUser();
                        }]
                    }
                });
        }])

    .run([function () {

    }])

    .controller('ReportsCtrl', ['$rootScope', '$scope', 'dfApplicationData', 'dfNotify', '$location',
        function($rootScope, $scope, dfApplicationData, dfNotify, $location) {

            $scope.$parent.title = 'Reports';

            // Set module links
            $scope.links = [
                {
                    name: 'manage-service-reports',
                    label: 'Manage service reports',
                    path: 'manage-service-reports'
                },
            ];

            // Set empty search result message
            $scope.emptySearchResult = {
                title: 'You have no Reports that match your search criteria!',
                text: ''
            };

            //HELP
            $scope.dfLargeHelp = {
                manageReports: {
                    title: 'Manage Service Reports',
                    text: 'Service reports tell you when each service was created, modified, and deleted.'
                }
            };

            // load data

            $scope.apiData = null;

            $scope.loadTabData = function (init) {

                $scope.dataLoading = true;

                var apis, newApiData;

                var errorFunc = function (error) {
                    var msg = 'To use the Reports tab you must be Root Admin and have GOLD license.';

                    var messageOptions = {
                        module: 'Reports',
                        provider: 'dreamfactory',
                        type: 'error',
                        message: msg
                    };

                    $location.url('/home');
                    dfNotify.warn(messageOptions);
                };

                // first get system data to decide whether to load other data
                dfApplicationData.getApiData(['system']).then(
                    function (response) {
                        angular.forEach(response[0].resource, function (value) {
                            if (value.name === 'service_report') {
                                $scope.reportsEnabled = true;
                            }
                        });
                        if (!$scope.reportsEnabled) {
                            // reports not enabled, disable UI
                            $scope.subscription_required = true;
                        } else {
                            // reports enabled, load other data
                            var apis = ['service_report'];

                            dfApplicationData.getApiData(apis).then(
                                function (response) {
                                    var newApiData = {};
                                    apis.forEach(function (value, index) {
                                        newApiData[value] = response[index].resource ? response[index].resource : response[index];
                                    });
                                    $scope.apiData = newApiData;
                                    if (init) {
                                        $scope.$broadcast('toolbar:paginate:service_report:load');
                                    }
                                },
                                // error getting other data
                                errorFunc
                            );
                        }
                    },
                    // error getting system data
                    function (error) {
                        var messageOptions = {
                            module: 'Reports',
                            provider: 'dreamfactory',
                            type: 'error',
                            message: 'There was an error loading data for the Reports tab. Please try refreshing your browser and logging in again.'
                    };
                        $location.url('/home');
                        dfNotify.error(messageOptions);
                    }
                ).finally(function () {
                    $scope.dataLoading = false;
                });
            };

            $scope.loadTabData(true);
        }])

    .directive('dfManageServiceReports', ['$rootScope', 'MOD_REPORT_ASSET_PATH', 'dfApplicationData', 'dfNotify', '$location', function ($rootScope, MOD_REPORT_ASSET_PATH, dfApplicationData, dfNotify, $location) {

        return {
            restrict: 'E',
            scope: false,
            templateUrl: MOD_REPORT_ASSET_PATH + 'views/df-manage-service-reports.html',
            link: function (scope, elem, attrs) {

                var ManagedServiceReport = function (serviceReportData) {
                    return {
                        __dfUI: {
                            selected: false
                        },
                        record: serviceReportData
                    };
                };

                scope.serviceReports = null;

                scope.fields = [
                    {
                        name: 'id',
                        label: 'ID',
                        active: true
                    },
                    {
                        name: 'time',
                        label: 'Time',
                        active: true
                    },
                    {
                        name: 'service_id',
                        label: 'Service Id',
                        active: true
                    },
                    {
                        name: 'service_name',
                        label: 'Service Name',
                        active: true
                    },
                    {
                        name: 'user_email',
                        label: 'User Email',
                        active: true
                    },
                    {
                        name: 'action',
                        label: 'Action',
                        active: true
                    },
                    {
                        name: 'request_method',
                        label: 'Request',
                        active: true
                    },
                ];

                scope.order = {
                    orderBy: 'id',
                    orderByReverse: false
                };

                scope.selectedReports = [];

                // PUBLIC API

                scope.orderOnSelect = function (fieldObj) {

                    scope._orderOnSelect(fieldObj);
                };

                scope._orderOnSelect = function (fieldObj) {

                    var orderedBy = scope.order.orderBy;

                    if (orderedBy === fieldObj.name) {
                        scope.order.orderByReverse = !scope.order.orderByReverse;
                    } else {
                        scope.order.orderBy = fieldObj.name;
                        scope.order.orderByReverse = false;
                    }
                };

                // WATCHERS

                // this fires when the API data changes
                var watchApiData = scope.$watchCollection(function() {

                    return dfApplicationData.getApiDataFromCache('service_report');

                }, function (newValue, oldValue) {

                    var _serviceReports = [];

                    if (newValue) {
                        angular.forEach(newValue, function (serviceReport) {
                            _serviceReports.push(new ManagedServiceReport(serviceReport));
                        });
                    }

                    scope.serviceReports = _serviceReports;
                });

                // MESSAGES

                scope.$on('$destroy', function (e) {

                    // Destroy watchers
                    watchApiData();
                    // when filter is changed the controller is reloaded and we get destroy event
                    // the reset event tells pagination engine to update based on filter
                    scope.$broadcast('toolbar:paginate:service_report:reset');
                });
            }
        };
    }])

    .directive('dfReportsLoading', [function() {
      return {
        restrict: 'E',
        template: "<div class='col-lg-12' ng-if='dataLoading'><span style='display: block; width: 100%; text-align: center; color: #A0A0A0; font-size: 50px; margin-top: 100px'><i class='fa fa-refresh fa-spin'></i></div>"
      };
    }]);
