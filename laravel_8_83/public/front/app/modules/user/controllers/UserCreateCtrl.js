"use strict";

var app = angular.module('ng-laravel',['dropzone']);
app.controller('UserCreateCtrl',function($scope,UserService,RoleService,$http,$rootScope,resolvedItems,$translatePartialLoader,trans,Notification){


    /*
     * Define initial value
     */
    $scope.user={};
    $scope.user.avatar_url='';


    /*
     * Create a user
     */
    $scope.create = function(user) {
        $scope.isDisabled = true;
        UserService.create(user);
    };


    /*
     * Get all Roles
     * Get from resolvedItems function in this page route (config.router.js)
     */
    $scope.roles = resolvedItems;



    /*
     * Dropzone file uploader initial
     */
    $scope.dropzoneConfig = {
        options: { // passed into the Dropzone constructor
            url: '../laravel-backend/public/api/uploadimage',
            paramName: "file", // The name that will be used to transfer the file
            maxFilesize: .5, // MB
            acceptedFiles: 'image/jpeg,image/png,image/gif',
            maxFiles: 1,
            maxfilesexceeded: function (file) {
                this.removeAllFiles();
                this.addFile(file);
            },
            addRemoveLinks: true,
            dictDefaultMessage: '<i class="upload-icon fa fa-cloud-upload blue fa-3x"></i>',
            dictResponseError: 'Error while uploading file!',
        },
        'eventHandlers': {
            'removedfile': function (file,response) {
                $http({
                    method : "POST",
                    url : "../laravel-backend/public/api/deleteimage/"+$scope.user.avatar_url
                }).then(function mySucces(response) {
                    $scope.deleteMessage = response.data;
                    $scope.user.avatar_url='';
                });
            },
            'success': function (file, response) {
                $scope.user.avatar_url = response.filename;
            }
        }
    };


    /********************************************************
     * Event Listeners
     * user event listener related to UserCreateCtrl
     ********************************************************/
    // Create user event listener
    $scope.$on('user.create', function() {
        $scope.user ={};
        $rootScope.$broadcast('dropzone.removeallfile');
        Notification({message: 'user.form.userAddSuccess' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
        $scope.isDisabled = false;
    });

    //Validation error in create user event listener
    $scope.$on('user.validationError', function(event,errorData) {
        Notification({message: errorData ,templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'},'warning');
        $scope.isDisabled = false;
    });

});