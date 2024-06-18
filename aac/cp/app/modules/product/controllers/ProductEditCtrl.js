"use strict";

var app = angular.module('ng-laravel',['dropzone','ui.select','ui.bootstrap']);
app.controller('ProductEditCtrl',function($scope,ProductService,$stateParams,$http,$rootScope,resolvedItems,$translatePartialLoader,Notification,trans){
	
								  
	$scope.products = resolvedItems;
	ProductService.show($stateParams.id).then(function(data) {
		 $scope.product = data;
	});
	
	$scope.update = function(product) {
		$scope.loader = true;
		$scope.tmp = angular.isObject(product) ? angular.toJson(product) : product;
		ProductService.update($scope.tmp);
	};
	
	$scope.$on('product.update', function() {
        Notification({message: 'Product has been updated successfully!' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
        $scope.isDisabled = false;
		$scope.loader = false;
    });
	 
	$scope.$on('product.validationError', function(event,errorData) {
		 if(typeof errorData === "undefined"){
			var errorData = {msg:["There was some error processing this request."]};
		}
        Notification({message: errorData ,templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'},'warning');
        $scope.loader = false;
    });
	
});

