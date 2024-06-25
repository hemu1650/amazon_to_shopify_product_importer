"use strict";

var app = angular.module('ng-laravel',['xeditable','ui.bootstrap','checklist-model']);
app.controller('form-pluginsCtrl',function($scope,$filter,$http,$q){
    $scope.fromDateOptions = {
        defaultDate: "+1w",
        changeMonth: true,
        numberOfMonths: 3,
        prevText: '<i class="fa fa-chevron-left"></i>',
        nextText: '<i class="fa fa-chevron-right"></i>',
        onClose: function (selectedDate) {
            $("#to").datepicker("option", "maxDate", selectedDate);
        }
    };

    $scope.toDateOptions = {
        defaultDate: "+1w",
        changeMonth: true,
        numberOfMonths: 3,
        prevText: '<i class="fa fa-chevron-left"></i>',
        nextText: '<i class="fa fa-chevron-right"></i>',
        onClose: function (selectedDate) {
            $("#from").datepicker("option", "minDate", selectedDate);
        }
    };

    // x-editable sample
    $scope.user = {
        name: 'superuser',
        family:'',
        status: 2,
        group: 4,
        groupName: 'Admin', // original value,
        desc: 'Awesome user \ndescription!',
        state: 'Arizona',
        fruit: [2, 3],
        gender: 2,
        time: new Date(2016, 1, 15, 19, 20),
        date: '2016/01/01',
        range: 52
    };

    $scope.users = [
        {id: 1, name: 'awesome user1', status: 2, group: 4, groupName: 'admin'},
        {id: 2, name: 'awesome user2', status: undefined, group: 3, groupName: 'vip'},
        {id: 3, name: 'awesome user3', status: 2, group: null}
    ];



    // Local Validation
    $scope.checkName = function(data) {
        if (data == '') {
            return "This filed is required!";
        }
    };

    // Remote validation
    $scope.checkRemote = function(data) {
        var d = $q.defer();
        $http.post('/checkName', {value: data}).success(function(res) {
            res = res || {};
            if(res.status === 'ok') { // {status: "ok"}
                d.resolve()
            } else { // {status: "error", msg: "Username should be `awesome`!"}
                d.resolve(res.msg)
            }
        }).error(function(e) {
            d.reject('Server error!');
        });
        return d.promise;
    };

    // Select, local array, custom display
    $scope.statuses = [
        {value: 1, text: 'Status1'},
        {value: 2, text: 'Status2'},
        {value: 3, text: 'Status3'},
        {value: 4, text: 'Status4'}
    ];
    $scope.showStatus = function() {
        var selected = $filter('filter')($scope.statuses, {value: $scope.user.status});
        return ($scope.user.status && selected.length) ? selected[0].text : 'Not set';
    };


    // Select, remote array, no buttons
    $scope.groups = [];
    $scope.loadGroups = function() {
        return $scope.groups.length ? null : $http.get('/groups').success(function(data) {
            $scope.groups = data;
        });
    };
    $scope.$watch('user.group', function(newVal, oldVal) {
        if (newVal !== oldVal) {
            var selected = $filter('filter')($scope.groups, {id: $scope.user.group});
            $scope.user.groupName = selected.length ? selected[0].text : null;
        }
    });

    $scope.showGroup = function(user) {
        if(user.group && $scope.groups.length) {
            var selected = $filter('filter')($scope.groups, {id: user.group});
            return selected.length ? selected[0].text : 'Not set';
        } else {
            return user.groupName || 'Not set';
        }
    };

    $scope.saveUser = function(data, id) {
        //$scope.user not updated yet
        angular.extend(data, {id: id});
        return $http.post('/saveUser', data);
    };

    // remove user
    $scope.removeUser = function(index) {
        $scope.users.splice(index, 1);
    };

    // add user
    $scope.addUser = function() {
        $scope.inserted = {
            id: $scope.users.length+1,
            name: '',
            status: null,
            group: null
        };
        $scope.users.push($scope.inserted);
    };


    // e-typeahead
    $scope.states = ['Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California', 'Colorado', 'Connecticut', 'Delaware', 'Florida', 'Georgia', 'Hawaii', 'Idaho', 'Illinois', 'Indiana', 'Iowa', 'Kansas', 'Kentucky', 'Louisiana', 'Maine', 'Maryland', 'Massachusetts', 'Michigan', 'Minnesota', 'Mississippi', 'Missouri', 'Montana', 'Nebraska', 'Nevada', 'New Hampshire', 'New Jersey', 'New Mexico', 'New York', 'North Dakota', 'North Carolina', 'Ohio', 'Oklahoma', 'Oregon', 'Pennsylvania', 'Rhode Island', 'South Carolina', 'South Dakota', 'Tennessee', 'Texas', 'Utah', 'Vermont', 'Virginia', 'Washington', 'West Virginia', 'Wisconsin', 'Wyoming'];


    // Checklist x-editable
    $scope.fruits = [
        {value: 1, text: 'Apple'},
        {value: 2, text: 'Orange'},
        {value: 3, text: 'Banana'}
    ];
    $scope.showFruit = function() {
        var selected = [];
        angular.forEach($scope.fruits, function(s) {
            if ($scope.user.fruit.indexOf(s.value) >= 0) {
                selected.push(s.text);
            }
        });
        return selected.length ? selected.join(', ') : 'Not set';
    };


    // Radio button
    $scope.genders = [
        {value: 1, text: 'Male'},
        {value: 2, text: 'Female'}
    ];

    $scope.showGender = function() {
        var selected = $filter('filter')($scope.genders, {value: $scope.user.gender});
        return ($scope.user.gender && selected.length) ? selected[0].text : 'Not set';
    };

});


