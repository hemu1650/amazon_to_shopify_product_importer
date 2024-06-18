"use strict";

var app = angular.module('ng-laravel', ['ui.select']);
app.controller('ContactCtrl', function($scope, ContactService, $http, $rootScope, $translatePartialLoader, Notification, trans, $state, $timeout){
			
    $scope.sendRequest = function(contact) {
        $scope.isDisabled = true;
		$scope.tmp = angular.isObject(contact) ? angular.toJson(contact) : contact;
        ContactService.sendRequest($scope.tmp);
    };  

    /********************************************************
     * Event Listeners
     * Task event listener related to ContactCtrl
     ********************************************************/
    // Send contact request event listener
    $scope.$on('contact.sendrequest', function() {
		Notification({message: 'Request has been submitted successfully. We will get back to you in few hours.' ,templateUrl:'app/vendors/angular-ui-notification/tpl/success.tpl.html'},'success');
		$scope.contact = {'servicetype':'integration'};
		$scope.isDisabled = false;
    });

    //Validation error in send contact request event listener
    $scope.$on('contact.validationError', function(event,errorData) {
        Notification({message: errorData ,templateUrl:'app/vendors/angular-ui-notification/tpl/validation.tpl.html'},'warning');
        $scope.isDisabled = false;
    });

});