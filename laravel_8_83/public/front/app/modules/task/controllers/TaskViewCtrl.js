"use strict";

var app = angular.module('ng-laravel',['ui.select']);
app.controller('TaskViewCtrl',function($scope,TaskService,$stateParams,$http,CategoryService,UserService,$rootScope,TagService,CommentService,resolvedItems,$translatePartialLoader,trans){

    /*
     * Define initial value
     */
    $scope.statusList = [{id:0,name:'Open'},{id:1,name:'Close'}];

    /*
     * Show task
     * Get from resolvedItems function in this page route (config.router.js)
     */
    $scope.task = resolvedItems;
    $scope.date = {
        startDate: resolvedItems.start_date,
        endDate: resolvedItems.end_date
    };


    /*
     * Get task and refresh cache.
     * At first check cache, if exist, we return data from cache and if don't exist return from API
     */
    TaskService.show($stateParams.id).then(function(data) {
        $scope.task = data;
        $scope.date = {
            startDate: data.start_date,
            endDate: data.end_date
        };
    });


    /*
     * Get all Category
     */
    CategoryService.list().then(function(data){
        $scope.categories = data;
    });


    /*
     * Get all Customers
     */
    UserService.list().then(function(data){
        $scope.users = data;
    });


    /*
     * Get all Tags
     */
    TagService.list().then(function(data){
        $scope.tags = data;
    });
    // create new tag
    $scope.tagTransform = function (newTag) {
        var item = {
            id: 0,
            tag: newTag
        };
        return item;
    };

    /*
     * Comment Insert
     */
    $scope.createComment = function(comment) {
        $scope.isDisabled = true;
        CommentService.create(comment);
    };

    /*
     * Comment Delete
     */
    $scope.deleteComment = function(id) {
        CommentService.delete(id);
    };


/********************************************************
 * Event Listeners
 * Task event listener related to TaskEditCtrl
 ********************************************************/
    // Edit task event listener
    $scope.$on('task.edit', function(scope, task) {
        $scope.task = task;
    });

    $scope.$on('comment.create', function() {
        TaskService.show($stateParams.id).then(function(task) {
            $scope.task = task;
            $scope.comment.comment_text = '';
            $scope.isDisabled = false;
        });
    });

    $scope.$on('comment.delete', function() {
        TaskService.show($stateParams.id).then(function(task) {
            $scope.task = task;
        });
    });

});

