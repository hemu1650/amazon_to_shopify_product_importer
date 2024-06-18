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
	
	$scope.padd = [];
	$scope.addFieldId = function(product_id){
		$scope.padd.id = product_id;
	}
	
	$scope.addField = function(product){
		var result = document.getElementsByClassName("close");
		var wrappedResult = angular.element(result);
		for(var m =0;m<wrappedResult.length;m++){
			wrappedResult[m].click();
		}
		$scope.loader = true;
		var data = {'id': product.id, 'price': product.price};
		ProductService.addField(data).then(function(data) {
			$scope.loader = false;
			console.log(data);
			if(data[1]){
				for(var s=0;s<$scope.products.length;s++){
					if($scope.products[s].product_id){
						if($scope.products[s].product_id == data[1]){
							$scope.products[s]['status'] = 'Imported';
							if(data[2]){$scope.products[s]['shopifyproductid'] = data[2];}
						}
					}
				}
			}
		},function(response){
			$rootScope.$broadcast('product.validationError', response.data.error);
		});
	}
	
	$scope.addProduct = function(product) {
		if(product['producturl']){
		    if(product['producturl'].includes('amazon')){
				$scope.products.push({'title': product['producturl'], 'status': 'Import in progress'});
				$scope.tmp = angular.isObject(product) ? angular.toJson(product) : product;
				$scope.pageObj = {'producturl':''};
				 ProductService.addProduct($scope.tmp).then(function(data) {
					for(var s=0;s<$scope.products.length;s++){
						if($scope.products[s].title == data[1][0]['variants'][0]['detail_page_url']){
							$scope.products[s] = data[1][0];
						}
					}
					/*var d = ProductService.cachedList2();
					d.splice(0,0,data[1][0]);
					ProductService.setCacheList(d);
					$scope.products = d;*/
					//$scope.pagination = $scope.products.metadata;
					$rootScope.$broadcast('product.addProduct',data[1][0]['product_id']);
				},function(response){
					if(response['data'][0]){
						for(var s=0;s<$scope.products.length;s++){
							if($scope.products[s].title == response['data'][0]['purl']){
								$scope.products[s] = {'title': response['data'][0]['purl'], 'status': 'Error ('+response['data']['error']['msg'][0]+')', 'cstatus': 'Error'};
							}
						}
					}else{
						$rootScope.$broadcast('product.validationError', response.data.error);
					}
				});
		  }else{alert('Please enter a valid product URL.');}
		}else{alert('Please enter a valid product URL.');}
    };  
	
	/*
	$scope.$on('product.addProduct', function(event,product_id) {
	    console.log(product_id);
   		$scope.pageObj = {'producturl':''};
		$.ajax({type: "GET",data: {id : product_id},url: "https://shopify.infoshore.biz/acc/api/public/importToShopify.php", success: function(result){
      	    console.log(result);
      	    if(result != ""){
      	        var d = ProductService.cachedList2();
          	    d[0].status = "Imported";
          	    d[0].shopifyproductid = result;
          	    $scope.products[0].status = "Imported";
          	    $scope.products[0].shopifyproductid = result;
          	    ProductService.setCacheList(d);
          	    
      	    }else{
      	        
      	    }
        },function(response){
            console.log(response);
        }});
    });*/



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
