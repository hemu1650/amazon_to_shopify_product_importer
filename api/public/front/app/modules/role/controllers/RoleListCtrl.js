"use strict";

var app = angular.module('ng-laravel',['ui.bootstrap']);
app.controller('RoleListCtrl',function($scope,RoleService,SweetAlert,resolvedItems,$translatePartialLoader,$rootScope,trans){


    /*
     * Define initial value
     */
    $scope.query ='';


    /*
     * Get all Roles
     * Get from resolvedItems function in this page route (config.router.js)
     */
    $scope.roles = resolvedItems;
    $scope.pagination = $scope.roles.metadata;
    $scope.maxSize = 5;


    /*
     * Get all Task and refresh cache.
     * At first check cache, if exist, we return data from cache and if don't exist return from API
     */
    RoleService.list().then(function(data){
        $scope.roles = data;
        $scope.pagination = $scope.roles.metadata;
    });

    /*
     * Remove selected roles
     */
    $scope.delete = function(role) {
        SweetAlert.swal($rootScope.areYouSureDelete,//define in AdminCtrl,
        function(isConfirm){
            if (isConfirm) {
                RoleService.delete(role);
            }
        });
    };


    /*
     * Pagination role list
     */
    $scope.units = [
        {'id': 10, 'label': 'Show 10 Item Per Page'},
        {'id': 15, 'label': 'Show 15 Item Per Page'},
        {'id': 20, 'label': 'Show 20 Item Per Page'},
        {'id': 30, 'label': 'Show 30 Item Per Page'},
    ];
    $scope.perPage= $scope.units[0];
    $scope.pageChanged = function(per_page) {
        RoleService.pageChange($scope.pagination.current_page,per_page.id).then(function(data){
            $scope.roles = data;
            $scope.pagination = $scope.roles.metadata;
            $scope.maxSize = 5;
        });
    };


    /*
     * Search in roles
     */
    $scope.search = function(per_page) {
        RoleService.search($scope.query,per_page.id).then(function(data){
            $scope.roles = data;
            $scope.pagination = $scope.roles.metadata;
            $scope.maxSize = 5;
        });
    };


    /**********************************************************
     * Event Listener
     **********************************************************/
    // Get list of selected role to do actions
    $scope.selection=[];
    $scope.toggleSelection = function toggleSelection(roleId) {
        // toggle selection for a given role by Id
        var idx = $scope.selection.indexOf(roleId);
        // is currently selected
        if (idx > -1) {
            $scope.selection.splice(idx, 1);
        }
        // is newly selected
        else {
            $scope.selection.push(roleId);
        }
    };

    // update list when role deleted
    $scope.$on('role.delete', function() {
        SweetAlert.swal($rootScope.recordDeleted);//define in AdminCtrl
        RoleService.list().then(function(data){
            $scope.roles =data;
            $scope.selection=[];
        });
    });

    // update list when role not deleted
    $scope.$on('role.not.delete', function() {
        SweetAlert.swal($rootScope.recordNotDeleted);//define in AdminCtrl
        RoleService.list().then(function(data){
            $scope.roles =data;
            $scope.selection=[];
        });
    });



});
