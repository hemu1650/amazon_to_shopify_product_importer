"use strict";

var app = angular.module('ng-laravel', ['ui.bootstrap','ui.select']);
app.controller('ProductAddBlkCtrl', function ($scope, ProductService, $stateParams, SweetAlert , $http, $translatePartialLoader,CategoryService,$rootScope,trans,Notification) {
    
	$scope.pageObj = {};
	$scope.imports = [];
	$scope.validfile = 0;
	ProductService.importHistory().then(function($data){
	    $scope.imports = $data;
	    console.log($scope.imports);
	});
	
	console.log("AddController");
	$scope.supportedUrls = [
	        {id:'www.amazon.com', value:'www.amazon.com'},
	        {id:'www.amazon.ca', value:'www.amazon.ca'},
	        {id:'www.amazon.in', value:'www.amazon.in'},
	        {id:'www.amazon.co.uk', value:'www.amazon.co.uk'},
	        {id:'www.amazon.com.br', value:'www.amazon.com.br'},
	        {id:'www.amazon.com.mx', value:'www.amazon.com.mx'},
	        {id:'www.amazon.de', value:'www.amazon.de'},
	        {id:'www.amazon.es', value:'www.amazon.es'},
	        {id:'www.amazon.fr', value:'www.amazon.fr'},
	        {id:'www.amazon.it', value:'www.amazon.it'},
	        {id:'www.amazon.co.jp', value:'www.amazon.co.jp'},
	        {id:'www.amazon.cn', value:'www.amazon.cn'},
	        {id:'www.amazon.com.au', value:'www.amazon.com.au'}
	        ];
    $scope.amazon_url = "www.amazon.com";

	
    /*
     * Search in Product
     */
    $scope.validate = function(){
        var url = /https:\/\/www.amazon.com/;
        if($scope.amazon_url === ""){
            return false;
        }else{
            if(url.test($scope.amazon_url)){
                return true;
            }else{
                return false;
            }
            console.log(url.test($scope.amazon_url));
        }
        return false;
    }
	/*$scope.addProduct = function(plan) {
		console.log(plan);
		if(plan == 3 || plan == 4){
    		//$scope.tmp = angular.isObject(product) ? angular.toJson(product) : product;
    		console.log($scope.uploadfile);
    		   if($scope.uploadfile){
    		       if($scope.validate()){
    		        $scope.isDisabled = true;
		            $scope.loader = true;
                    ProductService.addBlkProduct($scope.uploadfile,$scope.amazon_url).then(function(data) {
                        console.log(data);
                        $scope.imports = data[1];
                        $rootScope.$broadcast('product.addProduct');
                        $scope.isDisabled = false;
    		            $scope.loader = false;
                    },function(response){
                        console.log(response);
                        $scope.isDisabled = false;
    		            $scope.loader = false;
                       $rootScope.$broadcast('product.validationError', response.data.error);
                    });
    		       }else{
                        alert("please enter proper amazon url");
                        $scope.isDisabled = false;
		                $scope.loader = false;
    		       }
    		   }else{
    		       $scope.uploadfile = null;
    		       $scope.isDisabled = false;
		           $scope.loader = false;
    		       alert("please enter file in csv format");
    		   }
		}
    };  */
    
    
    	$scope.addProduct = function(plan) {
		console.log(plan);
		if(plan == 3 || plan == 4){
    		//$scope.tmp = angular.isObject(product) ? angular.toJson(product) : product;
        console.log($scope.uploadfile);

    		   if($scope.uploadfile){
    		        $scope.isDisabled = true;
		            $scope.loader = true;
                    ProductService.addBlkProduct($scope.uploadfile,$scope.amazon_url).then(function(data) {
                        console.log(data);
                        $scope.imports = data[1];
                        $rootScope.$broadcast('product.addProduct');
                        $scope.isDisabled = false;
    		            $scope.loader = false;
                    },function(response){
                        console.log(response);
                        $scope.isDisabled = false;
    		            $scope.loader = false;
                       $rootScope.$broadcast('product.validationError', response.data.error);
                    });
    		   }else{
    		       $scope.uploadfile = null;
    		       $scope.isDisabled = false;
		           $scope.loader = false;
    		       alert("please enter file in csv format");
    		   }
		}
    };  
    
    $scope.updateASIN = function(data){
        console.log(data);
        $scope.ASIN = data;
    }
	
	$scope.$on('product.addProduct', function() {
   		new Notification({message: 'ASIN uploaded. Import in progress.' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
    });	
	
   // update list when product not deleted
    $scope.$on('product.validationError',  function(event,errorData) {
		new Notification({message: errorData ,templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'},'warning');
		$scope.isDisabled = false;
		$scope.loader = false;
    });
	
});
app.directive("fileread", [
  function() {
    return {
      scope: {
        fileread: "="
      },
      link: function(scope, element, attributes) {
        element.bind("change", function(changeEvent) {
          var reader = new FileReader();
          reader.onload = function(loadEvent) {
              if(/.csv/.test(changeEvent.target.files[0].name)){
                scope.$apply(function() {
                  scope.fileread = loadEvent.target.result;
                });
              }
          }
          if(/.csv/.test(changeEvent.target.files[0].name)){
               reader.readAsDataURL(changeEvent.target.files[0]);   
          }
        });
      }
    }
  }
]);