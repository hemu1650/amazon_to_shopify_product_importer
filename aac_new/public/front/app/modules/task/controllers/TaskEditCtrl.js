"use strict";

var app = angular.module('ng-laravel',['dropzone','ui.select']);
app.controller('TaskEditCtrl',function($scope,TaskService,$stateParams,$http,CategoryService,UserService,$rootScope,TagService,resolvedItems,$translatePartialLoader,Notification,trans){


    /*
     * Define initial value
     */
    $scope.statusList = [{id:0,name:'Open'},{id:1,name:'Close'}];



    /*
     * Edit mode Task
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
     * Update task
     */
    $scope.update = function(task) {
        $scope.isDisabled = true;

        task.start_date = $scope.date.startDate;
        task.end_date = $scope.date.endDate;
        TaskService.update(task);
    };

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
            tag: newTag,
        };
        return item;
    };


    /*
     * Dropzone file uploader initial
     */
    $scope.dropzoneConfig = {
        options: { // passed into the Dropzone constructor
            url: '../laravel-backend/public/api/uploadimage',
            paramName: "file", // The name that will be used to transfer the file
            maxFilesize: .5, // MB
            acceptedFiles: 'image/jpeg,image/png,image/gif',
            maxFiles: 8,
            maxfilesexceeded: function (file) {
                this.removeAllFiles();
                this.addFile(file);
            },
            addRemoveLinks: true,
            dictDefaultMessage :
                '<span class="bigger-150 bolder"><i class=" fa fa-caret-right red"></i> Drop files</span> to upload \
                <span class="smaller-80 grey">(or click)</span> <br /> \
                <i class="upload-icon fa fa-cloud-upload blue fa-3x"></i>',
            dictResponseError: 'Error while uploading file!',
        },
        'eventHandlers': {
            'removedfile': function (file,response) {
                $http({
                    method : "POST",
                    url : "../laravel-backend/public/api/deleteimage/"+file.serverId,
                }).then(function mySuccess(response) {
                    $scope.deleteMessage = response.data;
                    removeByAttr($scope.task.gallery, file.serverId);
                });
            },
            'success': function (file, response) {
                file.serverId = response.filename;
                file.serverSize = response.size;
                $scope.task.gallery.push({filename:response.filename,size:response.size});
            }
        }
    };

    // remove array object by attribute value
    var removeByAttr = function(arr, value){
        for (var i = 0; i < arr.length; i++) {
            if (arr[i].filename && arr[i].filename === value) {
                arr.splice(i, 1);
                break;
            }
        }
    };


    /********************************************************
     * Event Listeners
     * Task event listener related to TaskEditCtrl
     ********************************************************/
    // Edit task event listener
    $scope.$on('task.edit', function(scope, task) {
        $scope.task = task;
    });

    // Update task event listener
    $scope.$on('task.update', function() {
        Notification({message: 'task.form.taskUpdatedSuccess' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
        $scope.isDisabled = false;
    });

    // task form validation event listener
    $scope.$on('task.validationError', function(event,errorData) {
        Notification({message: errorData ,templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'},'warning');
        $scope.isDisabled = false;
    });
});

