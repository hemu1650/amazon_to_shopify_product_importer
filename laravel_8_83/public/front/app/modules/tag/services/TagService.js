'use strict';

angular.module('ng-laravel').service('TagService', function($rootScope, Restangular) {
    /*
     * Build collection /tag
     */
    var _tagService = Restangular.all('tag');


    /*
     * Get list of tag
     */
    this.list = function() {
        // GET /api/tag
        return _tagService.getList();
    };



    /*
     * Show specific tag by Id
     */
    this.show = function(id) {
        // GET /api/tag/:id
        return _tagService.get(id);
    };


    /*
     * Search in tags
     */
    this.search = function(query) {
        // GET /api/tag/search?query=test
        if(query !=''){
            return _tagService.customGETLIST("search",{query:query});
        }else{
            return _tagService.getList();
        }
    }


});

