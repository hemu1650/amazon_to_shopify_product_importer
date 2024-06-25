"use strict";

var app = angular.module('ng-laravel');
app.controller('morrischartCtrl',function($scope,$timeout){

    /* Line chart data and options */
    $scope.chartOptions1 = {
        xkey : 'period',
        ykeys : ['a'],
        labels : ['Series A'],
        units : '%',
        pointStrokeColors: ['black'],
        lineColors:['#2196F3'],
        pointFillColors: ['#00ff00'],
        lineWidth:[3],
        pointSize:[3],
        hideHover:'always'
    }
    $scope.chartData1 =
        [{
        "period" : "2011-08-12",
        "a" : 100
    }, {
        "period" : "2011-03-03",
        "a" : 75
    }, {
        "period" : "2010-08-08",
        "a" : 50
    }, {
        "period" : "2010-05-10",
        "a" : 25
    }, {
        "period" : "2010-03-14",
        "a" : 0
    }, {
        "period" : "2010-01-10",
        "a" : -25
    }, {
        "period" : "2009-12-10",
        "a" : -50
    }, {
        "period" : "2009-10-07",
        "a" : -75
    }, {
        "period" : "2009-09-25",
        "a" : -100
    }];


    /* Donut chart data and options */
    $scope.chartData2 = [
        {value: 70, label: 'foo'},
        {value: 15, label: 'bar'},
        {value: 10, label: 'baz'},
        {value: 5, label: 'A really really long label'}
    ];
    $scope.chartOptions2 = {
        formatter: function (x) { return x + "%"}
    }

    /* Area chart data and options */
    $scope.chartData3 = [
        {period: '2010 Q1', iphone: 2666, ipad: null, itouch: 2647},
        {period: '2010 Q2', iphone: 2778, ipad: 2294, itouch: 2441},
        {period: '2010 Q3', iphone: 4912, ipad: 1969, itouch: 2501},
        {period: '2010 Q4', iphone: 3767, ipad: 3597, itouch: 5689},
        {period: '2011 Q1', iphone: 6810, ipad: 1914, itouch: 2293},
        {period: '2011 Q2', iphone: 5670, ipad: 4293, itouch: 1881},
        {period: '2011 Q3', iphone: 4820, ipad: 3795, itouch: 1588},
        {period: '2011 Q4', iphone: 15073, ipad: 5967, itouch: 5175},
        {period: '2012 Q1', iphone: 10687, ipad: 4460, itouch: 2028},
        {period: '2012 Q2', iphone: 8432, ipad: 5713, itouch: 1791}
    ];
    $scope.chartOptions3 = {
        xkey: 'period',
        ykeys: ['iphone', 'ipad'],
        labels: ['iPhone', 'iPad'],
        behaveLikeLine: true,
        resize: true,
        pointStrokeColors: ['black'],
        lineColors:['#2196F3'],
        pointFillColors: ['#00ff00'],
        lineWidth:[1],
        pointSize:[.5],
        hideHover:'always'
    }


    /* Bar chart style one data and options */
    $scope.chartData4 = [
        {x: '2011 Q1', y: 0},
        {x: '2011 Q2', y: 1},
        {x: '2011 Q3', y: 2},
        {x: '2011 Q4', y: 3},
        {x: '2012 Q1', y: 4},
        {x: '2012 Q2', y: 5},
        {x: '2012 Q3', y: 6},
        {x: '2012 Q4', y: 7},
        {x: '2013 Q1', y: 8}
    ];
    $scope.chartOptions4 = {
        xkey: 'x',
        ykeys: ['y'],
        labels: ['Y'],
        barColors: function (row, series, type) {
            if (type === 'bar') {
                var red = Math.ceil(255 * row.y / this.ymax);
                return 'rgb(' + red + ',0,0)';
            }
            else {
                return '#000';
            }
        }
    };


    /* Bar chart style two data and options */
    $scope.chartData5 = [
        {"period": "2012-10-01", "licensed": 3407, "sorned": 660},
        {"period": "2012-09-30", "licensed": 3351, "sorned": 629},
        {"period": "2012-09-29", "licensed": 3269, "sorned": 618},
        {"period": "2012-09-20", "licensed": 3246, "sorned": 661},
        {"period": "2012-09-19", "licensed": 3257, "sorned": 667},
        {"period": "2012-09-18", "licensed": 3248, "sorned": 627},
        {"period": "2012-09-17", "licensed": 3171, "sorned": 660},
        {"period": "2012-09-16", "licensed": 3171, "sorned": 676},
        {"period": "2012-09-15", "licensed": 3201, "sorned": 656},
        {"period": "2012-09-10", "licensed": 3215, "sorned": 622}
    ];
    $scope.chartOptions5 = {
        xkey: 'period',
        ykeys: ['licensed', 'sorned'],
        labels: ['Licensed', 'SORN'],
        xLabelAngle: 60,
        barColors:['#2196F3','#3F51B5']
    }


    /* Stack Bar chart style one data and options */
    $scope.chartData6 =[
        {x: '2011 Q1', y: 3, z: 2, a: 3},
        {x: '2011 Q2', y: 2, z: null, a: 1},
        {x: '2011 Q3', y: 0, z: 2, a: 4},
        {x: '2011 Q4', y: 2, z: 4, a: 3}
    ];
    $scope.chartOptions6 = {
        xkey: 'x',
        ykeys: ['y', 'z', 'a'],
        labels: ['Y', 'Z', 'A'],
        stacked: true,
        barColors:['#2196F3','#3F51B5','#00BCD4']
    };

    /* Line chart style one data and options */
    $scope.chartData7 =[
        {"period": "2011 W27", "licensed": 3407, "sorned": 660},
        {"period": "2011 W26", "licensed": 3351, "sorned": 629},
        {"period": "2011 W25", "licensed": 3269, "sorned": 618},
        {"period": "2011 W24", "licensed": 3246, "sorned": 661},
        {"period": "2011 W23", "licensed": 3257, "sorned": 667},
        {"period": "2011 W22", "licensed": 3248, "sorned": 627},
        {"period": "2011 W21", "licensed": 3171, "sorned": 660},
        {"period": "2011 W20", "licensed": 3171, "sorned": 676},
        {"period": "2011 W19", "licensed": 3201, "sorned": 656},
        {"period": "2011 W18", "licensed": 3215, "sorned": 622},
        {"period": "2011 W17", "licensed": 3148, "sorned": 632},
        {"period": "2011 W16", "licensed": 3155, "sorned": 681},
        {"period": "2011 W15", "licensed": 3190, "sorned": 667},
        {"period": "2011 W14", "licensed": 3226, "sorned": 620},
        {"period": "2011 W13", "licensed": 3245, "sorned": null},
        {"period": "2011 W12", "licensed": 3289, "sorned": null},
        {"period": "2011 W11", "licensed": 3263, "sorned": null},
        {"period": "2011 W10", "licensed": 3189, "sorned": null},
        {"period": "2011 W09", "licensed": 3079, "sorned": null},
        {"period": "2011 W08", "licensed": 3085, "sorned": null},
        {"period": "2011 W07", "licensed": 3055, "sorned": null},
        {"period": "2011 W06", "licensed": 3063, "sorned": null},
        {"period": "2011 W05", "licensed": 2943, "sorned": null},
        {"period": "2011 W04", "licensed": 2806, "sorned": null},
        {"period": "2011 W03", "licensed": 2674, "sorned": null},
        {"period": "2011 W02", "licensed": 1702, "sorned": null},
        {"period": "2011 W01", "licensed": 1732, "sorned": null}
    ];
    $scope.chartOptions7 = {
        xkey: 'period',
        ykeys: ['licensed', 'sorned'],
        labels: ['Licensed', 'SORN'],
        events: [
            '2011-04',
            '2011-08'
        ],
        pointStrokeColors: ['black'],
        lineColors:['#2196F3'],
        pointFillColors: ['#00ff00'],
        lineWidth:[2],
        pointSize:[.5],
        hideHover:'always'
    };


    /* Real time chart data and option */
    function getRandom(min, max) {
        return Math.random() * (max - min) + min;
    }
    var nReloads = 0;
    function dataFake(offset) {
        var ret = [];
        for (var x = 0; x <= 100; x += 10) {
            var v = (offset + x) % 100;
            ret.push({
                x: x,
                y: Math.sin(Math.PI * v / 50).toFixed(4),
                z: Math.cos(Math.PI * v / 50).toFixed(4)
            });
        }
        return ret;
    };

    $scope.chartData8 = dataFake(0);
    $scope.chartOptions8 = {
        xkey: 'x',
        ykeys: ['y', 'z'],
        labels: ['sin()', 'cos()'],
        parseTime: false,
        ymin: -1.0,
        ymax: 1.0,
        pointStrokeColors: ['black'],
        lineColors:['#2196F3'],
        pointFillColors: ['#00ff00'],
        lineWidth:[2],
        pointSize:[2],
        hideHover:'always'
    };
    function update() {
        nReloads++;
        $scope.chartData8 = dataFake(5 * nReloads);
        //console.error($scope.chartData8);
        $timeout(update, 500);
    }

    update()




});