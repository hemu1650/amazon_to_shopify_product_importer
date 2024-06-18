'use strict';

angular.module('ng-laravel').service('ContactService', function($rootScope, Restangular) {	
    
	this.sendRequest = function(contact) {
        // POST /api/contact
		var _contactService = Restangular.all('contact/requestquote');
        _contactService.post(contact).then(function(data) {
            $rootScope.$broadcast('contact.sendrequest');            
        },function(response) {
			//alert(JSON.stringify(response));
            $rootScope.$broadcast('contact.validationError',response.data.error);
        });
    };
});