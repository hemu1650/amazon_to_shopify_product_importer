'use strict';

angular.module('ng-laravel').service('SettingsService', function($rootScope, Restangular) {	
    
	var _settingsService = Restangular.all('settings');

    this.list = function() {
        // GET /api/settings
		var _settingsService1 = Restangular.one('settings');
        var data = _settingsService1.get();
		return data;
    };

    this.update = function(settings) {
        // POST /api/settings/		
        _settingsService.post(settings).then(function(data) {			
            $rootScope.$broadcast('settings.update');            
        },function(response) {
            $rootScope.$broadcast('settings.validationError',response.data.error);
        });
    };

	this.saveBuyNowSettings = function(buynowSettingsObj) {   
		_settingsService.customPOST(buynowSettingsObj, "buynow", {}, {}).then(function() {
            $rootScope.$broadcast('settings.buynowSettings');
        },function(response){			 
           $rootScope.$broadcast('settings.validationError', response.data.error);
        });
    };

	this.savePricingRulesSettings = function(pricingrulesSettingsObj) {   
		_settingsService.customPOST(pricingrulesSettingsObj, "pricingrules", {}, {}).then(function() {
            $rootScope.$broadcast('settings.pricingrulesSettings');
        },function(response){			 
           $rootScope.$broadcast('settings.validationError', response.data.error);
        });
    };

	this.saveSyncSettings = function(syncSettingsObj) {   
		_settingsService.customPOST(syncSettingsObj, "sync", {}, {}).then(function() {
            $rootScope.$broadcast('settings.syncSettings');
        },function(response){			 
           $rootScope.$broadcast('settings.validationError', response.data.error);
        });
    };

	this.saveReviewsSettings = function(reviewsSettingsObj) {   
		_settingsService.customPOST(reviewsSettingsObj, "reviews", {}, {}).then(function() {
            $rootScope.$broadcast('settings.reviewsSettings');
        },function(response){			 
           $rootScope.$broadcast('settings.validationError', response.data.error);
        });
    };
});