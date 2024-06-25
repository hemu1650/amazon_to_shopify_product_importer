/**
 * UI-Router and Basic App Configuration
 */
'use strict';

app
    .run(function($rootScope, $state,$stateParams,$translate,tmhDynamicLocale,Restangular) {
        // add listener for change page title and parent menu activation
        $rootScope.$state = $state;
        $rootScope.$stateParams = $stateParams;

        // translate refresh is necessary to load translate table
        $rootScope.$on('$translatePartialLoaderStructureChanged', function () {
            $translate.refresh();
        });


        $rootScope.$on('$translateChangeEnd', function() {
            // get current language
            $rootScope.currentLanguage = $translate.use();

            //dynamic load angularjs locale
            tmhDynamicLocale.set($rootScope.currentLanguage);

            // change direction to right-to-left language
            if($rootScope.currentLanguage==='ar-ae' || $rootScope.currentLanguage==='fa-ir'){
                $rootScope.currentDirection = 'rtl';
            } else{
                $rootScope.currentDirection = 'ltr';
            }

            // set lang parameter for any request that with Restangular
            Restangular.setDefaultRequestParams({lang: $rootScope.currentLanguage});
        });
    })

    .config(function($stateProvider,$urlRouterProvider,$locationProvider,$breadcrumbProvider,$authProvider,RestangularProvider,CacheFactoryProvider,$translateProvider,tmhDynamicLocaleProvider,NotificationProvider,$translatePartialLoaderProvider) {

        /**
         * Angular translate config
         */
        $translatePartialLoaderProvider.addPart('shared');
        $translateProvider
            .useSanitizeValueStrategy(null)// for prevent from XSS vulnerability but this has problem with utf-8 language
            .fallbackLanguage('en-us') //Registering a fallback language
            .registerAvailableLanguageKeys(['en-us', 'ar-ae','pt-br'], { // register your language key and browser key find
                'en_*': 'en-us',
                'ar_*': 'ar-ae',
                'pt_*': 'pt-br'
            })
            .useLoader('$translatePartialLoader', { // for lazy load we use this service
                urlTemplate: 'app/{part}/lang/locale_{lang}.json',// in this section we define our structure
                loadFailureHandler: 'MyErrorHandler'//it's a factory to error handling
            })
            .useLoaderCache(true)//use cache to loading translate file
            .useCookieStorage()// using cookie to keep current language
            //.useMissingTranslationHandlerLog() // you can remove in production
            //.determinePreferredLanguage();// define language by browser language
            .preferredLanguage('en-us');

        /* angular locale dynamic load */
        tmhDynamicLocaleProvider.localeLocationPattern('../assets/vendors/angularjs/js/i18n/angular-locale_{{locale}}.js');

        /**
         * Angular-ui-notification
         */
        NotificationProvider.setOptions({
            delay: 7000,
            startTop: 80,
            startRight: 10,
            verticalSpacing: 20,
            horizontalSpacing: 20,
            positionX: 'right',
            positionY: 'top'
        });

        /**
         * Angular-Cache basic configuration
         */
        //Cache will hold data in client memory. Data is cleared when the page is refreshed.
        angular.extend(CacheFactoryProvider.defaults, {
            maxAge: 5 * 60 * 1000, // 5 minutes
            deleteOnExpire: 'aggressive'
        });


        /**
         * Restangular API URL
         */
        RestangularProvider.setBaseUrl('../laravel-backend/public/api');
        /* force Restangular's getList to work with Laravel 5's pagination object  */
        RestangularProvider.addResponseInterceptor(parseApiResponse);
        function parseApiResponse(data, operation) {
            var response = data;
            if (operation === 'getList' && data.data) {
                response = data.data;
                response.metadata = _.omit(data, 'data');
            }
            return response;
        }


        /**
         *  ngAA Config
         */
        $authProvider.signinUrl = '../laravel-backend/public/api/authenticate';
        $authProvider.signinState = 'login';
        $authProvider.signinRoute = '/login';
        $authProvider.signinTemplateUrl ='app/shared/views/login.html';
        $authProvider.afterSigninRedirectTo = 'admin';
        $authProvider.afterSignoutRedirectTo = 'login';

        /**
         *  breadcrumb config
         */
        $breadcrumbProvider.setOptions({
            templateUrl: 'app/shared/views/ncyBreadcrumb.tpl.bs3.html',
            translations: true
        });

        /**
         * UI-Router config
         */
        // config prefix and unmatched route handler - UI-Router
        $urlRouterProvider.otherwise(function($injector){
            var $state = $injector.get("$state");
            $state.go('admin');
        });
        $stateProvider
            .state('forget-password', {
                url: '/forget',
                templateUrl: 'app/shared/views/forget.html',
                controller:'ForgetPassCtrl',
                resolve: {
                    dep: ['$ocLazyLoad',
                        function( $ocLazyLoad ){
                            return $ocLazyLoad.load(['sweet-alert','app/shared/controllers/ForgetPassCtrl.js']);
                        }]
                }
            })
            .state('reset-password', {
                url: '/forget/reset/:token',
                templateUrl: 'app/shared/views/reset.html',
                controller:'ForgetPassCtrl',
                resolve: {
                    dep: ['$ocLazyLoad',
                        function( $ocLazyLoad ){
                            return $ocLazyLoad.load(['sweet-alert','app/shared/controllers/ForgetPassCtrl.js']);
                        }]
                }
            })
            .state('admin', {
                url: '/admin',
                templateUrl: 'app/shared/views/admin.html',
                ncyBreadcrumb: {
                    label: 'app.breadcrumb.admin'// angular translate variable
                },
                data:{
                    authenticated:true
                },
                controller:'AdminCtrl',
                resolve: {
                    trans:['RequireTranslations',
                        function (RequireTranslations) {
                            RequireTranslations('shared');
                        }],
                    dep: ['trans','$ocLazyLoad',
                        function(trans,$ocLazyLoad){
                            return $ocLazyLoad.load(['ui-bs-paging']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/shared/controllers/AdminCtrl.js']);
                                }
                            );
                        }]
                }
            })
            .state('admin.dashboard',{ // define nested route with ui-router with (.) dot
                url: "/dashboard",
                templateUrl: "app/shared/views/dashboard.html",
                ncyBreadcrumb: {
                    label: 'app.breadcrumb.dashboard',// angular translate variable
                    parent:'admin'
                },
                data: {
                    permits: { //check for authenticity and permissions
                        withOnly: 'view_dashboard'
                    }
                },
                controller:'DashboardCtrl',
                resolve: {
                    dep: ['$ocLazyLoad',
                        function( $ocLazyLoad ){
                            return $ocLazyLoad.load(['morrischart','sparkline','easypiechart','momentjs','jquery-ui-custom']).then(
                                function(){
                                    return $ocLazyLoad.load(['panel-flat','fullcalendar','daterangepicker','app/shared/controllers/DashboardCtrl.js']);
                                }
                            );
                        }]
                }
            })
            .state('admin.users',{
                url: "/users",
                templateUrl: "app/modules/user/views/users.html",
                ncyBreadcrumb: {
                    label: 'app.breadcrumb.users',// angular translate variable
                    parent:'admin'
                },
                data: {
                    permits: { //check for authenticity and permissions
                        withAny: ['view_user','delete_user']
                    }
                },
                controller:'UserListCtrl',
                resolve: {
                    trans:['RequireTranslations',
                        function (RequireTranslations) {
                            RequireTranslations('modules/user');
                        }],
                    dep: ['trans','$ocLazyLoad',
                        function(trans,$ocLazyLoad){
                            return $ocLazyLoad.load(['sweet-alert','ui-bs-paging','UserServiceModule']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/user/controllers/UserListCtrl.js']);
                                }
                            );
                        }],
                    resolvedItems:['dep','UserService',
                        function(dep,UserService) {
                            return UserService.cachedList().then(function(data){
                                return data;
                            });
                        }]

                }
            })
            .state('admin.createUser',{
                url: "/users/new",
                templateUrl: "app/modules/user/views/userform.html",
                ncyBreadcrumb: {
                    label: 'app.breadcrumb.newUser',// angular translate variable
                    parent:'admin.users'
                },
                data: {
                    permits: { //check for authenticity and permissions
                        withOnly: 'add_user'
                    }
                },
                controller:'UserCreateCtrl',
                resolve: {
                    trans:['RequireTranslations',
                        function (RequireTranslations) {
                            RequireTranslations('modules/user');
                        }],
                    dep: ['trans','$ocLazyLoad',
                        function(trans,$ocLazyLoad){
                            return $ocLazyLoad.load(['dropzone','RoleServiceModule','UserServiceModule']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/user/controllers/UserCreateCtrl.js']);
                                }
                            );
                        }],
                    resolvedItems:['dep','RoleService',
                        function(dep,RoleService) {
                            return RoleService.list().then(function(data){
                                return data;
                            });
                        }]
                }
            })
            .state('admin.editUser',{
                url: "/users/:id/edit",
                templateUrl: "app/modules/user/views/userform.html",
                ncyBreadcrumb: {
                    label: 'app.breadcrumb.editUser',// angular translate variable
                    parent:'admin.users'
                },
                data: {
                    permits: {
                        withOnly: 'edit_user'
                    }
                },
                controller:'UserEditCtrl',
                resolve: {
                    trans:['RequireTranslations',
                        function (RequireTranslations) {
                            RequireTranslations('modules/user');
                        }],
                    dep: ['trans','$ocLazyLoad',
                        function(trans,$ocLazyLoad){
                            return $ocLazyLoad.load(['dropzone','RoleServiceModule','UserServiceModule']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/user/controllers/UserEditCtrl.js']);
                                }
                            );
                        }],
                    resolvedItems:['dep','UserService','$stateParams',
                        function(dep,UserService,$stateParams) {
                            return UserService.cachedShow($stateParams.id).then(function(data) {
                                return data;
                            });
                        }]
                }
            })
            .state('admin.importUser',{
                url: "/users/import",
                templateUrl: "app/modules/user/views/userimport.html",
                ncyBreadcrumb: {
                    label: 'app.breadcrumb.import',// angular translate variable
                    parent:'admin.users'
                },
                data: {
                    permits: {
                        withOnly: 'import_user'
                    }
                },
                controller:'UserImportCtrl',
                resolve: {
                    trans:['RequireTranslations',
                        function (RequireTranslations) {
                            RequireTranslations('modules/user');
                        }],
                    dep: ['trans','$ocLazyLoad',
                        function(trans,$ocLazyLoad){
                            return $ocLazyLoad.load(['dropzone','sweet-alert','RoleServiceModule','UserServiceModule','angular-wizard']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/user/controllers/UserImportCtrl.js']);
                                }
                            );
                        }]
                }
            })
            .state('admin.roles',{
                url: "/roles",
                templateUrl: "app/modules/role/views/roles.html",
                ncyBreadcrumb: {
                    label: 'app.breadcrumb.roles',// angular translate variable
                    parent:'admin'
                },
                data: {
                    permits: { //check for authenticity and permissions
                        withAny: ['view_role','delete_role']
                    }
                },
                controller:'RoleListCtrl',
                resolve: {
                    trans:['RequireTranslations',
                        function (RequireTranslations) {
                            RequireTranslations('modules/role');
                        }],
                    dep: ['trans','$ocLazyLoad',
                        function(trans,$ocLazyLoad){
                            return $ocLazyLoad.load(['sweet-alert','ui-bs-paging','RoleServiceModule']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/role/controllers/RoleListCtrl.js']);
                                }
                            );
                        }],
                    resolvedItems:['dep','RoleService',
                        function(dep,RoleService) {
                            return RoleService.cachedList().then(function(data){
                                return data;
                            });
                        }]
                }
            })
            .state('admin.createRole',{
                url: "/roles/new",
                templateUrl: "app/modules/role/views/roleform.html",
                ncyBreadcrumb: {
                    label: 'app.breadcrumb.newRole',// angular translate variable
                    parent:'admin.roles'
                },
                data: {
                    permits: {
                        withOnly: 'add_role'
                    }
                },
                controller:'RoleCreateCtrl',
                resolve: {
                    trans:['RequireTranslations',
                        function (RequireTranslations) {
                            RequireTranslations('modules/role');
                        }],
                    dep: ['trans','$ocLazyLoad',
                        function(trans,$ocLazyLoad){
                            return $ocLazyLoad.load(['PermissionServiceModule','RoleServiceModule']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/role/controllers/RoleCreateCtrl.js']);
                                }
                            );
                        }],
                    resolvedItems:['dep','PermissionService',
                        function(dep,PermissionService) {
                            return PermissionService.all().then(function(data){
                                return data;
                            });
                        }]
                }
            })
            .state('admin.editRole',{
                url: "/roles/:id/edit",
                templateUrl: "app/modules/role/views/roleform.html",
                ncyBreadcrumb: {
                    label: 'app.breadcrumb.editRole',// angular translate variable
                    parent:'admin.roles'
                },
                data: {
                    permits: {
                        withOnly: 'edit_role'
                    }
                },
                controller:'RoleEditCtrl',
                resolve: {
                    trans:['RequireTranslations',
                        function (RequireTranslations) {
                            RequireTranslations('modules/role');
                        }],
                    dep: ['trans','$ocLazyLoad',
                        function(trans,$ocLazyLoad){
                            return $ocLazyLoad.load(['RoleServiceModule']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/role/controllers/RoleEditCtrl.js']);
                                }
                            );
                        }],
                    resolvedItems:['dep','RoleService','$stateParams',
                        function(dep,RoleService,$stateParams) {
                            return RoleService.cachedShow($stateParams.id).then(function(data) {
                                return data;
                            });
                        }]
                }
            })
            .state('admin.permissions',{
                url: "/permissions",
                templateUrl: "app/modules/permission/views/permissions.html",
                ncyBreadcrumb: {
                    label: 'app.breadcrumb.permissions',// angular translate variable
                    parent:'admin'
                },
                data: {
                    permits: {
                        withAny: ['view_permission','delete_permission']
                    }
                },
                controller:'PermissionListCtrl',
                resolve: {
                    trans:['RequireTranslations',
                        function (RequireTranslations) {
                            RequireTranslations('modules/permission');
                        }],
                    dep: ['trans','$ocLazyLoad',
                        function(trans,$ocLazyLoad){
                            return $ocLazyLoad.load(['ui-bs-paging','PermissionServiceModule']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/permission/controllers/PermissionListCtrl.js']);
                                }
                            );
                        }],
                    resolvedItems:['dep','PermissionService',
                        function(dep,PermissionService) {
                            return PermissionService.cachedList().then(function(data){
                                return data;
                            });
                        }]
                }
            })
            .state('admin.createPermission',{
                url: "/permissions/new",
                templateUrl: "app/modules/permission/views/permissionform.html",
                ncyBreadcrumb: {
                    label: 'app.breadcrumb.newPermission',// angular translate variable
                    parent:'admin.permissions'
                },
                data: {
                    permits: {
                        withOnly: 'add_permission'
                    }
                },
                controller:'PermissionCreateCtrl',
                resolve: {
                    trans:['RequireTranslations',
                        function (RequireTranslations) {
                            RequireTranslations('modules/permission');
                        }],
                    dep: ['trans','$ocLazyLoad',
                        function(trans,$ocLazyLoad){
                            return $ocLazyLoad.load(['sweet-alert','ui-bs-paging','PermissionServiceModule']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/permission/controllers/PermissionCreateCtrl.js']);
                                }
                            );
                        }]
                }
            })
            .state('admin.tasks',{
                url: "/tasks",
                templateUrl: "app/modules/task/views/tasks.html",
                ncyBreadcrumb: {
                    label: 'app.breadcrumb.tasks',// angular translate variable
                    parent:'admin'
                },
                data: {
                    permits: {
                        withAny: ['view_task','delete_task']
                    }
                },
                controller:'TaskListCtrl',
                resolve: {
                    trans:['RequireTranslations',
                        function (RequireTranslations) {
                            RequireTranslations('modules/task');
                        }],
                    dep: ['trans','$ocLazyLoad',
                        function(trans,$ocLazyLoad){
                            return $ocLazyLoad.load(['sweet-alert','ui-bs-paging','TaskServiceModule']).then(
                                function(){
                                    return $ocLazyLoad.load('app/modules/task/controllers/TaskListCtrl.js');
                                }
                            );
                        }],
                    resolvedItems:['dep','TaskService',
                        function(dep,TaskService) {
                            return TaskService.cachedList().then(function(data){
                                return data;
                            });
                        }]
                }
            })
            .state('admin.createTask',{
                url: "/tasks/new",
                templateUrl: "app/modules/task/views/taskform.html",
                ncyBreadcrumb: {
                    label: 'app.breadcrumb.newTask',// angular translate variable
                    parent:'admin.tasks'
                },
                data: {
                    permits: {
                        withOnly: 'add_task'
                    }
                },
                controller:'TaskCreateCtrl',
                resolve: {
                    trans:['RequireTranslations',
                        function (RequireTranslations) {
                            RequireTranslations('modules/task');
                        }],
                    dep: ['trans','$ocLazyLoad',
                        function(trans,$ocLazyLoad){
                            return $ocLazyLoad.load(['summernote','select2','TagServiceModule','CategoryServiceModule','UserServiceModule','TaskServiceModule','ui-select-filter','dropzone','momentjs']).then(
                                function(){
                                    return $ocLazyLoad.load(['angular-daterangepicker','app/modules/task/controllers/TaskCreateCtrl.js']);
                                }
                            );
                        }]
                }
            })
            .state('admin.editTask',{
                url: "/tasks/:id/edit",
                templateUrl: "app/modules/task/views/taskform.html",
                ncyBreadcrumb: {
                    label: 'app.breadcrumb.editTask',// angular translate variable
                    parent:'admin.tasks'
                },
                data: {
                    permits: {
                        withOnly: 'edit_task'
                    }
                },
                controller:'TaskEditCtrl',
                resolve: {
                    trans:['RequireTranslations',
                        function (RequireTranslations) {
                            RequireTranslations('modules/task');
                        }],
                    dep: ['trans','$ocLazyLoad',
                        function(trans,$ocLazyLoad){
                            return $ocLazyLoad.load(['summernote','select2','TagServiceModule','CategoryServiceModule','UserServiceModule','TaskServiceModule','ui-select-filter','dropzone','momentjs']).then(
                                function(){
                                    return $ocLazyLoad.load(['angular-daterangepicker','app/modules/task/controllers/TaskEditCtrl.js']);
                                }
                            );
                        }],
                    resolvedItems:['dep','TaskService','$stateParams',
                        function(dep,TaskService,$stateParams) {
                            return TaskService.cachedShow($stateParams.id).then(function(data) {
                                return data;
                            });
                        }]
                }
            })
            .state('admin.viewTask',{
                url: "/tasks/:id/view",
                templateUrl: "app/modules/task/views/taskview.html",
                ncyBreadcrumb: {
                    label: 'app.breadcrumb.taskView',// angular translate variable
                    parent:'admin.tasks'
                },
                data: {
                    permits: {
                        withOnly: 'view_task'
                    }
                },
                controller:'TaskViewCtrl',
                resolve: {
                    trans:['RequireTranslations',
                        function (RequireTranslations) {
                            RequireTranslations('modules/task');
                        }],
                    dep: ['trans','$ocLazyLoad',
                        function(trans,$ocLazyLoad){
                            return $ocLazyLoad.load(['summernote','select2','TagServiceModule','CategoryServiceModule','UserServiceModule','TaskServiceModule','ui-select-filter','dropzone','momentjs','fancybox-plus','CommentServiceModule']).then(
                                function(){
                                    return $ocLazyLoad.load(['angular-daterangepicker','app/modules/task/controllers/TaskViewCtrl.js']);
                                }
                            );
                        }],
                    resolvedItems:['dep','TaskService','$stateParams',
                        function(dep,TaskService,$stateParams) {
                            return TaskService.cachedShow($stateParams.id).then(function(data) {
                                return data;
                            });
                        }]
                }
            })

            .state('admin.categories',{
                url: "/categories",
                templateUrl: "app/modules/category/views/categories.html",
                ncyBreadcrumb: {
                    label: 'app.breadcrumb.categories',// angular translate variable
                    parent:'admin'
                },
                data: {
                    permits: {
                        withAny: ['view_category','delete_category','add_category','edit_category']
                    }
                },
                controller:'CategoryCtrl',
                resolve: {
                    trans:['RequireTranslations',
                        function (RequireTranslations) {
                            RequireTranslations('modules/category');
                        }],
                    dep: ['trans','$ocLazyLoad',
                        function(trans,$ocLazyLoad){
                            return $ocLazyLoad.load(['summernote','sweet-alert','CategoryServiceModule']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/category/controllers/CategoryCtrl.js']);
                                }
                            );
                        }],
                    resolvedItems:['dep','CategoryService',
                        function(dep,CategoryService) {
                            return CategoryService.cachedList().then(function(data){
                                return data;
                            });
                        }]
                }
            })


            /***************************************************
             * This is sample and you can use it for your module
             ***************************************************/
            //.state('admin.samples',{
            //    url: "/samples",
            //    templateUrl: "app/modules/sample/views/samples.html",
            //    ncyBreadcrumb: {
            //        label: 'Samples',
            //        parent:'admin'
            //    },
            //    data: {
            //        permits: { // this permissions not define in back-end model
            //            withAny: ['view_sample','delete_sample']
            //        }
            //    },
            //    controller:'SampleListCtrl',
            //    resolve: {
            //        trans:['RequireTranslations',
            //            function (RequireTranslations) {
            //                RequireTranslations('modules/sample');
            //            }],
            //        dep: ['trans','$ocLazyLoad',
            //            function(trans, $ocLazyLoad ){
            //                return $ocLazyLoad.load(['sweet-alert','ui-bs-paging','SampleServiceModule']).then(
            //                    function(){
            //                        return $ocLazyLoad.load(['app/modules/customer/controllers/SampleListCtrl.js']);
            //                    }
            //                );
            //            }]
            //    }
            //})
            //.state('admin.createSample',{
            //    url: "/samples/new",
            //    templateUrl: "app/modules/sample/views/sampleform.html",
            //    ncyBreadcrumb: {
            //        label: 'New Sample',
            //        parent:'admin.samples'
            //    },
            //    data: {
            //        permits: { // this permissions not define in back-end model
            //            withOnly: 'add_sample'
            //        }
            //    },
            //    controller:'SampleCreateCtrl',
            //    resolve: {
            //        trans:['RequireTranslations',
            //            function (RequireTranslations) {
            //                RequireTranslations('modules/sample');
            //            }],
            //        dep: ['trans','$ocLazyLoad',
            //            function(trans, $ocLazyLoad ){
            //                return $ocLazyLoad.load(['dropzone','jasny-bootstrap','SampleServiceModule']).then(
            //                    function(){
            //                        return $ocLazyLoad.load(['app/modules/sample/controllers/SampleCreateCtrl.js']);
            //                    }
            //                );
            //            }]
            //    }
            //})
            //.state('admin.viewSample',{
            //    url: "/samples/:id/view",
            //    templateUrl: "app/modules/sample/views/sampleform.html",
            //    ncyBreadcrumb: {
            //        label: 'Show Sample',
            //        parent:'admin.samples'
            //    },
            //    data: {
            //        permits: { // this permissions not define in back-end model
            //            withOnly: 'view_sample'
            //        }
            //    },
            //    controller:'SampleViewCtrl',
            //    resolve: {
            //        trans:['RequireTranslations',
            //            function (RequireTranslations) {
            //                RequireTranslations('modules/sample');
            //            }],
            //        dep: ['trans','$ocLazyLoad',
            //            function(trans, $ocLazyLoad ){
            //                return $ocLazyLoad.load(['SampleServiceModule']).then(
            //                    function(){
            //                        return $ocLazyLoad.load(['app/modules/sample/controllers/SampleViewCtrl.js'])
            //                    }
            //                )
            //            }]
            //    }
            //})
            //.state('admin.editSample',{
            //    url: "/samples/:id/edit",
            //    templateUrl: "app/modules/sample/views/sampleform.html",
            //    ncyBreadcrumb: {
            //        label: 'Edit Sample',
            //        parent:'admin.samples'
            //    },
            //    data: {
            //        permits: { // this permissions not define in back-end model
            //            withOnly: 'edit_sample'
            //        }
            //    },
            //    controller:'SampleEditCtrl',
            //    resolve: {
            //        trans:['RequireTranslations',
            //            function (RequireTranslations) {
            //                RequireTranslations('modules/sample');
            //            }],
            //        dep: ['trans','$ocLazyLoad',
            //            function(trans, $ocLazyLoad ){
            //                return $ocLazyLoad.load(['SampleServiceModule']).then(
            //                    function(){
            //                        return $ocLazyLoad.load(['app/modules/sample/controllers/SampleEditCtrl.js'])
            //                    }
            //                )
            //            }]
            //    }
            //})


            /***************************************************
             * This is admin template ui-route
             * You should remove this route in real project
             ***************************************************/
            .state('admin.ui-elements', {
                template: '<div ui-view class="fade-in-up"></div>',
                ncyBreadcrumb: {
                    label: 'UI & Elements'
                }
            })
            .state('admin.generalelements', {
                url: "/ui-elements/generalelements",
                templateUrl: "app/modules/adminTemplate/views/generalelements.html",
                ncyBreadcrumb: {
                    label: 'General Elements',
                    parent:'admin.ui-elements'
                },
                controller:'generalelementsCtrl',
                resolve: {
                    dep: ['$ocLazyLoad',
                        function( $ocLazyLoad ){
                            return $ocLazyLoad.load('bootbox').then(
                                function(){
                                    return $ocLazyLoad.load('app/modules/adminTemplate/controllers/generalelementsCtrl.js');
                                }
                            );
                        }]
                }
            })
            .state('admin.typography', {
                url: "/ui-elements/typography",
                templateUrl: "app/modules/adminTemplate/views/typography.html",
                ncyBreadcrumb: {
                    label: 'Typography',
                    parent:'admin.ui-elements'
                },
                resolve: {
                    dep: ['$ocLazyLoad',
                        function( $ocLazyLoad ){
                            return $ocLazyLoad.load('bs-example');
                        }
                    ]
                }

            })
            .state('admin.tab', {
                url: "/ui-elements/tab",
                templateUrl: "app/modules/adminTemplate/views/tab.html",
                ncyBreadcrumb: {
                    label: 'Tab & Accordian',
                    parent:'admin.ui-elements'
                }
            })
            .state('admin.treeview', {
                url: "/ui-elements/treeview",
                templateUrl: "app/modules/adminTemplate/views/treeview.html",
                ncyBreadcrumb: {
                    label: 'Treeview',
                    parent:'admin.ui-elements'
                },
                controller:'treeviewCtrl',
                resolve: {
                    dep: ['$ocLazyLoad',
                        function( $ocLazyLoad ){
                            return $ocLazyLoad.load(['jstree']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/adminTemplate/controllers/treeviewCtrl.js']);
                                }
                            );
                        }]
                }
            })
            .state('admin.buttons', {
                url: "/ui-elements/buttons",
                templateUrl: "app/modules/adminTemplate/views/buttons.html",
                ncyBreadcrumb: {
                    label: 'Buttons',
                    parent:'admin.ui-elements'
                },
                controller:'buttonsCtrl',
                resolve: {
                    dep: ['$ocLazyLoad',
                        function( $ocLazyLoad ){
                            return $ocLazyLoad.load(['ladda','bootstrap-iconpicker']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/vendors/angular-ladda/angular-ladda.min.js','app/modules/adminTemplate/controllers/buttonsCtrl.js']);
                                }
                            );
                        }]
                }
            })
            .state('admin.jquery-ui', {
                url: "/ui-elements/jquery-ui",
                templateUrl: "app/modules/adminTemplate/views/jquery-ui.html",
                ncyBreadcrumb: {
                    label: 'jQuery UI',
                    parent:'admin.ui-elements'
                },
                controller:'jquery-uiCtrl',
                resolve: {
                    dep: ['$ocLazyLoad',
                        function( $ocLazyLoad ){
                            return $ocLazyLoad.load(['jquery-ui']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/adminTemplate/controllers/jquery-uiCtrl.js']);
                                }
                            );
                        }]
                }
            })
            .state('admin.nestable-list', {
                url: "/ui-elements/nestable-list",
                templateUrl: "app/modules/adminTemplate/views/nestable-list.html",
                ncyBreadcrumb: {
                    label: 'Nestable List',
                    parent:'admin.ui-elements'
                },
                controller:'nestable-listCtrl',
                resolve: {
                    dep: ['$ocLazyLoad',
                        function( $ocLazyLoad ){
                            return $ocLazyLoad.load(['nestable']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/adminTemplate/controllers/nestable-listCtrl.js']);
                                }
                            );
                        }]
                }
            })
            .state('admin.dual-list', {
                url: "/ui-elements/dual-list",
                templateUrl: "app/modules/adminTemplate/views/dual-list.html",
                ncyBreadcrumb: {
                    label: 'Dual List',
                    parent:'admin.ui-elements'
                },
                controller:'dual-listCtrl',
                resolve: {
                    dep: ['$ocLazyLoad',
                        function( $ocLazyLoad ){
                            return $ocLazyLoad.load(['dual-list']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/adminTemplate/controllers/dual-listCtrl.js']);
                                }
                            );
                        }]
                }
            })
            .state('admin.image-crop', {
                url: "/ui-elements/image-crop",
                templateUrl: "app/modules/adminTemplate/views/image-crop.html",
                ncyBreadcrumb: {
                    label: 'Image Crop',
                    parent:'admin.ui-elements'
                },
                controller:'image-cropCtrl',
                resolve: {
                    dep: ['$ocLazyLoad',
                        function( $ocLazyLoad ){
                            return $ocLazyLoad.load(['jcrop']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/adminTemplate/controllers/image-cropCtrl.js']);
                                }
                            );
                        }]
                }
            })
            .state('admin.table', {
                template: '<div ui-view class="fade-in-up"></div>',
                ncyBreadcrumb: {
                    label: 'Table'
                }
            })
            .state('admin.tables', {
                url: "/table/tables",
                templateUrl: "app/modules/adminTemplate/views/tables.html",
                ncyBreadcrumb: {
                    label: 'Simple Tables',
                    parent:'admin.table'
                }
            })
            .state('admin.datatables', {
                url: "/table/datatables",
                templateUrl: "app/modules/adminTemplate/views/datatables.html",
                ncyBreadcrumb: {
                    label: 'Data Tables',
                    parent:'admin.table'
                },
                controller:'datatablesCtrl',
                resolve: {
                    dep: ['$ocLazyLoad',
                        function( $ocLazyLoad ){
                            return $ocLazyLoad.load(['datatable']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/adminTemplate/controllers/datatablesCtrl.js']);
                                }
                            );
                        }]
                }
            })
            .state('admin.charts', {
                template: '<div ui-view class="fade-in-up"></div>',
                ncyBreadcrumb: {
                    label: 'Charts'
                }
            })
            .state('admin.flotchart',{
                url: "/chart/flotchart",
                templateUrl: "app/modules/adminTemplate/views/flotchart.html",
                ncyBreadcrumb: {
                    label: 'Flot Charts',
                    parent:'admin.charts'
                },
                controller:'flotchartCtrl',
                resolve: {
                    dep: ['$ocLazyLoad',
                        function( $ocLazyLoad ){
                            return $ocLazyLoad.load(['flot']).then(
                                function(){
                                    return $ocLazyLoad.load(['flot-plugins','app/modules/adminTemplate/controllers/flotchartCtrl.js'])
                                }
                            );

                        }]
                }
            })
            .state('admin.morrischart', {
                url: "/chart/morrischart",
                templateUrl: "app/modules/adminTemplate/views/morrischart.html",
                ncyBreadcrumb: {
                    label: 'Morris Charts',
                    parent:'admin.charts'
                },
                controller:'morrischartCtrl',
                resolve: {
                    dep: ['$ocLazyLoad',
                        function( $ocLazyLoad ){
                            return $ocLazyLoad.load(['morrischart']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/adminTemplate/controllers/morrischartCtrl.js']);
                                }
                            );
                        }]
                }
            })
            .state('admin.jquery-sparklines', {
                url: "/chart/jquery-sparklines",
                templateUrl: "app/modules/adminTemplate/views/jquery-sparklines.html",
                ncyBreadcrumb: {
                    label: 'jQuery Sparklines Charts',
                    parent:'admin.charts'
                },
                controller:'jquery-sparklinesCtrl',
                resolve: {
                    dep: ['$ocLazyLoad',
                        function( $ocLazyLoad ){
                            return $ocLazyLoad.load(['sparkline']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/adminTemplate/controllers/jquery-sparklinesCtrl.js']);
                                }
                            );
                        }]
                }
            })
            .state('admin.forms', {
                template: '<div ui-view class="fade-in-up"></div>',
                ncyBreadcrumb: {
                    label: 'Forms'
                }
            })
            .state('admin.form-elements', {
                url: "/forms/form-elements",
                templateUrl: "app/modules/adminTemplate/views/form-elements.html",
                ncyBreadcrumb: {
                    label: 'Form Elements',
                    parent:'admin.forms'
                }
            })
            .state('admin.form-validations',{
                url: "/forms/form-validations",
                templateUrl: "app/modules/adminTemplate/views/form-validations.html",
                ncyBreadcrumb: {
                    label: 'Form Validations',
                    parent:'admin.forms'
                },
                controller:'form-validationsCtrl',
                resolve: {
                    dep: ['$ocLazyLoad',
                        function( $ocLazyLoad ){
                            return $ocLazyLoad.load(['sweet-alert']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/adminTemplate/controllers/form-validationsCtrl.js'])
                                }
                            );

                        }]
                }
            })
            .state('admin.wizard-validation',{
                url: "/forms/wizard-validation",
                templateUrl: "app/modules/adminTemplate/views/wizard-validation.html",
                ncyBreadcrumb: {
                    label: 'Wizard & Validation',
                    parent:'admin.forms'
                },
                controller:'wizard-validationCtrl',
                resolve: {
                    dep: ['$ocLazyLoad',
                        function( $ocLazyLoad ){
                            return $ocLazyLoad.load(['fuelux','jquery.steps','jquery.validate','sweet-alert']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/adminTemplate/controllers/wizard-validationCtrl.js'])
                                }
                            );

                        }]
                }
            })
            .state('admin.wysiwyg-editor',{
                url: "/forms/wysiwyg-editor",
                templateUrl: "app/modules/adminTemplate/views/wysiwyg-editor.html",
                ncyBreadcrumb: {
                    label: 'WYSIWYG Editor',
                    parent:'admin.forms'
                },
                controller:'wysiwyg-editorCtrl',
                resolve: {
                    dep: ['$ocLazyLoad',
                        function( $ocLazyLoad ){
                            return $ocLazyLoad.load(['summernote','markdown','ckeditor','tinymce']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/adminTemplate/controllers/wysiwyg-editorCtrl.js']);
                                }
                            );
                        }]
                }
            })
            .state('admin.form-plugins',{
                url: "/forms/form-plugins",
                templateUrl: "app/modules/adminTemplate/views/form-plugins.html",
                ncyBreadcrumb: {
                    label: 'Form Plugins',
                    parent:'admin.forms'
                },
                controller:'form-pluginsCtrl',
                resolve: {
                    dep: ['$ocLazyLoad',
                        function( $ocLazyLoad ){
                            return $ocLazyLoad.load(['x-editable','select2','ui-bootstrap','momentx-edit','bootstrap-datepicker','jasny-bootstrap','knob','bootstrap-tagsinput','bootstrap-timepicker','clockpicker','bootstrap-colorpicker','checklist']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/adminTemplate/controllers/form-pluginsCtrl.js']);
                                }
                            );
                        }]
                }
            })
            .state('admin.file-uploader', {
                template: '<div ui-view class="fade-in-up"></div>',
                ncyBreadcrumb: {
                    label: 'File Uploader'
                }
            })
            .state('admin.dropzone-file',{
                url: "/file-uploader/dropzone-file",
                templateUrl: "app/modules/adminTemplate/views/dropzone-file.html",
                ncyBreadcrumb: {
                    label: 'Dropzone File',
                    parent:'admin.file-uploader'
                },
                controller:'dropzone-fileCtrl',
                resolve: {
                    dep: ['$ocLazyLoad',
                        function( $ocLazyLoad ){
                            return $ocLazyLoad.load(['dropzone']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/adminTemplate/controllers/dropzone-fileCtrl.js']);
                                }
                            );
                        }]
                }
            })
            .state('admin.multiple-file-upload',{
                url: "/file-uploader/multiple-file-upload",
                templateUrl: "app/modules/adminTemplate/views/multiple-file-upload.html",
                ncyBreadcrumb: {
                    label: 'Multiple File Upload',
                    parent:'admin.file-uploader'
                },
                controller:'multipleFileUploadCtrl',
                resolve: {
                    dep: ['$ocLazyLoad',
                        function( $ocLazyLoad ){
                            return $ocLazyLoad.load(['jquery-fileupload']).then(
                                function(){
                                    return $ocLazyLoad.load(['jquery-fileupload-feature']).then(
                                        function(){
                                            return $ocLazyLoad.load(['app/modules/adminTemplate/controllers/multipleFileUploadCtrl.js'])
                                        }
                                    );
                                }
                            );
                        }]
                }
            })
            .state('admin.maps', {
                template: '<div ui-view class="fade-in-up"></div>',
                ncyBreadcrumb: {
                    label: 'Maps'
                }
            })
            .state('admin.vector-maps',{
                url: "/maps/vector-maps",
                templateUrl: "app/modules/adminTemplate/views/vector-maps.html",
                ncyBreadcrumb: {
                    label: 'Vector Maps',
                    parent:'admin.maps'
                },
                controller:'vector-mapsCtrl',
                resolve: {
                    dep: ['$ocLazyLoad',
                        function( $ocLazyLoad ){
                            return $ocLazyLoad.load(['vectormap']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/adminTemplate/controllers/vector-mapsCtrl.js']);
                                }
                            );
                        }]
                }
            })
            .state('admin.google-map',{
                url: "/maps/google-map",
                templateUrl: "app/modules/adminTemplate/views/google-map.html",
                ncyBreadcrumb: {
                    label: 'Google Maps',
                    parent:'admin.maps'
                },
                controller:'google-mapCtrl',
                resolve: {
                    dep: ['$ocLazyLoad',
                        function( $ocLazyLoad ){
                            return $ocLazyLoad.load(['google-map']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/adminTemplate/controllers/google-mapCtrl.js']);
                                }
                            );
                        }]
                }
            })
            .state('admin.mailbox',{
                url: "/mailbox",
                templateUrl: "app/modules/adminTemplate/views/mailbox.html",
                ncyBreadcrumb: {
                    label: 'Mailbox'
                },
                controller:'mailboxCtrl',
                resolve: {
                    dep: ['$ocLazyLoad',
                        function( $ocLazyLoad ){
                            return $ocLazyLoad.load(['summernote','jasny-bootstrap']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/adminTemplate/controllers/mailboxCtrl.js']);
                                }
                            );
                        }]
                }
            })
            .state('admin.gallery',{
                url: "/gallery",
                templateUrl: "app/modules/adminTemplate/views/gallery.html",
                ncyBreadcrumb: {
                    label: 'Gallery'
                },
                controller:'galleryCtrl',
                resolve: {
                    dep: ['$ocLazyLoad',
                        function( $ocLazyLoad ){
                            return $ocLazyLoad.load(['blueimp-gallery','yep-gallery']).then(
                                function(){
                                    return $ocLazyLoad.load(['bootstrap-image-gallery','app/modules/adminTemplate/controllers/galleryCtrl.js']);
                                }
                            );
                        }]
                }
            })
            .state('admin.calendar',{
                url: "/calendar",
                templateUrl: "app/modules/adminTemplate/views/calendar.html",
                ncyBreadcrumb: {
                    label: 'Calendar'
                },
                controller:'calendarCtrl',
                resolve: {
                    dep: ['$ocLazyLoad',
                        function( $ocLazyLoad ){
                            return $ocLazyLoad.load(['momentjs','jquery-ui-custom','bootbox']).then(
                                function(){
                                    return $ocLazyLoad.load(['fullcalendar']).then(
                                        function(){
                                            return $ocLazyLoad.load(['app/modules/adminTemplate/controllers/calendarCtrl.js'])
                                        }
                                    );
                                }
                            );
                        }]
                }
            })
            .state('admin.more-pages', {
                template: '<div ui-view class="fade-in-up"></div>',
                ncyBreadcrumb: {
                    label: 'More Pages'
                }
            })
            .state('admin.timeline', {
                url: "/more-page/timeline",
                templateUrl: "app/modules/adminTemplate/views/timeline.html",
                ncyBreadcrumb: {
                    label: 'Timeline',
                    parent:'admin.more-pages'
                }
            })
            .state('admin.profile', {
                url: "/more-page/profile",
                templateUrl: "app/modules/adminTemplate/views/profile.html",
                ncyBreadcrumb: {
                    label: 'Profile',
                    parent:'admin.more-pages'
                },
                controller:'profileCtrl',
                resolve: {
                    dep: ['$ocLazyLoad',
                        function( $ocLazyLoad ){
                            return $ocLazyLoad.load(['panel-flat']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/adminTemplate/controllers/profileCtrl.js']);
                                }
                            );
                        }]
                }
            })
            .state('admin.invoice', {
                url: "/more-page/invoice",
                templateUrl: "app/modules/adminTemplate/views/invoice.html",
                ncyBreadcrumb: {
                    label: 'Invoice',
                    parent:'admin.more-pages'
                }
            })
            .state('admin.search-page',{
                url: "/more-page/search-page",
                templateUrl: "app/modules/adminTemplate/views/search-page.html",
                ncyBreadcrumb: {
                    label: 'Search Page',
                    parent:'admin.more-pages'
                },
                controller:'search-pageCtrl',
                resolve: {
                    dep: ['$ocLazyLoad',
                        function( $ocLazyLoad ){
                            return $ocLazyLoad.load(['sparkline','easypiechart']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/adminTemplate/controllers/search-pageCtrl.js']);
                                }
                            );
                        }]
                }
            })
            .state('admin.blank', {
                url: "/more-page/blank",
                templateUrl: "app/modules/adminTemplate/views/blank.html",
                ncyBreadcrumb: {
                    label: 'Blank',
                    parent:'admin.more-pages'
                }
            });

    }
);

