"use strict";

var app = angular.module('ng-laravel',['dropzone','mgo-angular-wizard']);
app.controller('UserImportCtrl',function($scope,UserService,RoleService,$http,$rootScope,WizardHandler,SweetAlert,$translatePartialLoader,trans){

    /*
     * Define initial value
     */
    $scope.importdata={};
    $scope.importdata.file='';
    $scope.notification={};
    $scope.mapForm={};



    /*
     * Wizard Controller
     * Step one: file uploaded validation
     */
    // get field name
    $scope.getFieldMap = function() {
        if($scope.importdata.file !='') {
            return UserService.fetchFields($scope.importdata.file).then(function (response) {
                $scope.importdata.columns = response.columns;
                $scope.importdata.fields = response.fields;
                return true;
            }, function (error) {
                console.log(error);
                return false;
            })
        }
        else {
            SweetAlert.swal($rootScope.selectFileError);//define in AdminCtrl
            return false;
        }
    };
    // validation to fill mapping form
    $scope.fieldMapValidation = function () {
        if ($scope.mapForm.name) {
            return true;
        } else {
            return false;
        }
    };
    // submit and import data
    $scope.importSubmit = function(){
        WizardHandler.wizard().next();
        $scope.mapForm.import_file_name = $scope.importdata.file;
        UserService.importData($scope.mapForm).then(function(response){
            WizardHandler.wizard().next();
            $scope.importdata.success_count = response.success_counter;
            $scope.importdata.fail_count = response.fail_counter;
            $rootScope.$broadcast('import.validationError',response.insert_error.error);
        });
    };



    /*
     * Dropzone file uploader initial
     */
    $scope.dropzoneConfig = {
        options: { // passed into the Dropzone constructor
            url: '../laravel-backend/public/api/uploadimage',
            paramName: "file", // The name that will be used to transfer the file
            maxFilesize: .5, // MB
            acceptedFiles: '.csv, .xls, .xlsx',
            maxFiles: 1,
            maxfilesexceeded: function (file) {
                this.removeAllFiles();
                this.addFile(file);
            },
            addRemoveLinks: true,
            dictDefaultMessage: '<i class="upload-icon fa fa-cloud-upload blue fa-3x"></i></br> To import data please select or darg & drop </br>XLS and CSV File',
            dictResponseError: 'Error while uploading file!',
        },
        'eventHandlers': {
            'removedfile': function (file,response) {
                $http({
                    method : "POST",
                    url : "../laravel-backend/public/api/deleteimage/"+$scope.importdata.file
                }).then(function mySucces(response) {
                    $scope.deleteMessage = response.data;
                    $scope.importdata.file='';
                });
            },
            'success': function (file, response) {
                $scope.importdata.file = response.filename;
            }
        }
    };


    /********************************************************
     * Event Listeners
     * user event listener related to UserCreateCtrl
     ********************************************************/

    //Validation error in create user event listener
    $scope.$on('import.validationError', function(event,errorData) {
        $scope.notification={status:true, message : errorData, type : 'warning'};
    });

});