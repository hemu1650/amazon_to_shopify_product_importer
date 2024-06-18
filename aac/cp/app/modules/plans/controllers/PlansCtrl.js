"use strict";

var app = angular.module('ng-laravel');
app.controller('PlansCtrl', function($scope, $http, $rootScope, $translatePartialLoader, Notification, trans, $state, $timeout){
		
	$scope.upgrade = function(plantype) {
		var key = $scope.profile.tempcode;
        window.location.href = 'https://shopify.infoshore.biz/aac/upgrade1.php?key='+key+'&type='+plantype;
    };
});