'use strict';

angular.module('ng-laravel').service('SampleService', function($rootScope, Restangular) {
    /*
     * Build collection /sample
     */
    var _sampleService = Restangular.all('sample');


    /*
     * Get list of samples
     */
    this.list = function() {
        // GET /api/sample
        return _sampleService.getList();
    };


    /*
     * Pagination change
     */
    this.pageChange = function(pageNumber,per_page) {
        // GET /api/sample?page=2
        return _sampleService.getList({page:pageNumber,per_page:per_page});
    };


    /*
     * Show specific sample by Id
     */
    this.show = function(id) {
        // GET /api/sample/:id
        return _sampleService.get(id);
    };


    /*
     * Create sample (POST)
     */
    this.create = function(sample) {
        // POST /api/sample/:id
        _sampleService.post(sample).then(function() {
            $rootScope.$broadcast('sample.create');
        },function(response) {
            $rootScope.$broadcast('sample.validationError',response.data.error);
        });
    };


    /*
     * Update sample (PUT)
     */
    this.update = function(sample) {
        // PUT /api/sample/:id
        sample.put().then(function() {
            $rootScope.$broadcast('sample.update');
        },function(response) {
            $rootScope.$broadcast('sample.validationError',response.data.error);
        });
    };


    /*
     * Delete sample
     * To delete multi record you should must use 'Restangular.several'
     */
    this.delete = function(selection) {
        // DELETE /api/sample/id1,id2,...
        Restangular.several('sample',selection).remove().then(function() {
            $rootScope.$broadcast('sample.delete');
        });
    };


    /*
     * Search in samples
     */
    this.search = function(query,per_page) {
        // GET /api/sample/search?query=test&per_page=10
        if(query !=''){
            return _sampleService.customGETLIST("search",{query:query, per_page:per_page});
        }else{
            return _sampleService.getList();
        }
    }


});

