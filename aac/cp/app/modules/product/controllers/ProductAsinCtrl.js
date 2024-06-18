"use strict";

var app = angular.module('ng-laravel', ['ui.bootstrap','ui.select']);
app.controller('ProductAsinCtrl', function ($scope, ProductService, $stateParams, $http, $translatePartialLoader,CategoryService,$rootScope,trans,Notification) {

    /*
     * Define initial value
     */
	$scope.tmp = {};
    $scope.query = '';
    $scope.radioValue = 1;
	
	CategoryService.list().then(function(data){
       $scope.categories = data;
    });
	
	$scope.perPage = 10;

	$scope.createsingle = function(product) {
		$scope.isDisabled = true;
		$scope.loader = true;
		$scope.tmp = angular.isObject(product) ? angular.toJson(product) : product;
        ProductService.createsingle($scope.tmp);
    };  
	
	$scope.createmany = function(product) {
		$scope.isDisabled = true;
		$scope.loader = true;
		$scope.tmp = angular.isObject(product) ? angular.toJson(product) : product;
        ProductService.createmany($scope.tmp);
    };
	
	$scope.amzpageChanged = function (per_page) {
		$scope.loader = true;
		if($scope.pagination.current_page >= 11) {
			alert('Please refine your search criteria.');
			$scope.loader = false;
		} else {
			ProductService.asin_search($scope.keyword, $scope.category, per_page,$scope.pagination.current_page,$scope.end,$scope.pagination.total).then(function (data) {
				$scope.loader = false;
            	$scope.products = data;
				$scope.pagination = $scope.products.metadata;
				$scope.maxSize = 2;
				if(($scope.pagination.current_page * per_page) < $scope.pagination.total) {
					$scope.start = $scope.pagination.current_page * per_page - per_page + 1;
					$scope.end = $scope.pagination.current_page * per_page;			
				} else {
					$scope.start = $scope.pagination.current_page * per_page - per_page + 1;
					$scope.end = $scope.pagination.total;	
				}
			}, function(response) {			
            	$rootScope.$broadcast('product.validationError',response.data.error);
        	});
		}
    };

	 /*
     * Search in Product
     */
    $scope.asin_search = function (per_page) {
		$scope.loader = true;
    	ProductService.asin_search($scope.keyword, $scope.category, per_page).then(function (data) {
			$scope.loader = false;
        	$scope.products = data;			
            $scope.pagination = $scope.products.metadata;
            $scope.maxSize = 2;
			if($scope.perPage < $scope.pagination.total) {
				$scope.start = 1;
				$scope.end = $scope.perPage;
			} else {
				$scope.start = 1;
				$scope.end = $scope.pagination.total;
			}
		}, function(response){			
           $rootScope.$broadcast('product.validationError', response.data.error);
        });
    };
	
	$scope.$on('product.createsingle', function() {
   		Notification({message: 'product.form.productCreateSingle' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
      	$scope.isDisabled = false; 
      	$scope.loader = false;
    });
	
	$scope.$on('product.createmany', function() {
    	Notification({message: 'product.form.productCreateMany' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
       	$scope.isDisabled = false;
       	$scope.loader = false;
    });
	
   // update list when product not deleted
    $scope.$on('product.validationError',  function(event,errorData) {
		Notification({message: errorData ,templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'},'warning');
		$scope.isDisabled = false;
		$scope.loader = false;
    });
});