"use strict";

var app = angular.module('ng-laravel', ['ui.select']);
app.controller('SettingsCreateCtrl', function($scope, SettingsService, resolvedItems, $http, $rootScope, $translatePartialLoader, Notification, trans){
		
	$scope.settings = resolvedItems;

    $scope.publishedList = [{id:1, value:'Published'}, {id:0, value:'Hidden'}];
    $scope.inventory_syncList = [{id:1, value:'Enable'}, {id:0, value:'Disable'}];
	$scope.outofstock_action_syncList = [{id:'unpublish', value:'Unpublish out-of-stock products'}, {id:'delete', value:'Delete out-of-stock products'}];
    $scope.price_syncList = [{id:1, value:'Enable'}, {id:0, value:'Disable'}];
	//$scope.buynow_List = [{id:1, value:'Redirect users from Product page'}, {id:2, value:'Redirect users from shopping cart page'}, {id:0, value:"Don't redirect users to Amazon"}];
	$scope.buynow_List = [{id:1, value:'Redirect users from Product page'}, {id:0, value:"Don't redirect users to Amazon"}];
	$scope.inventory_policyList = [{id:'shopify',value:'Shopify tracks this product\'s inventory'},{id:'',value:'Don\'t track inventory'}];

    $scope.update = function(settings) {
        $scope.isDisabled = true;
        $scope.tmp = angular.isObject(settings) ? angular.toJson(settings) : settings;
        SettingsService.update($scope.tmp);
    };

    // update event listener
    $scope.$on('settings.update', function() {
        Notification({message: 'settings.form.settingsUpdateSuccess' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
        $scope.isDisabled = false;
    });

    //Validation error event listener
    $scope.$on('settings.validationError', function(event,errorData) {
        Notification({message: errorData ,templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'},'warning');
        $scope.isDisabled = false;
    });
});