"use strict";

var app = angular.module('ng-laravel', ['ui.bootstrap','ui.select']);
app.controller('ProductAddCtrl', function ($scope, ProductService, $stateParams, SweetAlert , $http, $translatePartialLoader,CategoryService,$rootScope,trans,Notification) {
    
	$scope.pageObj = {};
	$scope.tmp = {};
	$scope.products = [];
	$scope.pagination = [];
	console.log("AddController");
	ProductService.setCacheList([]);
	$scope.delete = '';
	
    
    $scope.maxSize = 2;

    //console.log($scope.pagination);

    $scope.units = [
        {'id': 10, 'label': '10'},
		{'id': 20, 'label': '20'},
        {'id': 50, 'label': '50'},
        {'id': 100, 'label': '100'},
    ]
    $scope.perPage = $scope.units[1];
    if($scope.pagination != []){
    	if($scope.perPage.id < $scope.pagination.total) {
    		$scope.start = 1;
    		$scope.end = $scope.perPage.id;
    	} else {
    		$scope.start = 1;
    		$scope.end = $scope.pagination.total;
    	}
    }
	
    $scope.pageChanged = function (per_page) {
		$scope.loader = true;
		if($scope.query == '') {
        	ProductService.pageChange($scope.pagination.current_page, per_page.id,$scope.end,$scope.pagination.total).then(function (data) {
            	$scope.loader = false;
				$scope.products = data;
				$scope.pagination = $scope.products.metadata;
				$scope.maxSize = 2;
				if(($scope.pagination.current_page * per_page.id) < $scope.pagination.total) {
					$scope.start = $scope.pagination.current_page * per_page.id - per_page.id + 1;
					$scope.end = $scope.pagination.current_page * per_page.id;			
				} else {
					$scope.start = $scope.pagination.current_page * per_page.id - per_page.id + 1;
					$scope.end = $scope.pagination.total;	
				}
				console.log($scope.pagination);
			});
		} else { 
			ProductService.search($scope.query, per_page.id, $scope.pagination.current_page, $scope.end, $scope.pagination.total).then(function (data) 		{
				$scope.loader = false;
				$scope.products = data;
            	$scope.pagination = $scope.products.metadata;
            	$scope.maxSize = 2;
				if(($scope.pagination.current_page * per_page.id) < $scope.pagination.total) {
					$scope.start = $scope.pagination.current_page * per_page.id - per_page.id + 1;
					$scope.end = $scope.pagination.current_page * per_page.id;			
				} else {
					$scope.start = $scope.pagination.current_page * per_page.id - per_page.id + 1;
					$scope.end = $scope.pagination.total;	
				}
				console.log($scope.pagination);
        	});
		}	 
    };

	 /*
     * Search in Product
     */
    $scope.search = function (per_page) {
		$scope.loader = true;
        ProductService.search($scope.query, per_page.id).then(function (data) {
			$scope.loader = false;
        	$scope.products = data;
            $scope.pagination = $scope.products.metadata;
            $scope.maxSize = 2;
			if($scope.perPage.id < $scope.pagination.total) {
				$scope.start = 1;
				$scope.end = $scope.perPage.id;
			} else {
				$scope.start = 1;
				$scope.end = $scope.pagination.total;
			}
		 });
    };
	
    $scope.fetchReviews = function(product){
    	//console.log("Your Request Accepted");
    	//Notification({message: 'request Accepted' ,templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'},'success');
    	ProductService.fetchAmzReviews(product).then(function(data){
    	    console.log(data);
    		Notification({message: data[0] ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
    	},function(response){
    		Notification({message: "ValidationError" ,templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'},'warning');
    	});
    }

    $scope.destroy = function (key,product) {
		SweetAlert.swal($rootScope.areYouSureDelete,function(isConfirm){
        	if (isConfirm) {
        	    $scope.delete = key;
        	    console.log(key);
            	$scope.tmp = angular.isObject(product) ? angular.toJson(product) : product;
		 		ProductService.destroy($scope.tmp);
            }
        });		
    };

	$scope.refresh = function(){
		$scope.loader = true;
		$scope.query = '';
		ProductService.list().then(function (data) {
			$scope.loader = false;
        	$scope.products = data;
        	$scope.pagination = $scope.products.metadata;
			if($scope.perPage.id < $scope.pagination.total) {
				$scope.start = 1;
				$scope.end = $scope.perPage.id;
			} else {
				$scope.start = 1;
				$scope.end = $scope.pagination.total;
			}
		});
	}


  /* $scope.addProduct = function(product) {
		$scope.isDisabled = true;
		$scope.loader = true;
		$scope.tmp = angular.isObject(product) ? angular.toJson(product) : product;
        ProductService.addProduct($scope.tmp).then(function(data) {
        	console.log(data[1]);
        	console.log(data[1][0]['product_id']);
        	var d = ProductService.cachedList2();
        	d.splice(0,0,data[1][0]);
        	ProductService.setCacheList(d);
        	$scope.products = d;
			//console.log($scope.products);
		    //$scope.pagination = $scope.products.metadata;
            $rootScope.$broadcast('product.addProduct',data[1][0]['product_id']);
        },function(response){			 
           $rootScope.$broadcast('product.validationError', response.data.error);
        });;
    };  
	*/
	
	
	$scope.addProduct = function(product) {		
		if(product['producturl']){
		    if(product['producturl'].includes('amazon')){
				$scope.isDisabled = true;
				$scope.loader = true;
				$scope.tmp = angular.isObject(product) ? angular.toJson(product) : product;
				ProductService.addProduct($scope.tmp).then(function(data) {
					var d = ProductService.cachedList2();
					d.splice(0,0,data[1][0]);
					ProductService.setCacheList(d);
					$scope.products = d;					
					$rootScope.$broadcast('product.addProduct',data[1][0]['product_id']);
				},function(response){			 
				   $rootScope.$broadcast('product.validationError', response.data.error);
				});
			} else {
				Notification({message: "CheckInManageProducts" ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
			}
		} else {
Notification({message: "CheckInManageProducts" ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
		}
    };  
	
	
	$scope.$on('product.addProduct', function(event,product_id) {
	    console.log(product_id);
   		Notification({message: 'product.form.productAddSuccess' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
      	$scope.pageObj = {'producturl':''};
		$scope.isDisabled = false; 
      	$scope.loader = false;
      	$.ajax({type: "GET",data: {id : product_id},url: "https://shopify.infoshore.biz/aac/api/public/importToShopify.php", success: function(result){
      	    console.log(result);
      	    if(result != ""){
      	        var d = ProductService.cachedList2();
          	    d[0].status = "Imported";
          	    d[0].shopifyproductid = result;
          	    $scope.products[0].status = "Imported";
          	    $scope.products[0].shopifyproductid = result;
          	    ProductService.setCacheList(d);
          	    Notification({message: 'Imported' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
      	    }else{
      	        Notification({message: "CheckInManageProducts" ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
      	    }
        },function(response){
            console.log(response);
        }});
    });



/*	$scope.addProduct = function(product) {
		$scope.isDisabled = true;
		$scope.loader = true;
		$scope.tmp = angular.isObject(product) ? angular.toJson(product) : product;
        ProductService.addProduct($scope.tmp).then(function(data) {
        	console.log(data[1]);
        	var d = ProductService.cachedList2();
        	d.splice(0,0,data[1][0]);
        	ProductService.setCacheList(d);
        	$scope.products = d;
			//console.log($scope.products);
		    //$scope.pagination = $scope.products.metadata;
            $rootScope.$broadcast('product.addProduct');
        },function(response){			 
           $rootScope.$broadcast('product.validationError', response.data.error);
        });;
    };  
	
	$scope.$on('product.addProduct', function() {
   		Notification({message: 'product.form.productAddSuccess' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
      	$scope.pageObj = {'producturl':''};
		$scope.isDisabled = false; 
      	$scope.loader = false;
      	$.ajax({url: "https://shopify.infoshore.biz/aac/api/public/importToShopify.php", success: function(result){
      	    console.log(result);
      	    if(result != ""){
      	    var d = ProductService.cachedList2();
          	    d[0].status = "Imported";
          	    d[0].shopifyproductid = result;
          	    $scope.products[0].status = "Imported";
          	    $scope.products[0].shopifyproductid = result;
          	    ProductService.setCacheList(d);
      	    }
        },function(response){
            console.log(response);
        }});
    });	*/
	
   // update list when product not deleted
    $scope.$on('product.validationError',  function(event,errorData) {
		Notification({message: errorData ,templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'},'warning');
		$scope.isDisabled = false;
		$scope.loader = false;
    });
	
	$scope.$on('product.destroy', function() {
		SweetAlert.swal($rootScope.recordDeleted);
		 ProductService.list().then(function (data) {
		     var d = ProductService.cachedList2();
		     console.log(d);
		     d.splice($scope.delete,1);
		     ProductService.setCacheList(d);
		     console.log(d);
			$scope.products = d;
			//$scope.pagination = $scope.products.metadata;
		});
     
    });
	
 	$scope.$on('product.not.delete', function() {
        SweetAlert.swal($rootScope.recordNotDeleted);
        ProductService.list().then(function (data) {	
			$scope.products = data;
			$scope.pagination = $scope.products.metadata;
			
		});
    });
});
