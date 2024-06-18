"use strict";

var app = angular.module('ng-laravel', ['ui.bootstrap']);
app.controller('ProductListCtrl', function ($scope,$state,ReviewService, ProductService, $stateParams, $http, resolvedItems, CategoryService,$translatePartialLoader, $rootScope, trans, Notification, SweetAlert) {

    /*
     * Define initial value
     */
    $scope.query = '';
    $scope.radioValue = 1;
    $scope.alert = false;
	
	/*
     * Get all Products
     * Get from resolvedItems function in this page route (config.router.js)
     */
    $scope.products = resolvedItems;
    console.log($scope.products);
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
	});
	
	$scope.viewReviews = function(asin){
	    $state.go("admin.reviews",{id:asin});
	}
	$scope.fetchReviews = function(product){
		ProductService.fetchAmzReviews(product).then(function(data){
    	    console.log(data);
    		Notification({message: data[0] ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
    	},function(response){
    		Notification({message: "ValidationError" ,templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'},'warning');
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
	////Pankaj Sir
	$scope.exportProducts = function(plan){
	    if(plan == 3 || plan == 4){
	        $scope.alert = false;
	        $scope.loader = true;
        	ProductService.exportProducts($scope.selection).then(function(data){
                console.log(data);
                window.open(data[0], '_self');
                $scope.loader = false;
            },function(response){
				console.log(response);
                new Notification({message: response.data[0], templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'warning');
                console.log(response);
                $scope.loader = false;
            });
	    }else{
	        $scope.alert = true;
	    }
    }
	
	
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
		$scope.reimport = function (product,plan) {
		 if(plan <=1){
        	  Notification({message: "Reimport Feature is not available in this plan", templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'}, 'success');
        	}
        else{ 
		
		var errmsg = {"msg": ["Please choose a product to import."]};
		//product.status = "Re-Import In Progress";
		if(Array.isArray(product)){
			if(product.length > 0){
				Notification({message: "Re-import  Request for ASIN:"+product.variants[0].sku+" Submitted", templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'}, 'success');
				ProductService.reimport(product.product_id,product.variants[0].sku);
			} else {
				Notification({message: errmsg, templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'}, 'warning');
			}				
		} else {
			if(product){
				Notification({message: "Re-import  Request for ASIN:"+product.variants[0].sku+" Submitted", templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'}, 'success');
				ProductService.reimport(product.product_id,product.variants[0].sku);
			} else {
				Notification({message: errmsg, templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'}, 'warning');
			}
		}
            
        }
    };
    
    $scope.forceSync = function(product,plan) {
        if(plan <=1){
        	  Notification({message: "Force Sync Feature is not available in this plan", templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'}, 'success');
        	}
        else{ 	
        
        if(Array.isArray(product)){
			if(product.length > 0){
				Notification({message: "forSync Request for ASIN:"+product.variants[0].sku+" Submitted", templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'}, 'success');
				ProductService.forceSync(product.product_id,product.variants[0].sku);	
			} else {
				Notification({message: errmsg, templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'}, 'warning');
			}				
		} else {
			if(product){
				Notification({message: "forSync Request for ASIN:"+product.variants[0].sku+" Submitted", templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'}, 'success');
				ProductService.forceSync(product.product_id,product.variants[0].sku);
			} else {
				Notification({message: errmsg, templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'}, 'warning');
			}
		}
          }
            
    }
	
	$scope.getVariantLink = function (product_id) {
	    for(var i=0; i < $scope.products.length; i++) {
		    if($scope.products[i].product_id == product_id.id) {
			    $scope.productobj = $scope.products[i];
			}
		}
    };

	$scope.changeLink = function(productobj,plan) {
		if(plan <0){
			Notification({message: "This feature is not available in this plan.", templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'}, 'success');
		} else { 	
        
        if(Array.isArray(productobj)){
			if(productobj.length > 0){
				Notification({message: "Request to update Buy Now link for ASIN: "+productobj.variants[0].sku+" has been initiated.", templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'}, 'success');
				$scope.tmp = angular.isObject(productobj) ? angular.toJson(productobj) : productobj;
		 		ProductService.changeLink($scope.tmp);
			} else {
				Notification({message: errmsg, templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'}, 'warning');
			}				
		} else {
			if(productobj){
				Notification({message: "Request to update Buy Now link for ASIN: "+productobj.variants[0].sku+" has been initiated.", templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'}, 'success');
				$scope.tmp = angular.isObject(productobj) ? angular.toJson(productobj) : productobj;
		 		ProductService.changeLink($scope.tmp);
			} else {
				Notification({message: errmsg, templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'}, 'warning');
			}
		}
          }
     }

    /**********************************************************
     * Event Listener
     **********************************************************/
        // Get list of selected product to do actions
    $scope.selection = [];
	$scope.selectedAll = '';
	$scope.checkAll = function () {
        if ($scope.selectedAll) {
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
        console.log($scope.selection);
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
    
    
    $scope.forceSyncSelected = function(){
        Notification({message: "forSync Request for Selected Items Submitted", templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'}, 'success');
        ProductService.forceSyncSelected($scope.selection).then(function(data){
            console.log(data);
            new Notification({message: "Synchronized", templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
            //window.open(data[0], '_self');
        },function(response){
            new Notification({message: response.error, templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'},'warning');
            console.log(response);
        });
    }

    
    $scope.exportAllProducts = function(){
        $scope.loader = true;
        ProductService.exportAllProducts().then(function(data){
            console.log(data);
            new Notification({message: "Downloading...", templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
            $scope.loader = false;
            window.open(data[0], '_self');
        },function(response){
            new Notification({message: response.error, templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'},'warning');
            $scope.loader = false;
            console.log(response);
        });
    }
	
    // update list when product not deleted
    $scope.$on('product.validationError',  function(event,errorData) {
		$scope.loader = false;
        $scope.selection = [];
		$scope.selectedAll= '';
        new Notification({message: errorData, templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'},'warning');
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
	
	$scope.$on('product.changeLink', function (event, data) {
		$scope.loader = false;
		new Notification({message: 'Buy Now link has been updated successfully.', templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');				
    });
	
	$scope.$on('product.block', function (event, data) {
		$scope.loader = false;
		if(Array.isArray(data)){
			new Notification({message: 'product.form.productBlockSuccessP', templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
			for(var i = 0; i < $scope.products.length; i++) {
				if($.inArray($scope.products[i].product_id, data) > -1) {
					$scope.products[i].block = 1;
					$scope.removeSelection($scope.products[i].product_id);
				}
			}
			$scope.selectedAll = '';
		} else {
			new Notification({message: 'product.form.productBlockSuccess', templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
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
			new Notification({message: 'product.form.productUnblockSuccessP' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
			for(var i = 0; i < $scope.products.length; i++) {
				if($.inArray($scope.products[i].product_id, data) > -1) {
					$scope.products[i].block = 0;
					$scope.removeSelection($scope.products[i].product_id);
				}
			}
			$scope.selectedAll = '';
		} else {
			new Notification({message: 'product.form.productUnblockSuccess' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
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
		var id="";
        if(Array.isArray(data)){
			for(var i = 0; i < $scope.products.length; i++) {
				if($.inArray($scope.products[i].product_id, data[0]) > -1) {
					$scope.products[i].status = "Imported";
					console.log($scope.products[id]);
        			$scope.removeSelection($scope.products[id].product_id);
              	    Notification({message: 'Imported' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
              	    break;
				}
			}
			new Notification({message: 're-Import for ASIN: '+data[2]+' success', templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
			$scope.selectedAll = '';
		} else {
			for(var i = 0; i < $scope.products.length; i++) {
				if($scope.products[i].product_id == data[0]) {
					$scope.products[id].status = "Imported";
          	        $scope.removeSelection($scope.products[id].product_id);
					break;
				}
			}
			new Notification({message: 're-Import for ASIN: '+data[2]+' success', templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
		}		
    });
    $scope.$on('product.forceSync', function (event, data) {
		$scope.loader = false;
		var id="";
        if(Array.isArray(data)){
			new Notification({message: 'Synchronised ASIN : '+data[2], templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
			for(var i = 0; i < $scope.products.length; i++) {
				if($.inArray($scope.products[i].product_id, data[0]) > -1) {
					console.log(data);
        			$scope.removeSelection($scope.products[id].product_id);
        			Notification({message: 'Synchronised' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
				}
			}
			$scope.selectedAll = '';
		} else {
			new Notification({message: 'Synchronised', templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
			
			
			for(var i = 0; i < $scope.products.length; i++) {
				if($scope.products[i].product_id == data[0]) {
					console.log(data);
          	        $scope.removeSelection($scope.products[id].product_id);
          	        new Notification({message: 'Synchronised' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
					break;
				}
			}
		}		
    });
});