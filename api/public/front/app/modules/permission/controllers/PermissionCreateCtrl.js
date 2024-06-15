"use strict";

var app = angular.module('ng-laravel');
app.controller('PermissionCreateCtrl',function($scope,PermissionService,$http,$rootScope,$translatePartialLoader,Notification,trans){

    /*
     * Define initial value
     */
    $scope.permission={};


    /*
     * Create a permission
     */
    $scope.create = function(permission) {
        $scope.isDisabled = true;
        PermissionService.create(permission);
    };




    /********************************************************
     * Event Listeners
     * Permission event listener related to PermissionCreateCtrl
     ********************************************************/
    // Create permission event listener
    $scope.$on('permission.create', function() {
        $scope.permission ={};
        Notification({message: 'permission.form.permissionAddSuccess' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
        $scope.isDisabled = false;
    });

    //Validation error in create Permission event listener
    $scope.$on('permission.validationError', function(event,errorData) {
        Notification({message: errorData ,templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'},'warning');
        $scope.isDisabled = false;
    });

});