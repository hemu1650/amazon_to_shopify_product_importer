"use strict";

var app = angular.module('ng-laravel', ['ui.select']);
app.controller('AmzConfigCreateCtrl', function($scope, AmzConfigService, $http, $rootScope, $translatePartialLoader, Notification, trans, $state, $timeout){
		
	/*
     * Define initial value
     */
	$scope.tmp = {};
    $scope.amzconfig = {country:'com'};
	$scope.marketplaceList = [{key:'com', value:'Amazon.com'}, {key:'ca', value:'Amazon.ca'}, {key:'co.uk', value:'Amazon.co.uk'}, {key:'in', value:'Amazon.in'}, {key:'com.br', value:'Amazon.com.br'}, {key:'com.mx', value:'Amazon.com.mx'}, {key:'de', value:'Amazon.de'}, {key:'es', value:'Amazon.es'}, {key:'fr', value:'Amazon.fr'}, {key:'it', value:'Amazon.it'}, {key:'co.jp', value:'Amazon.co.jp'}, {key:'cn', value:'Amazon.cn'}];
    $scope.awsdivflag = false;
    
	AmzConfigService.list().then(function(data){
		if(data != ''){
			$scope.amzconfig = data;
			//$scope.isDisabled = true;		
		}			
    });
	
    $scope.create = function(amzconfig) {
        $scope.isDisabled = true;
		$scope.tmp = angular.isObject(amzconfig) ? angular.toJson(amzconfig) : amzconfig;
        AmzConfigService.create($scope.tmp);
    };
	
    /********************************************************
     * Event Listeners
     * Task event listener related to TaskCreateCtrl
     ********************************************************/
    // Create task event listener
    $scope.$on('amzconfig.create', function() {
       // $scope.amzconfig = {};
     Notification({message: 'amzconfig.form.amzconfigAddSuccess' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
       //$scope.isDisabled = false;
	    $timeout(function() {
		  $scope.profile.amztoken = 1;
	      $state.go('admin');
      }, 2000);

    });

    //Validation error in create task event listener
    $scope.$on('amzconfig.validationError', function(event,errorData) {
        Notification({message: errorData ,templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'},'warning');
        $scope.isDisabled = false;
    });

});