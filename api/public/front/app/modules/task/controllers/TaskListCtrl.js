"use strict";

var app = angular.module('ng-laravel', ['ui.bootstrap']);
app.controller('TaskListCtrl', function ($scope, TaskService, SweetAlert, resolvedItems,$translatePartialLoader,$rootScope,trans) {

    /*
     * Define initial value
     */
    $scope.query = '';
    $scope.radioValue=1;


    /*
     * Get all Tasks
     * Get from resolvedItems function in this page route (config.router.js)
     */
    $scope.tasks = resolvedItems;
    $scope.pagination = $scope.tasks.metadata;
    $scope.maxSize = 5;


    /*
     * Get all Task and refresh cache.
     * At first check cache, if exist, we return data from cache and if don't exist return from API
     */
    TaskService.list().then(function (data) {
        $scope.tasks = data;
        $scope.pagination = $scope.tasks.metadata;
    });


    /*
     * Remove selected tasks
     */
    $scope.delete = function (task) {
        SweetAlert.swal($rootScope.areYouSureDelete,//define in AdminCtrl,
        function (isConfirm) {
            if (isConfirm) {
                TaskService.delete(task);
            }
        });
    };


    /*
     * Pagination task list
     */
    $scope.units = [
        {'id': 10, 'label': 'Show 10 Item Per Page'},
        {'id': 15, 'label': 'Show 15 Item Per Page'},
        {'id': 20, 'label': 'Show 20 Item Per Page'},
        {'id': 30, 'label': 'Show 30 Item Per Page'},
    ]
    $scope.perPage = $scope.units[0];
    $scope.pageChanged = function (per_page) {
        TaskService.pageChange($scope.pagination.current_page, per_page.id).then(function (data) {
            $scope.tasks = data;
            $scope.pagination = $scope.tasks.metadata;
            $scope.maxSize = 5;
        });
    };


    /*
     * Search in tasks
     */
    $scope.search = function (per_page) {
        TaskService.search($scope.query, per_page.id).then(function (data) {
            $scope.tasks = data;
            $scope.pagination = $scope.tasks.metadata;
            $scope.maxSize = 5;
        });
    };

    /*
     * Export CSV
     */
    $scope.export = function (selection,export_type) {
        SweetAlert.swal($rootScope.exportSelect,
            function (isConfirm) {
                if (isConfirm) {
                    var recordType = $("input[name=exportSelect]:checked").val();
                    TaskService.downloadExport(recordType,selection,export_type);
                }
            });
    };



    /**********************************************************
     * Event Listener
     **********************************************************/
        // Get list of selected task to do actions
    $scope.selection = [];
    $scope.toggleSelection = function toggleSelection(taskId) {
        // toggle selection for a given task by Id
        var idx = $scope.selection.indexOf(taskId);
        // is currently selected
        if (idx > -1) {
            $scope.selection.splice(idx, 1);
        }
        // is newly selected
        else {
            $scope.selection.push(taskId);
        }
    };

    // update list when task deleted
    $scope.$on('task.delete', function () {
        SweetAlert.swal($rootScope.recordDeleted);//define in AdminCtrl
        TaskService.list().then(function (data) {
            $scope.tasks = data;
            $scope.selection = [];
        });
    });

    // update list when task not deleted
    $scope.$on('task.not.delete', function () {
        SweetAlert.swal($rootScope.recordNotDeleted);//define in AdminCtrl
        TaskService.list().then(function (data) {
            $scope.tasks = data;
            $scope.selection = [];
        });
    });


});
