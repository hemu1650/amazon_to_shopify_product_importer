"use strict";

var app = angular.module('ng-laravel',['ui.bootstrap']);
app.controller('UserListCtrl',function($scope,UserService,SweetAlert,resolvedItems,$translatePartialLoader,$translate,$rootScope,trans){

    /*
     * Define initial value
     */
    $scope.query ='';



    /*
     * Get all users
     * Get from resolvedItems function in this page route (config.router.js)
     */
    $scope.users = resolvedItems;
    $scope.pagination = $scope.users.metadata;
    $scope.maxSize = 5;


    /*
     * Get all Task and refresh cache.
     * At first check cache, if exist, we return data from cache and if don't exist return from API
     */
    UserService.list().then(function(data){
        $scope.users = data;
        $scope.pagination = $scope.users.metadata;
    });


    /*
     * Remove selected users
     */
    $scope.delete = function(user) {
        SweetAlert.swal($rootScope.areYouSureDelete,
        function(isConfirm){
            if (isConfirm) {
                UserService.delete(user);
            }
        });
    };




    /*
     * Pagination user list
     */
    $scope.units = [
        {'id': 10, 'label': 'Show 10 Item Per Page'},
        {'id': 15, 'label': 'Show 15 Item Per Page'},
        {'id': 20, 'label': 'Show 20 Item Per Page'},
        {'id': 30, 'label': 'Show 30 Item Per Page'},
    ]
    $scope.perPage= $scope.units[0];
    $scope.pageChanged = function(per_page) {
        UserService.pageChange($scope.pagination.current_page,per_page.id).then(function(data){
            $scope.users = data;
            $scope.pagination = $scope.users.metadata;
            $scope.maxSize = 5;
        });
    };


    /*
     * Search in users
     */
    $scope.search = function(query,per_page) {
        UserService.search(query,per_page.id).then(function(data){
            $scope.users = data;
            $scope.pagination = $scope.users.metadata;
            $scope.maxSize = 5;
        });
    };

    /*
     * Download Export
     */
    $scope.export = function (selection,export_type) {
        SweetAlert.swal($rootScope.exportSelect,
            function (isConfirm) {
                if (isConfirm) {
                    var recordType = $("input[name=exportSelect]:checked").val();
                    UserService.downloadExport(recordType,selection,export_type);
                }
            });
    };


    /**********************************************************
     * Event Listener
     **********************************************************/
        // Get list of selected user to do actions
    $scope.selection=[];
    $scope.toggleSelection = function toggleSelection(userId) {
        // toggle selection for a given user by Id
        var idx = $scope.selection.indexOf(userId);
        // is currently selected
        if (idx > -1) {
            $scope.selection.splice(idx, 1);
        }
        // is newly selected
        else {
            $scope.selection.push(userId);
        }
    };

    // update list when user deleted
    $scope.$on('user.delete', function() {
        SweetAlert.swal($rootScope.recordDeleted);//define in AdminCtrl
        UserService.list().then(function(data){
            $scope.users =data;
            $scope.selection=[];
        });
    });

    // update list when user not deleted
    $scope.$on('user.not.delete', function() {
        SweetAlert.swal($rootScope.recordNotDeleted);//define in AdminCtrl
        UserService.list().then(function(data){
            $scope.users =data;
            $scope.selection=[];
        });
    });



});
