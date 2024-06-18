"use strict";

var app = angular.module('ng-laravel', ['ui.bootstrap','ui.select']);
app.controller('ReviewEditCtrl', function ($scope,$state,ReviewService, ProductService, $stateParams, SweetAlert , $http, $translatePartialLoader,CategoryService,$rootScope,trans,Notification) {

	$scope.loader = false;
	//console.log($stateParams.id);
	ReviewService.show($stateParams.id).then(function(data){
		//console.log(data);
		$scope.review = data;
		
		//$scope.pagination = $scope.reviews.metadata;
	});
    
    //console.log(resolvedItems);
    
    //console.log($scope.pagination);

    /*$scope.units = [
        {'id': 10, 'label': '10'},
		{'id': 20, 'label': '20'},
        {'id': 50, 'label': '50'},
        {'id': 100, 'label': '100'},
    ]
    $scope.perPage = $scope.units[1];
    $scope.maxSize = 3;*/
    
    $scope.validate = function(){
        if(isNaN(Date.parse($scope.review.reviewDate))){
            alert("please enter date in valid format [DD-MMM-YYYY]");
            return false;
        }else if(isNaN(parseInt($scope.review.rating))){
            alert("please enter valid rating in digits only");
            return false;
        }else if(parseInt($scope.review.rating) < 0){
            alert("please enter valid rating between 0-5");
            return false;
        }else if(parseInt($scope.review.rating) > 5){
            alert("please enter valid rating between 0-5");
            return false;
        }else{
            return true;
        }
    }
    
    $scope.update = function(review){
        $scope.loader = true;
        if($scope.validate()){
        	ReviewService.update(review).then(function(data){
        	    $scope.loader = false;
        	    console.log(review);
        		SweetAlert.swal('Review Updated Successfully');
        		$state.go("admin.reviews",{id:review.product_asin});
        	},function(response){
        		SweetAlert.swal('Review not Updated');
        	});
        }else{
            $scope.loader = false;
        }
    }
    
    $scope.cancle = function(review){
		//console.log(review);
        $state.go("admin.reviews",{id:review.product_asin});
    }

});
