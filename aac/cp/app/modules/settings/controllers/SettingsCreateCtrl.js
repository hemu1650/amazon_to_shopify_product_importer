"use strict";

var app = angular.module('ng-laravel', ['ui.select']);
app.controller('SettingsCreateCtrl', function($scope, SettingsService, resolvedItems, $http, $rootScope, $translatePartialLoader, Notification, trans){
		
	$scope.settings = resolvedItems;
	console.log($scope.settings);
    $scope.publishedList = [{id:'1', value:'Published'}, {id:'0', value:'Hidden'}];
    $scope.inventory_syncList = [{id:'1', value:'Enable'}, {id:'0', value:'Disable'}];
	$scope.autoCurrencyConversionList = [{id:'1', value:'Enable'}, {id:'0', value:'Disable'}];
	$scope.outofstock_action_syncList = [{id:'outofstock', value:'Mark it as Sold Out (Quantity = 0) on Shopify'}, {id:'unpublish', value:'Unpublish out-of-stock products'}, {id:'delete', value:'Delete out-of-stock products'}];
    $scope.price_syncList = [{id:'1', value:'Enable'}, {id:'0', value:'Disable'}];
	//$scope.buynow_List = [{id:'1', value:'Redirect users from Product page'}, {id:2, value:'Redirect users from shopping cart page'}, {id:0, value:"Don't redirect users to Amazon"}];
	$scope.buynow_List = [{id:'1', value:'Redirect users from Product page'}, {id:'0', value:"Don't redirect users to Amazon"}];
	$scope.inventory_policyList = [{id:'shopify',value:'Shopify tracks this product\'s inventory'},{id:'NO',value:'Don\'t track inventory'}];
	$scope.markupenabledList = [{id:'1', value:'Enable'}, {id:'0', value:'Disable'}];
	$scope.markuptypeList = [{id:'FIXED', value:'Fixed Amount'}, {id:'PERCEN', value:'Percentage'}];
	$scope.markuproundList = [{id:'1', value:'Round off price to nearest 0.99'}, {id:'0', value:'Do not round off price'}];
	$scope.reviewenabledList = [{id:'1', value:'Enable'}, {id:'0', value:'Disable'}];
	$scope.colorList = [{id:'red', value:'Red'}, {id:'yellow', value:'Yellow'}, {id:'green', value:'Green'}, {id:'blue', value:'Blue'}];

//	$scope.settings.markupenabled = 1;

    $scope.update = function(settings) {
        $scope.isDisabled = true;
        $scope.tmp = angular.isObject(settings) ? angular.toJson(settings) : settings;
        SettingsService.update($scope.tmp);
    };

	$scope.saveBuyNowSettings = function(settings) {
        $scope.isDisabled = true;
        $scope.tmp = angular.isObject(settings) ? angular.toJson(settings) : settings;
        SettingsService.saveBuyNowSettings($scope.tmp);
    };

	$scope.savePricingRulesSettings = function(settings,apply) {
        $scope.isDisabled = true;
        $scope.tmp = angular.isObject(settings) ? angular.toJson(settings) : settings;
        SettingsService.savePricingRulesSettings({settings:$scope.tmp,apply:apply});
    };

	$scope.saveSyncSettings = function(settings) {
        $scope.isDisabled = true;
        $scope.tmp = angular.isObject(settings) ? angular.toJson(settings) : settings;		
        SettingsService.saveSyncSettings($scope.tmp);
    };

	$scope.saveReviewsSettings = function(settings) {
        $scope.isDisabled = true;
        $scope.tmp = angular.isObject(settings) ? angular.toJson(settings) : settings;
        SettingsService.saveReviewsSettings($scope.tmp);
    };

   

    // update event listener
    $scope.$on('settings.update', function() {
        Notification({message: 'settings.form.settingsUpdateSuccess' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
        $scope.isDisabled = false;
    });

	// update event listener
    $scope.$on('settings.buynowSettings', function() {
        Notification({message: 'settings.form.settingsUpdateSuccess' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
        $scope.isDisabled = false;
    });

	// update event listener
    $scope.$on('settings.pricingrulesSettings', function() {
        Notification({message: 'settings.form.settingsUpdateSuccess' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
        $scope.isDisabled = false;
    });

	// update event listener
    $scope.$on('settings.syncSettings', function() {
        Notification({message: 'settings.form.settingsUpdateSuccess' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
        $scope.isDisabled = false;
    });

	// update event listener
    $scope.$on('settings.reviewsSettings', function() {
        Notification({message: 'settings.form.settingsUpdateSuccess' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
        $scope.isDisabled = false;
    });

    //Validation error event listener
    $scope.$on('settings.validationError', function(event,errorData) {
        Notification({message: errorData ,templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'},'warning');
        $scope.isDisabled = false;
    });

    // update event listener
    $scope.$on('settings.productreviewsettings', function() {
        Notification({message: 'settings.form.settingsUpdateSuccess' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
        $scope.isDisabled = false;
    });
});