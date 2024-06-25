"use strict";

var app = angular.module('ng-laravel');
app.controller('SampleCreateCtrl',function($scope,SampleService,$translatePartialLoader,Notification,trans){

    /*
     * Define initial value
     */
    $scope.sample={};
    $scope.sample.avatar_url='';


    /*
     * Create a Sample
     */
    $scope.create = function(sample) {
        $scope.isDisabled = true;
        SampleService.create(sample);
    };

    

    /********************************************************
     * Event Listeners
     * Sample event listener related to SampleCreateCtrl
     ********************************************************/
    // Create sample event listener
    $scope.$on('sample.create', function() {
        $scope.sample ={};
        Notification({message: 'category.form.categoryAddSuccess' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
        $scope.isDisabled = false;
    });

    //Validation error in create sample event listener
    $scope.$on('sample.validationError', function(event,errorData) {
        Notification({message: errorData ,templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'},'warning');
        $scope.isDisabled = false;
    });

});