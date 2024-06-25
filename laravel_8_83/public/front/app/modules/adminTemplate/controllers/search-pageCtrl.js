"use strict";

var app = angular.module('ng-laravel');
app.controller('search-pageCtrl', function ($scope) {
    $scope.htmlopt1 = {
        fillColor: false,
        changeRangeMin: 0,
        chartRangeMax: 10,
        width: '100%',
        height: '78px',
        spotRadius: '5',
        lineWidth: '3',
        borderWidth: '4',
        lineColor: '#2b5c59'
    };
    $scope.valueopt1 = {
        composite: true,
        fillColor: false,
        changeRangeMin: 0,
        chartRangeMax: 10,
        spotRadius: '5',
        lineColor: '#3a6965'
    };
    $scope.value1 = [6, 4, 7, 8, 4, 3, 2, 2, 5, 6, 7, 4, 1, 5, 7, 9, 9, 8, 7, 6];


    $scope.htmlopt2={
        type: 'bar',
        barColor: '#aaf'
    };
    $scope.valueopt2={
        composite: true,
        fillColor: false,
        lineColor: 'red'
    };
    $scope.value2=[4,1,5,7,9,9,8,7,6,6,4,7,8,4,3,2,2,5,6,7];
});