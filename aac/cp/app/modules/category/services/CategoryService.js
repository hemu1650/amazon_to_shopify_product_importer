'use strict';

angular.module('ng-laravel').service('CategoryService', function($rootScope, Restangular) {
    /*
     * Build collection /tag
     */
    var _categoryService = Restangular.all('category');


    /*
     * Get list of tag
     */
    this.list = function() {
        // GET /api/tag
        return _categoryService.getList();
    };



    /*
     * Show specific tag by Id
     */
    this.show = function(id) {
        // GET /api/tag/:id
        return _categoryService.get(id);
    };


    
});

