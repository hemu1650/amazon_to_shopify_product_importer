"use strict";

var app = angular.module('ng-laravel');
app.controller('image-cropCtrl',function($scope){
    $scope.jcropOption1 = {
        setSelect: [ 346, 39, 166, 259 ]
    }
});