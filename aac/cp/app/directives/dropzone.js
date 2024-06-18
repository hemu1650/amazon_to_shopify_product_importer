'use strict';
/*
 * Dropzone for single file uploader
 */
angular.module('dropzone', []).directive('dropzone', function ($timeout) {
    return {
        restrict:'AE',
        require: 'ngModel',
        link:function (scope, element, attrs, ngModel) {
            var init = function () {
                var config, dropzone, mode;

                config = scope[attrs.dropzone];
                mode = attrs.mode.toLowerCase();

                // create a Dropzone for the element with the given options
                Dropzone.autoDiscover = false;
                dropzone = new Dropzone(element[0], config.options);


                // Display existing files on server
                if(ngModel.$viewValue !=='' && ngModel.$viewValue !==undefined){
                    if(mode == 'single'){
                        var mockFile = {name: ngModel.$viewValue, size: 123};
                        dropzone.emit("addedfile", mockFile);
                        dropzone.createThumbnailFromUrl(mockFile, "../laravel-backend/public/uploads/" + ngModel.$viewValue);
                        dropzone.emit("complete", mockFile);
                    }
                    if(mode == 'multi'){
                        angular.forEach(ngModel.$viewValue,function(image,key){
                            var mockFile = {filename: image.filename, serverId:image.filename ,size: image.size};
                            dropzone.emit("addedfile", mockFile);
                            dropzone.createThumbnailFromUrl(mockFile, "../laravel-backend/public/uploads/" + image.filename);
                            dropzone.emit("complete", mockFile);
                            dropzone.files.push(mockFile);
                        })
                    }
                }

                // Form submit rest dropzone event handler
                scope.$on('dropzone.removeallfile', function() {
                    dropzone.removeAllFiles();
                });


                // bind the given event handlers
                angular.forEach(config.eventHandlers, function (handler, event) {
                    dropzone.on(event, handler);
                });
            };
            $timeout(init, 0);
        }
    }
});
