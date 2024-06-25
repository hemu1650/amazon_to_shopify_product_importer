"use strict";

var app = angular.module('ng-laravel');
app.controller('CategoryCtrl',function($scope,CategoryService,SweetAlert,Restangular,$rootScope,resolvedItems,$translatePartialLoader,Notification,trans){

    /*
     * Define initial value
     */
    $scope.category={};


    /*
     * Get all categories
     * Get from resolvedItems function in this page route (config.router.js)
     */
    $scope.categories = resolvedItems;
    //$scope.pagination = $scope.categories.metadata;
    //$scope.maxSize = 5;


    /*
     * Get all category and refresh cache.
     * At first check cache, if exist, we return data from cache and if don't exist return from API
     */
    CategoryService.list().then(function(data){
        $scope.categories = data;
    });


    /*
     * Create a category
     */
    $scope.create = function(category) {
        $scope.notification={};
        $scope.isDisabled = true;
        CategoryService.create(category);
    };


    /*
     * Remove selected customers
     */
    $scope.delete = function(category) {
        SweetAlert.swal($rootScope.areYouSureDelete,//define in AdminCtrl
        function(isConfirm){
            if (isConfirm) {
                CategoryService.delete(category);
            }
        });
    };


    /*
     * Edit mode category - Copy category to edit form
     */
    $scope.edit = function(category) {
        var categoryCopy = Restangular.copy(category);
        $rootScope.$broadcast('category.edit', categoryCopy);
    };


    /*
     * Update category
     */
    $scope.update = function(category) {
        $scope.isDisabled = true;
        CategoryService.update(category);
    };


    /**********************************************************
     * Event Listener
     ***********************************************************/
    // Create category event listener
    $scope.$on('category.create', function() {
        CategoryService.list().then(function(data){
            $scope.categories = data;
            $scope.category={};
            Notification({message: 'category.form.categoryAddSuccess' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
            $scope.isDisabled = false;
        });
    });

    //Validation error in create category event listener
    $scope.$on('category.validationError', function(event,errorData) {
        Notification({message: errorData ,templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'},'warning');
        $scope.isDisabled = false;
    });

    // update list when category deleted
    $scope.$on('category.delete', function() {
        SweetAlert.swal($rootScope.recordDeleted);//define in AdminCtrl
        CategoryService.list().then(function(data){
            $scope.categories =data;
            $scope.selection=[];
        });
    });

    // update list when category not deleted
    $scope.$on('category.not.delete', function() {
        SweetAlert.swal($rootScope.recordNotDeleted);//define in AdminCtrl
        CategoryService.list().then(function(data){
            $scope.categories =data;
            $scope.selection=[];
        });
    });

    // copy category to form for update
    $scope.$on('category.edit', function(scope, category) {
        $scope.category = category;
        $scope.updateMode= true;
    });

    // Update category event listener
    $scope.$on('category.update', function() {
        Notification({message: 'category.form.categoryUpdateSuccess' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
        $scope.isDisabled = false;
        $scope.category={};
        CategoryService.list().then(function(data){
            $scope.categories = data;
        })
    });
});