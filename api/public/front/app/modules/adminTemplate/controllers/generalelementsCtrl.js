'use strict';

var app = angular.module('ng-laravel');
app.controller('generalelementsCtrl',function($scope){
    $scope.promptCallback= function(result){
        console.log('Click on Ok button and result:'+result)
    }
    $scope.promptCallbackCancelled = function(result){
        console.log('Click on Canel button and result:'+ result)
    }

    $scope.confirmCallbackMethod = function(attr1, attr2){
        console.log('Ok')
    }
    $scope.confirmCallbackCancel = function(attr1, attr2){
        console.log('Cancel')
    }
})