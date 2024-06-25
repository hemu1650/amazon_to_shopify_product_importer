"use strict";

var app = angular.module('ng-laravel');
app.controller('SampleEditCtrl',function($scope,SampleService,$stateParams,Notification,$translatePartialLoader,trans){

    /*
     * Define initial value
     */
    $scope.url ='WHdlX62vMOo8buIoczxZs134Q.jpg';


    /*
     * Edit mode sample
     */
    SampleService.show($stateParams.id).then(function(sample) {
        $scope.sample = sample;
    });


    /*
     * Update sample
     */
    $scope.update = function(sample) {
        $scope.isDisabled = true;
        SampleService.update(sample);
    };




    /********************************************************
     * Event Listeners
     * Sample event listener related to SampleEditCtrl
     ********************************************************/
    // Edit sample event listener
    $scope.$on('sample.edit', function(scope, sample) {
        $scope.sample = sample;
    });

    // Update sample event listener
    $scope.$on('sample.update', function() {
        Notification({message: 'category.form.categoryAddSuccess' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
        $scope.isDisabled = false;
    });

    // Sample form validation event listener
    $scope.$on('sample.validationError', function(event,errorData) {
        Notification({message: errorData ,templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'},'warning');
        $scope.isDisabled = false;
    });
});

