"use strict";

var app = angular.module('ng-laravel');
app.controller('jquery-sparklinesCtrl', function ($scope) {

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
    }
    $scope.valueopt1 = {
        composite: true,
        fillColor: false,
        changeRangeMin: 0,
        chartRangeMax: 10,
        spotRadius: '5',
        lineColor: '#3a6965'
    }
    $scope.value1 =[6, 4, 7, 8, 4, 3, 2, 2, 5, 6, 7, 4, 1, 5, 7, 9, 9, 8, 7, 6];


    $scope.htmlopt2={
        fillColor: false,
        changeRangeMin: 0,
        chartRangeMax: 10
    };
    $scope.valueopt2={
        composite: true,
        fillColor: false,
        lineColor: 'red',
        changeRangeMin: 0,
        chartRangeMax: 10
    };
    $scope.value2=[4,1,5,7,9,9,8,7,6,6,4,7,8,4,3,2,2,5,6,7];


    $scope.htmlopt3={
        type: 'bar',
        barColor: '#aaf'
    };
    $scope.valueopt3={
        composite: true,
        fillColor: false,
        lineColor: 'red'
    };
    $scope.value3=[4,1,5,7,9,9,8,7,6,6,4,7,8,4,3,2,2,5,6,7];

});

