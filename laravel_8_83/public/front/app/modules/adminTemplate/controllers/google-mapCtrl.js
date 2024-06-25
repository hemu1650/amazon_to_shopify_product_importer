"use strict";

var app = angular.module('ng-laravel', ['ngMap']);
app.controller('google-mapCtrl',function($scope,NgMap){
    NgMap.getMap().then(function(map) {

    });

});