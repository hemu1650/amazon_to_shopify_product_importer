"use strict";

var app = angular.module('ng-laravel', ['ui.bootstrap']);
app.controller('ReviewProductsCtrl', function ($scope,$state,ReviewService, ProductService, $stateParams, $http, resolvedItems, CategoryService,$translatePartialLoader, $rootScope, trans, Notification, SweetAlert) {

    /*
     * Define initial value
     */
    $scope.query = '';
    $scope.radioValue = 1;
	
	/*
     * Get all Products
     * Get from resolvedItems function in this page route (config.router.js)
     */
    $scope.products = resolvedItems;
    // console.log($scope.products);
    $scope.pagination = $scope.products.metadata;
    $scope.maxSize = 2;
    $scope.checkedStatus = false;
    $scope.checkA = false;
    /*
     * Get all Product and refresh cache.
     * At first check cache, if exist, we return data from cache and if don't exist return from API
     */
    ProductService.list().then(function (data) {		
        $scope.products = data;
        $scope.pagination = $scope.products.metadata;	
		
		// console.log($scope.products.metadata);
	});
	
	$scope.viewReviews = function(asin){
	    $state.go("admin.reviews",{id:asin});
	}
	$scope.fetchReviews = function(product){
	    $scope.loader = true;
		ProductService.fetchAmzReviews(product).then(function(data){
		    $scope.loader = false;
    	    console.log("hello");
    		Notification({message: data[0] ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
    	},function(response){
    	    $scope.loader = false;
    		new Notification({message: response.data.error, templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'},'warning');
    	});
	}
	$scope.destroy = function (product) {
		SweetAlert.swal($rootScope.areYouSureDelete,
    	function(isConfirm){
        	if (isConfirm) {
            	$scope.tmp = angular.isObject(product) ? angular.toJson(product) : product;
		 		ProductService.destroy($scope.tmp);
            }
        });		
    };
	
	$scope.popupvariants = function (product_id){
		for(var i=0; i < $scope.products.length; i++) {
			if($scope.products[i].product_id == product_id.id) {
				$scope.popupvariant = $scope.products[i].variants;
				$scope.popupoption1 = $scope.products[i].option1name;
				$scope.popupoption2 = $scope.products[i].option2name;
				$scope.popupoption3 = $scope.products[i].option3name;
			}
		}
	};

     /*
     * Pagination product list
     */
	$scope.units = [
        {'id': 10, 'label': '10'},
		{'id': 20, 'label': '20'},
        {'id': 50, 'label': '50'},
        {'id': 100, 'label': '100'},
    ]
    $scope.perPage = $scope.units[1];
	if($scope.perPage.id < $scope.pagination.total) {
		$scope.start = 1;
		$scope.end = $scope.perPage.id;
	} else {
		$scope.start = 1;
		$scope.end = $scope.pagination.total;
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
	
	/*
     * block selected Products
     */
    $scope.block = function (product) {
		var errmsg = {"msg": ["Please choose a product to block."]};
		if(Array.isArray(product)){
			if(product.length > 0){
				$scope.loader = true;
				ProductService.block(product);
			} else {
				Notification({message: errmsg, templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'}, 'warning');
			}				
		} else {
			if(product){
				$scope.loader = true;
				ProductService.block(product);
			} else {
				Notification({message: errmsg, templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'}, 'warning');
			}
		}		
    };

	/*
     * unblock selected Products
     */
    $scope.unblock = function (product) {
		var errmsg = {"msg": ["Please choose a product to un-block."]};
		if(Array.isArray(product)){
			if(product.length > 0){
				$scope.loader = true;
				ProductService.unblock(product);
			} else {
				Notification({message: errmsg, templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'}, 'warning');
			}				
		} else {
			if(product){
				$scope.loader = true;
				ProductService.unblock(product);
			} else {
				Notification({message: errmsg, templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'}, 'warning');
			}
		}		
    };

	/*
     * reimport selected Products
     */
	$scope.reimport = function (product) {
		var errmsg = {"msg": ["Please choose a product to import."]};
		if(Array.isArray(product)){
			if(product.length > 0){
				$scope.loader = true;
				ProductService.reimport(product);	
			} else {
				Notification({message: errmsg, templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'}, 'warning');
			}				
		} else {
			if(product){
				$scope.loader = true;
				ProductService.reimport(product);
			} else {
				Notification({message: errmsg, templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'}, 'warning');
			}
		}
    };

    /**********************************************************
     * Event Listener
     **********************************************************/
        // Get list of selected product to do actions
    $scope.selection = [];
	$scope.selectedAll = false;
	$scope.checkAll = function () {
        if ($scope.selectedAll == false) {
            $scope.selectedAll = true;
            $scope.checkA = true;
            $scope.checkedStatus = true;
        } else {
            $scope.selectedAll = false;
            $scope.checkA = false;
            $scope.checkedStatus = false;
            $scope.selection = [];
        }
        angular.forEach($scope.products, function (product) {
			 product.Selected = $scope.selectedAll;
			 if($scope.selectedAll){
			     var idx = $scope.selection.indexOf(product.product_id);
			     if(idx>-1){
			         $scope.selection.splice(idx, 1);
			     }
			    $scope.selection.push(product.product_id);
			 }
        });
        $scope.checkAllStatus();
    };
	
    $scope.toggleSelection = function toggleSelection(productId) {
        // toggle selection for a given product by Id
        var idx = $scope.selection.indexOf(productId);
        // is currently selected
        if (idx > -1) {
            $scope.selection.splice(idx, 1);
        }
        // is newly selected
        else {
            $scope.selection.push(productId);
        }
        $scope.checkAllStatus();
    };
    
    $scope.checkAllStatus = function(){
        // console.log($scope.selection);
        if($scope.checkA == true){
            $scope.checkedStatus = true;
        }else if($scope.selection.length > 0){
            $scope.checkedStatus = true;
        }else{
            $scope.checkedStatus = false;
        }
        if($scope.selection.length < $scope.perPage.id){
            $scope.selectedAll = false;
        }else{
            $scope.selectedAll = true;
        }
    }

	$scope.removeSelection = function removeSelection(productId) {
		// toggle selection for a given product by Id
		var idx = $scope.selection.indexOf(productId);
		// is currently selected
		if (idx > -1) {
			$scope.selection.splice(idx, 1);
        }
    };
    
    $scope.downloadAllSelected = function(){
        $scope.loader = true;
        ReviewService.downloadAllSelected($scope.selection).then(function(data){
            $scope.loader = false;
            // console.log(data);
            window.open(data[0], '_self');
        },function(response){
            $scope.loader = false;
            new Notification({message: response.data[0], templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'warning');
            // console.log(response);
        });
    }
    
    $scope.fetchAllSelected = function(){
        ReviewService.fetchAllSelected($scope.selection).then(function(data){
            // console.log(data);
            //window.open(data[0], '_self');
            new Notification({message: "Request to fetch reviews has been submitted", templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
        },function(response){
            new Notification({message: response.data.error, templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'},'warning');
            // console.log(response);
        });
    } 
	
    // update list when product not deleted
    $scope.$on('product.validationError',  function(event,errorData) {
		$scope.loader = false;
        $scope.selection = [];
		$scope.selectedAll= '';
        Notification({message: errorData, templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'},'warning');
    });
	
	$scope.$on('product.destroy', function() {
		SweetAlert.swal($rootScope.recordDeleted);
		 ProductService.list().then(function (data) {	
			$scope.products = data;
			$scope.pagination = $scope.products.metadata;
		});
     
    });
	
 	$scope.$on('product.not.delete', function() {
        SweetAlert.swal($rootScope.recordNotDeleted);
        ProductService.list().then(function (data) {	
			$scope.products = data;
			$scope.pagination = $scope.products.metadata;
			
		});
    });

	$scope.$on('product.block', function (event, data) {
		$scope.loader = false;
		if(Array.isArray(data)){
			Notification({message: 'product.form.productBlockSuccessP', templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
			for(var i = 0; i < $scope.products.length; i++) {
				if($.inArray($scope.products[i].product_id, data) > -1) {
					$scope.products[i].block = 1;
					$scope.removeSelection($scope.products[i].product_id);
				}
			}
			$scope.selectedAll = '';
		} else {
			Notification({message: 'product.form.productBlockSuccess', templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
			for(var i = 0; i < $scope.products.length; i++) {
				if($scope.products[i].product_id == data) {
					$scope.products[i].block = 1;
					$scope.removeSelection($scope.products[i].product_id);
					break;
				}
			}
		}		
    });
	
	$scope.$on('product.unblock', function (event, data) {
		$scope.loader = false;
        if(Array.isArray(data)){
			Notification({message: 'product.form.productUnblockSuccessP' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
			for(var i = 0; i < $scope.products.length; i++) {
				if($.inArray($scope.products[i].product_id, data) > -1) {
					$scope.products[i].block = 0;
					$scope.removeSelection($scope.products[i].product_id);
				}
			}
			$scope.selectedAll = '';
		} else {
			Notification({message: 'product.form.productUnblockSuccess' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
			for(var i = 0; i < $scope.products.length; i++) {
				if($scope.products[i].product_id == data) {
					$scope.products[i].block = 0;
					$scope.removeSelection($scope.products[i].product_id);
					break;
				}
			}
		}		
    });

	$scope.$on('product.reimport', function (event, data) {
		$scope.loader = false;
        if(Array.isArray(data)){
			Notification({message: 'product.form.reimportPushSuccessP', templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
			for(var i = 0; i < $scope.products.length; i++) {
				if($.inArray($scope.products[i].product_id, data) > -1) {
					$scope.products[i].status = "reimport in progress";
					$scope.removeSelection($scope.products[i].product_id);
				}
			}
			$scope.selectedAll = '';
		} else {
			Notification({message: 'product.form.reimportPushSuccess', templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
			for(var i = 0; i < $scope.products.length; i++) {
				if($scope.products[i].product_id == data) {
					$scope.products[i].status = "reimport in progress";
					$scope.removeSelection($scope.products[i].product_id);
					break;
				}
			}
		}		
    });
});