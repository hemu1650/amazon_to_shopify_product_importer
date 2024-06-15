'use strict';

angular.module('ng-laravel').service('CategoryService', function($rootScope, Restangular,CacheFactory) {
    /*
     * Build collection /category
     */
    var _categoryService = Restangular.all('category');
    if (!CacheFactory.get('categoryCache')) {
        var categoryCache = CacheFactory('categoryCache');
    }

    /*
     * Get list of category from cache.
     * if cache is empty, data fetched and cache create else retrieve from cache
     */
    this.cachedList = function() {
        // GET /api/category
        if (!categoryCache.get('list')) {
            return this.list();
        } else{
            return categoryCache.get('list');
        }
    };


    /*
     * Get list of category
     */
    this.list = function() {
        // GET /api/category
        var data = _categoryService.getList();
        categoryCache.put('list',data);
        return data;
    };


    /*
     * Pagination change
     */
    this.pageChange = function(pageNumber,per_page) {
        // GET /api/category?page=2
        return _categoryService.getList({page:pageNumber,per_page:per_page});
    };


    /*
     * Show specific category by Id
     */
    this.show = function(id) {
        // GET /api/category/:id
        return _categoryService.get(id);
    };


    /*
     * Create category (POST)
     */
    this.create = function(category) {
        // POST /api/category/:id
        _categoryService.post(category).then(function() {
            $rootScope.$broadcast('category.create');
        },function(response) {
            $rootScope.$broadcast('category.validationError',response.data.error);
        });
    };


    /*
     * Update category (PUT)
     */
    this.update = function(category) {
        // PUT /api/category/:id
        category.put().then(function() {
            $rootScope.$broadcast('category.update');
        },function(response) {
            $rootScope.$broadcast('category.validationError',response.data.error);
        });
    };


    /*
     * Delete category
     * To delete multi record you should must use 'Restangular.several'
     */
    this.delete = function(category) {
        // DELETE /api/category/:id
        category.remove().then(function() {
            $rootScope.$broadcast('category.delete');
        },function(response){
            $rootScope.$broadcast('category.not.delete');
        });
    };


    /*
     * Search in categorys
     */
    this.search = function(query,per_page) {
        // GET /api/category/search?query=test&per_page=10
        if(query !=''){
            return _categoryService.customGETLIST("search",{query:query, per_page:per_page});
        }else{
            return _categoryService.getList();
        }
    }


});

