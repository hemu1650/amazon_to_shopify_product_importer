"use strict";

var app = angular.module('ng-laravel', ['ui.bootstrap','ui.select']);
app.controller('ReviewListCtrl', function ($scope,ReviewService,$state, ProductService, $stateParams, SweetAlert , $http, $translatePartialLoader,CategoryService,$rootScope,trans,Notification) {
    console.log('calling for data');
	$scope.loader = true;
	$scope.asin = $stateParams.id;
	$scope.i = 0;
	$scope.images = [];
	$scope.units = [
        {'id': 10, 'label': '10'},
		{'id': 20, 'label': '20'},
        {'id': 50, 'label': '50'},
        {'id': 100, 'label': '100'},
    ]
    $scope.perPage = $scope.units[1];
    $scope.maxSize = 3;
    
    
	//console.log($scope.asin);
	ReviewService.list($scope.asin).then(function(data){
		//console.log(data);
		$scope.loader = true;
		$scope.reviews = data;
		console.log(data);
		if($scope.reviews.length == 0){
		    SweetAlert.swal("No Reviews","Reviews have not been fetched for the selected product. Please initiate a Fetch Reviews request.","info");
		    $state.go('admin.reviewproducts');
		}
		$scope.pagination = $scope.reviews.metadata;
		
		if($scope.perPage.id < $scope.pagination.total) {
    		$scope.start = 1;
    		$scope.end = $scope.perPage.id;
    	} else {
    		$scope.start = 1;
    		$scope.end = $scope.pagination.total;
    	}
	
		console.log($scope.pagination);
		$scope.loader = false;
	},function(response){
	    console.log(response);
	});
    

    
    //console.log($scope.pagination);

    
	
    $scope.splitArr = function(imgArr){
        console.log("Splitting Images");
        var arr = imgArr.split("|")
            return [arr[0]];
    }
    
    $scope.splitArrA = function(imgArr){
        console.log("Splitting Multiple Return Images");
        var arr = imgArr.split("|")
        $scope.images = arr;
        console.log($scope.images);
            return arr;
    }
    
    $scope.left = function(){
        if($scope.images.length > $scope.i){
            $scope.i = $scope.i + 1;
        }else{
            $scope.i = 0;
        }
    }
    $scope.right = function(){
        if(0 < $scope.i){
            $scope.i = $scope.i - 1;
        }else{
            $scope.i = $scope.images.length;
        }
    }
	
	$scope.publish = function(review){
	    ReviewService.publish(review).then(function(data){
	        $scope.reviews = data.data;
	        $scope.pagination = data;
	        Notification({message: 'Published' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
	    },function(response){
	        Notification({message: 'Validation Error' ,templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'},'Error');
	    });
	}
	
	$scope.exportAllReviews = function(review){
	    ReviewService.exportReviews(review).then(function(data){
	        window.open(data[0], '_self');
	        console.log(data);
	    },function(response){
	        console.log(response);
	    });
	}
	$scope.unpublish = function(review){
	    ReviewService.unpublish(review).then(function(data){
	        $scope.reviews = data.data;
	        $scope.pagination = data;
	        Notification({message: 'Un-Published' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
	    },function(response){
	        Notification({message: 'Validation Error' ,templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'},'warning');
	    });
	}

	$scope.pageChanged = function (per_page) {
		$scope.loader = true;
		ReviewService.pageChange($stateParams.id,$scope.pagination.current_page, per_page.id).then(function (data) {
            $scope.reviews = data;
            console.log(data);
            $scope.pagination = $scope.reviews.metadata;
                if(($scope.pagination.current_page * per_page.id) < $scope.pagination.total) {
					$scope.start = $scope.pagination.current_page * per_page.id - per_page.id + 1;
					$scope.end = $scope.pagination.current_page * per_page.id;			
				} else {
					$scope.start = $scope.pagination.current_page * per_page.id - per_page.id + 1;
					$scope.end = $scope.pagination.total;	
				}
            $scope.maxSize = 3;
            //console.log($scope.reviews);
            $scope.loader = false;
        });
	}
	
	$scope.refetchAllReviews = function(){
        console.log("fetching all reviews");
		ReviewService.reFetchAllReviews($stateParams.id).then(function(data){
            //console.log(data);
            Notification({message: data[0] ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
        },function(response){
            Notification({message: "ValidationError" ,templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'},'warning');
        });
	}

	$scope.destroy = function(key,id){
	    SweetAlert.swal($rootScope.areYouSureDelete,
    	function(isConfirm){
        	if (isConfirm) {
            	ReviewService.destroy(key,id).then(function(data){
            	    //console.log(data);
            	    $scope.reviews = data.data;
            	    $scope.pagination = data;
        			SweetAlert.swal($rootScope.recordDeleted);
        		},function(response){
        			SweetAlert.swal($rootScope.recordNotDeleted);
        		});
            }
        });
	}

});