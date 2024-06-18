/**
 * UI-Router and Basic App Configuration
 */
'use strict';

app
    .run(function($rootScope,$state,$stateParams,$translate,tmhDynamicLocale,Restangular) {
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
        RestangularProvider.setBaseUrl('http://localhost/amazon_to_shopify_product_importer/aac/api/public/v1');
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
        $authProvider.signinUrl = 'http://localhost/amazon_to_shopify_product_importer/aac/api/public/v1/authenticate';
        $authProvider.signinState = 'login';
        $authProvider.signinRoute = '/login/:key';
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
                            return $ocLazyLoad.load(['ui-bs-paging','ProductServiceModule']).then(
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
                }              
            })
			.state('admin.faq',{ // define nested route with ui-router with (.) dot
                url: "/faq",
                templateUrl: "app/shared/views/faq.html",
                ncyBreadcrumb: {
                    label: 'app.breadcrumb.faq',// angular translate variable
                    parent:'admin'
                }              
            })
			.state('admin.products',{
                url: "/products",
                templateUrl: "app/modules/product/views/products.html",
                ncyBreadcrumb: {
                    label: 'app.breadcrumb.products',// angular translate variable
                    parent:'admin'
                },
                
                controller:'ProductListCtrl',
                resolve: {
                    trans:['RequireTranslations',
                        function (RequireTranslations) {
                            RequireTranslations('modules/product');
                        }],
                    dep: ['trans','$ocLazyLoad',
                        function(trans,$ocLazyLoad){
                            return $ocLazyLoad.load(['sweet-alert','select2','ui-bs-paging','ui-select-filter','ReviewServiceModule','CategoryServiceModule','ProductServiceModule','datatable']).then(
                                function(){
                                    
                                    return $ocLazyLoad.load('app/modules/product/controllers/ProductListCtrl.js');
                                    
                                }
                            );
                        }],
                    resolvedItems:['dep','ProductService',
                        function(dep,ProductService) {
                            return ProductService.cachedList().then(function(data){
                                return data;
                            });
                        }]
                }
            })
			.state('admin.incompleteProducts',{
                url: "/incompleteProducts",
                templateUrl: "app/modules/product/views/incompleteProducts.html",
                ncyBreadcrumb: {
                    label: 'app.breadcrumb.incompleteProducts',// angular translate variable
                    parent:'admin'
                },
                
                controller:'ProductListCtrl',
                resolve: {
                    trans:['RequireTranslations',
                        function (RequireTranslations) {
                            RequireTranslations('modules/product');
                        }],
                    dep: ['trans','$ocLazyLoad',
                        function(trans,$ocLazyLoad){
                            return $ocLazyLoad.load(['sweet-alert','select2','ui-bs-paging','ui-select-filter','ReviewServiceModule','CategoryServiceModule','ProductServiceModule','datatable']).then(
                                function(){
                                    
                                    return $ocLazyLoad.load('app/modules/product/controllers/ProductListCtrl.js');
                                    
                                }
                            );
                        }],
                    resolvedItems:['dep','ProductService',
                        function(dep,ProductService) {
                            return ProductService.cachedList().then(function(data){
                                return data;
                            });
                        }]
                }
            })
            // .state('admin.reviews',{
            //     url: "/reviews/:id",
            //     templateUrl: "app/modules/reviews/views/reviewList.html",
            //     ncyBreadcrumb: {
            //         label: 'Reviews',// angular translate variable
            //         parent:'admin'
            //     },
                
            //     controller:'ReviewListCtrl',
            //     resolve: {
            //         trans:['RequireTranslations',
            //             function (RequireTranslations) {
            //                 RequireTranslations('modules/reviews');
            //             }],
            //         dep: ['trans','$ocLazyLoad',
            //             function(trans,$ocLazyLoad){
            //                 return $ocLazyLoad.load(['sweet-alert','ui-bs-paging','ReviewServiceModule','ProductServiceModule','datatable']).then(
            //                     function(){
            //                         return $ocLazyLoad.load('app/modules/reviews/controllers/ReviewListCtrl.js');
            //                     }
            //                 );
            //             }],
            //     }
            // })
            // .state('admin.reviewproducts',{
            //     url: "/reviewproducts",
            //     templateUrl: "app/modules/reviews/views/reviewProducts.html",
            //     ncyBreadcrum: {
            //         label: "ReviewProducts",
            //         parent: "admin"
            //     },
                
            //     controller: "ReviewProductsCtrl",
            //     resolve: {
            //         trans: ['RequireTranslations',
            //             function (RequireTranslations){
            //                 RequireTranslations('modules/reviews');
            //             }],
            //         dep: ['trans','$ocLazyLoad',
            //             function(trans,$ocLazyLoad){
            //                 return $ocLazyLoad.load(['sweet-alert','select2','ui-bs-paging','ui-select-filter','ReviewServiceModule','CategoryServiceModule','ProductServiceModule','datatable']).then(
            //                   function(){
            //                       return $ocLazyLoad.load('app/modules/reviews/controllers/ReviewProductsCtrl.js');
            //                   }  
            //                 );
            //             }],
            //         resolvedItems:['dep','ProductService',
            //             function(dep,ProductService) {
            //                 return ProductService.cachedList().then(function(data){
            //                     return data;
            //                 });
            //             }]
            //     }
            // })
            // .state('admin.editReview',{
            //     url: "/editReview/:id",
            //     templateUrl: "app/modules/reviews/views/reviewEdit.html",
            //     ncyBreadcrumb: {
            //         label: 'Edit',// angular translate variable
            //         parent:'admin.reviews'
            //     },
                
            //     controller:'ReviewEditCtrl',
            //     resolve: {
            //         trans:['RequireTranslations',
            //             function (RequireTranslations) {
            //                 RequireTranslations('modules/reviews');
            //             }],
            //         dep: ['trans','$ocLazyLoad',
            //             function(trans,$ocLazyLoad){
            //                 return $ocLazyLoad.load(['sweet-alert','ui-bs-paging','ReviewServiceModule','ProductServiceModule','datatable']).then(
            //                     function(){
            //                         return $ocLazyLoad.load('app/modules/reviews/controllers/ReviewEditCtrl.js');
            //                     }
            //                 );
            //             }],
            //     }
            // })
			.state('admin.asinProduct',{
                url: "/products/new",
                templateUrl: "app/modules/product/views/productform.html",
                ncyBreadcrumb: {
                    label: 'app.breadcrumb.newProduct',// angular translate variable
                    parent:'admin.products'
                },
                
                controller:'ProductAsinCtrl',
                resolve: {
                    trans:['RequireTranslations',
                        function (RequireTranslations) {
                            RequireTranslations('modules/product');
                        }],
                    dep: ['trans','$ocLazyLoad',
                        function(trans,$ocLazyLoad){
                            return $ocLazyLoad.load(['sweet-alert','select2','ui-bs-paging','ProductServiceModule','CategoryServiceModule','ui-select-filter','datatable']).then(
                                function(){
                                    return $ocLazyLoad.load('app/modules/product/controllers/ProductAsinCtrl.js');
                                }
                            );
                        }]
                   }
            })
			.state('admin.addproduct',{
                url: "/products/add",
                templateUrl: "app/modules/product/views/productform1.html",
                ncyBreadcrumb: {
                    label: 'app.breadcrumb.newProduct',// angular translate variable
                    parent:'admin.products'
                },
                
                controller:'ProductAddCtrl',
                resolve: {
                    trans:['RequireTranslations',
                        function (RequireTranslations) {
                            RequireTranslations('modules/product');
                        }],
                    dep: ['trans','$ocLazyLoad',
                        function(trans,$ocLazyLoad){
                            return $ocLazyLoad.load(['sweet-alert','select2','ui-bs-paging','ProductServiceModule','CategoryServiceModule','ui-select-filter','datatable']).then(
                                function(){
                                    return $ocLazyLoad.load('app/modules/product/controllers/ProductAddCtrl.js');
                                }
                            );
                        }],
                   }
            })
            //added harsh
            .state('admin.addproductnew',{
                url: "/products/addnew",
                templateUrl: "app/modules/product/views/productformmultivar.html",
                ncyBreadcrumb: {
                    label: 'app.breadcrumb.newProduct',// angular translate variable
                    parent:'admin.products'
                },
                
                controller:'ProductAddCtrlnew',
                resolve: {
                    trans:['RequireTranslations',
                        function (RequireTranslations) {
                            RequireTranslations('modules/product');
                        }],
                    dep: ['trans','$ocLazyLoad',
                        function(trans,$ocLazyLoad){
                            return $ocLazyLoad.load(['sweet-alert','select2','ui-bs-paging','ProductServiceModule','CategoryServiceModule','ui-select-filter','datatable']).then(
                                function(){
                                    return $ocLazyLoad.load('app/modules/product/controllers/ProductAddCtrlnew.js');
                                }
                            );
                        }],
                   }
            })
            .state('admin.asinBlkProduct',{
                url: "/products/addBulk",
                templateUrl: "app/modules/product/views/productBlkForm.html",
                ncyBreadcrumb: {
                    label: 'addBulk',// angular translate variable
                    parent:'admin.products'
                },
                
                controller:'ProductAddBlkCtrl',
                resolve: {
                    trans:['RequireTranslations',
                        function (RequireTranslations) {
                            RequireTranslations('modules/product');
                        }],
                    dep: ['trans','$ocLazyLoad',
                        function(trans,$ocLazyLoad){
                            return $ocLazyLoad.load(['sweet-alert','select2','ui-bs-paging','ProductServiceModule','CategoryServiceModule','ui-select-filter','datatable']).then(
                                function(){
                                    return $ocLazyLoad.load('app/modules/product/controllers/ProductAddBlkCtrl.js');
                                }
                            );
                        }],
                   }
            })

            .state('admin.editProduct',{
                url: "/products/:id/edit",
                templateUrl: "app/modules/product/views/editProduct.html",
                ncyBreadcrumb: {
                    label: 'app.breadcrumb.editProduct',// angular translate variable
                    parent:'admin.products'
                },
                controller:'ProductEditCtrl',
                resolve: {
                    trans:['RequireTranslations',
                        function (RequireTranslations) {
                            RequireTranslations('modules/product');
                        }],
                    dep: ['trans','$ocLazyLoad',
                        function(trans,$ocLazyLoad){
                            return $ocLazyLoad.load(['bootstrap-tagsinput','summernote','select2','ProductServiceModule','ui-select-filter','dropzone','jquery-ui']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/product/controllers/ProductEditCtrl.js']);
                                }
                            );
                        }],
                    resolvedItems:['dep','ProductService','$stateParams',
                        function(dep,ProductService,$stateParams) {
                            return ProductService.cachedShow($stateParams.id).then(function(data) {
                                return data;
                            });
                        }]
                }
            })
			.state('admin.amzconfig',{
                url: "/amzconfig",
                templateUrl: "app/modules/amzconfig/views/amzconfigform.html",
                ncyBreadcrumb: {
                    label: 'app.breadcrumb.amzconfig',// angular translate variable
                    parent:'admin'
                },
               	controller:'AmzConfigCreateCtrl',
                resolve: {
                    trans:['RequireTranslations',
                        function (RequireTranslations) {
                            RequireTranslations('modules/amzconfig');
                        }],
                    dep: ['trans','$ocLazyLoad',
                        function(trans,$ocLazyLoad){
                            return $ocLazyLoad.load(['select2','AmzConfigServiceModule','ui-select-filter']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/amzconfig/controllers/AmzConfigCreateCtrl.js']);
                                }
                            );
                        }]
                }
            })
			.state('admin.settings',{
                url: "/settings",
                templateUrl: "app/modules/settings/views/settingform.html",
                ncyBreadcrumb: {
                    label: 'app.breadcrumb.settings',// angular translate variable
                    parent:'admin'
                },
               	controller:'SettingsCreateCtrl',
                resolve: {
                    trans:['RequireTranslations',
                        function (RequireTranslations) {
                            RequireTranslations('modules/settings');
                        }],
                    dep: ['trans','$ocLazyLoad',
                        function(trans,$ocLazyLoad){
                            return $ocLazyLoad.load(['select2','SettingsServiceModule','ui-select-filter']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/settings/controllers/SettingsCreateCtrl.js']);
                                }
                            );
                        }],
					resolvedItems:['dep','SettingsService',
                        function(dep, SettingsService) {							
                            return SettingsService.list().then(function(data){
                                return data;
                            });
                        }]
                }
            })
			.state('admin.buynowlink',{
                url: "/buynowlink",
                templateUrl: "app/modules/settings/views/buynowlink.html",
                ncyBreadcrumb: {
                    label: 'app.breadcrumb.buynowlink',// angular translate variable
                    parent:'admin'
                },
               	controller:'SettingsCreateCtrl',
                resolve: {
                    trans:['RequireTranslations',
                        function (RequireTranslations) {
                            RequireTranslations('modules/settings');
                        }],
                    dep: ['trans','$ocLazyLoad',
                        function(trans,$ocLazyLoad){
                            return $ocLazyLoad.load(['select2','SettingsServiceModule','ui-select-filter']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/settings/controllers/SettingsCreateCtrl.js']);
                                }
                            );
                        }],
					resolvedItems:['dep','SettingsService',
                        function(dep, SettingsService) {							
                            return SettingsService.list().then(function(data){
                                return data;
                            });
                        }]
                }
            })
			.state('admin.pricingrules',{
                url: "/pricingrules",
                templateUrl: "app/modules/settings/views/pricingrules.html",
                ncyBreadcrumb: {
                    label: 'app.breadcrumb.pricingrules',// angular translate variable
                    parent:'admin'
                },
               	controller:'SettingsCreateCtrl',
                resolve: {
                    trans:['RequireTranslations',
                        function (RequireTranslations) {
                            RequireTranslations('modules/settings');
                        }],
                    dep: ['trans','$ocLazyLoad',
                        function(trans,$ocLazyLoad){
                            return $ocLazyLoad.load(['select2','SettingsServiceModule','ui-select-filter']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/settings/controllers/SettingsCreateCtrl.js']);
                                }
                            );
                        }],
					resolvedItems:['dep','SettingsService',
                        function(dep, SettingsService) {							
                            return SettingsService.list().then(function(data){
                                return data;
                            });
                        }]
                }
            })
			.state('admin.syncsettings',{
                url: "/syncsettings",
                templateUrl: "app/modules/settings/views/syncsettings.html",
                ncyBreadcrumb: {
                    label: 'app.breadcrumb.syncsettings',// angular translate variable
                    parent:'admin'
                },
               	controller:'SettingsCreateCtrl',
                resolve: {
                    trans:['RequireTranslations',
                        function (RequireTranslations) {
                            RequireTranslations('modules/settings');
                        }],
                    dep: ['trans','$ocLazyLoad',
                        function(trans,$ocLazyLoad){
                            return $ocLazyLoad.load(['select2','SettingsServiceModule','ui-select-filter']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/settings/controllers/SettingsCreateCtrl.js']);
                                }
                            );
                        }],
					resolvedItems:['dep','SettingsService',
                        function(dep, SettingsService) {							
                            return SettingsService.list().then(function(data){
                                return data;
                            });
                        }]
                }
            })
			.state('admin.reviewsettings',{
                url: "/reviewsettings",
                templateUrl: "app/modules/settings/views/reviewsettings.html",
                ncyBreadcrumb: {
                    label: 'app.breadcrumb.reviewsettings',// angular translate variable
                    parent:'admin'
                },
               	controller:'SettingsCreateCtrl',
                resolve: {
                    trans:['RequireTranslations',
                        function (RequireTranslations) {
                            RequireTranslations('modules/settings');
                        }],
                    dep: ['trans','$ocLazyLoad',
                        function(trans,$ocLazyLoad){
                            return $ocLazyLoad.load(['select2','SettingsServiceModule','ui-select-filter']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/settings/controllers/SettingsCreateCtrl.js']);
                                }
                            );
                        }],
					resolvedItems:['dep','SettingsService',
                        function(dep, SettingsService) {							
                            return SettingsService.list().then(function(data){
                                return data;
                            });
                        }]
                }
            })				
			.state('admin.contact',{
                url: "/contact",
                templateUrl: "app/modules/contact/views/contactform.html",
                ncyBreadcrumb: {
                    label: 'app.breadcrumb.contact',// angular translate variable
                    parent:'admin'
                },
               	controller:'ContactCtrl',
                resolve: {
                    trans:['RequireTranslations',
                        function (RequireTranslations) {
                            RequireTranslations('shared');
                        }],
                    dep: ['trans','$ocLazyLoad',
                        function(trans,$ocLazyLoad){
                            return $ocLazyLoad.load(['select2','ContactServiceModule','ui-select-filter']).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/contact/controllers/ContactCtrl.js']);
                                }
                            );
                        }]
                }
            })
			.state('admin.plans',{
                url: "/plans",
                templateUrl: "app/modules/plans/views/plansform.html",
                ncyBreadcrumb: {
                    label: 'app.breadcrumb.plans',// angular translate variable
                    parent: 'admin'
                },
              	controller:'PlansCtrl',
                resolve: {
                    trans:['RequireTranslations',
                        function (RequireTranslations) {
                            RequireTranslations('shared');
                        }],
                    dep: ['trans','$ocLazyLoad',
                        function(trans,$ocLazyLoad){
                            return $ocLazyLoad.load([]).then(
                                function(){
                                    return $ocLazyLoad.load(['app/modules/plans/controllers/PlansCtrl.js']);
                                }
                            );
                        }]
                }
            })
    }
);