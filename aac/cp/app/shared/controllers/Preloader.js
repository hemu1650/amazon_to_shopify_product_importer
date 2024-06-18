"use strict";

var app = angular.module('ng-laravel');
app.controller('Preloader',function($scope,$rootScope){

    $scope.$on('signinBegin', function() {
        $scope.loader = true;
    });

    $scope.$on('signinError', function() {
        $scope.loader = false;
    });
});