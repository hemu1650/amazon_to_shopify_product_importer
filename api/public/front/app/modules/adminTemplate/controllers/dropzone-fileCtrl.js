"use strict";

var app = angular.module('ng-laravel');
app.controller('dropzone-fileCtrl',function($scope){

    $scope.dropzoneOption1 = {
        paramName: "file", // The name that will be used to transfer the file
        maxFilesize: .5, // MB
        addRemoveLinks : true,
        dictDefaultMessage :
            '<span class="bigger-150 bolder"><i class=" fa fa-caret-right red"></i> Drop files</span> to upload \
            <span class="smaller-80 grey">(or click)</span> <br /> \
            <i class="upload-icon fa fa-cloud-upload blue fa-3x"></i>',
        dictResponseError: 'Error while uploading file!',
    };

    $scope.dropzoneOption2 ={
        url: "../dummy.html",
        paramName: "file", // The name that will be used to transfer the file
        maxFilesize:50, // MB
        maxFiles: 1,
        maxfilesexceeded: function(file) {
            this.removeAllFiles();
            this.addFile(file);
        },
        addRemoveLinks : true,
        dictDefaultMessage :'<i class="upload-icon fa fa-cloud-upload blue fa-3x"></i>',
        dictResponseError: 'Error while uploading file!'
    }


    $scope.dropzoneOption3 ={
        url: "../dummy.html",
        paramName: "file", // The name that will be used to transfer the file
        maxFilesize:.5, // MB
        maxFiles: 1,
        clickable:'#upload',
        createImageThumbnails:false,
        maxfilesexceeded: function(file) {
            this.removeAllFiles();
            this.addFile(file);
        },
        addRemoveLinks : true,
        dictDefaultMessage:'',
        dictRemoveFile:'x',
        dictCancelUpload:'x',
        dictResponseError: 'Error while uploading file!',
        previewTemplate:'<div class="dz-preview dz-file-preview">\
                            <div class="dz-image"><img data-dz-thumbnail=""></div>\
                            <div class="dz-details">\
                                <div class="dz-size"><span data-dz-size=""></span></div>\
                                <div class="dz-filename">\
                                    <span data-dz-name=""></span>\
                                </div>\
                            </div>\
                            <div class="dz-progress">\
                                <span class="dz-upload" data-dz-uploadprogress=""></span>\
                            </div>\
                            <div class="dz-error-message">\
                                <span data-dz-errormessage=""></span>\
                            </div>\
                            <div class="dz-success-mark"><i class="fa fa-check success"></i></div>\
                            <div class="dz-error-mark"><i class="fa fa-exclamation-triangle red"></i></div>\
                        </div>'
    }

}).directive('dropzone', function () {
    return function (scope, element, attrs) {
        var config, dropzone;

        config = scope[attrs.dropzone];

        // create a Dropzone for the element with the given options
        dropzone = new Dropzone(element[0], config.options);

        // bind the given event handlers
        angular.forEach(config.eventHandlers, function (handler, event) {
            dropzone.on(event, handler);
        });
    };
});