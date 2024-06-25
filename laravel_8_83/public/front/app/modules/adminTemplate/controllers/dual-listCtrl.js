"use strict";

var app = angular.module('ng-laravel',['frapontillo.bootstrap-duallistbox']);
app.controller('dual-listCtrl',function($scope){

    //  Example with default values - Example1 model1
    $scope.list1 = [];

    var updateList1 = function() {
        $scope.list1.push({
            'id': '_1',
            'text': 'option1'
        },{
            'id': '_2',
            'text': 'option2'
        },{
            'id': '_3',
            'text': 'option3'
        },{
            'id': '_4',
            'text': 'option4'
        },{
            'id': '_5',
            'text': 'option5'
        },{
            'id': '_6',
            'text': 'option6'
        },{
            'id': '_7',
            'text': 'option7'
        },{
            'id': '_8',
            'text': 'option8'
        });
    };
    updateList1();


    // Example with custom settings - Example 2 model2
    $scope.list2 = [];

    var updateList2 = function() {
        $scope.list2.push({
            'id': '_1',
            'text': 'Cat1'
        },{
            'id': '_2',
            'text': 'Cat2'
        },{
            'id': '_3',
            'text': 'Cat3'
        },{
            'id': '_4',
            'text': 'Cat4'
        },{
            'id': '_5',
            'text': 'Cat5'
        },{
            'id': '_6',
            'text': 'Cat6'
        });
    };
    updateList2();

    $scope.settings = {
        nonSelectedListLabel: 'Non-selected',
        selectedListLabel: 'Selected',
        preserveSelection: 'moved',
        moveOnSelect: true,
        filterNonSelected: 'ion ([7-9]|[1][0-2])',
        bootstrap2: false,
        selectMinHeight: 1000,
        //filterClear: 'Show all!',
        //filterPlaceHolder: 'Filter!',
        //moveSelectedLabel: 'Move selected only',
        //moveAllLabel: 'Move all!',
        //removeSelectedLabel: 'Remove selected only',
        //removeAllLabel: 'Remove all!',
        //postfix: '_helperz',
        //filter: true,
        //filterSelected: '4',
        //infoAll: 'Showing all {0}!',
        //infoFiltered: '<span class="label label-warning">Filtered</span> {0} from {1}!',
        //infoEmpty: 'Empty list!',
        //filterValues: true
    };
});