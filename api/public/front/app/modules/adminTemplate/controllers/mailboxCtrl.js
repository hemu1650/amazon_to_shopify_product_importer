"use strict";

var app = angular.module('ng-laravel');
app.controller('mailboxCtrl',function($scope){
    $scope.summernoteOption ={
        height: 140,   //set editable area's height
        codemirror: { // codemirror options
            theme: 'monokai'
        }
    }
}).directive('yepmail', function () {
        return {
            restrict: "A",
            link: function (scope, element, attrs) {
                var emailList = element.find('.email-list');
                var emailCompose = element.find('.email-compose');
                var emailMessage = element.find('.email-message');

                var table = element.find('table');
                var composeBtn = element.find('.compose-btn');
                var myinbox = element.find('#inbox');
                var triggerMessageClose = element.find('.trigger-message-close');

                var showMessage = function(){
                    emailList.hide();
                    emailCompose.hide();
                    emailMessage.show();
                };
                var showCompose = function(){
                    emailList.hide();
                    emailMessage.hide();
                    emailCompose.show();
                };
                var showList = function(){
                    emailCompose.hide();
                    emailMessage.hide();
                    emailList.show();
                };

                composeBtn.on('click',function(){
                    showCompose();
                });

                table.on('click','tr',function(){
                    showMessage();
                    //alert('Ok!');
                });

                myinbox.on('click',function(){
                    showList();
                });

                triggerMessageClose.on('click',function(){
                    showList();
                });

            }
        };
    });
