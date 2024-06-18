'use strict';

angular.module('ng-laravel').service('AmzConfigService', function($rootScope, Restangular) {	
    
    this.list = function() {
        // GET /api/amzconfig
		var _amzconfigService = Restangular.one('amzconfig');
        var data = _amzconfigService.get();  
		return data;
    };


    this.create = function(amzconfig) {
        // POST /api/amzconfig/
		var _amzconfigService = Restangular.all('amzconfig');
        _amzconfigService.post(amzconfig).then(function(data) {			
            $rootScope.$broadcast('amzconfig.create');            
        },function(response) {
            $rootScope.$broadcast('amzconfig.validationError', response.data.error);
        });
    };
});