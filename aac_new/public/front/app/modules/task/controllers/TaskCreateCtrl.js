"use strict";

var app = angular.module('ng-laravel',['dropzone','ui.select']);
app.controller('TaskCreateCtrl',function($scope,TaskService,CategoryService,UserService,$http,$rootScope,TagService,$translatePartialLoader,Notification,trans){


    /*
     * Define initial value
     */
    $scope.tmp = {};
    $scope.task={};
    $scope.task.gallery=[];
    $scope.statusList = [{id:0,name:'Open'},{id:1,name:'Close'}];


    /*
     * Create a task
     */
    $scope.create = function(task) {
        $scope.isDisabled = true;

        task.start_date = $scope.date.startDate;
        task.end_date = $scope.date.endDate;
        $scope.tmp = angular.isObject(task) ? angular.toJson(task) : task;
        TaskService.create($scope.tmp);
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
            tag: newTag
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
                if($scope.task.gallery.length > 0) {
                    $http({
                        method: "POST",
                        url: "../laravel-backend/public/api/deleteimage/" + file.serverId,
                    }).then(function mySuccess(response) {
                        $scope.deleteMessage = response.data;
                        removeByAttr($scope.task.gallery, file.serverId);
                    });
                }
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
     * Task event listener related to TaskCreateCtrl
     ********************************************************/
    // Create task event listener
    $scope.$on('task.create', function() {
        $scope.task ={};
        $scope.task.gallery =[];
        $scope.task.tags =[];
        $rootScope.$broadcast('dropzone.removeallfile');
        Notification({message: 'task.form.taskAddSuccess' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
        $scope.isDisabled = false;
    });

    //Validation error in create task event listener
    $scope.$on('task.validationError', function(event,errorData) {
        Notification({message: errorData ,templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'},'warning');
        $scope.isDisabled = false;
    });

});