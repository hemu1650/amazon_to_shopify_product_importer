'use strict';

angular.module('ng-laravel').service('PermissionService', function($rootScope, Restangular,CacheFactory) {
    /*
     * Build collection /permission
     */
    var _permissionService = Restangular.all('permission');
    if (!CacheFactory.get('permissionCache')) {
        var permissionCache = CacheFactory('permissionCache');
    }

    /*
     * Get list of permissions from cache.
     * if cache is empty, data fetched and cache create else retrieve from cache
     */
    this.cachedList = function() {
        // GET /api/permission
        if (!permissionCache.get('list')) {
            return this.list();
        } else{
            return permissionCache.get('list');
        }
    };


    /*
     * Get list of permissions
     */
    this.list = function() {
        // GET /api/permission
        var data = _permissionService.getList();
        permissionCache.put('list',data);
        return data;
    };


    /*
     * Get All list of permissions without pagination
     */
    this.all = function() {
        // GET /api/permission
        return _permissionService.getList({per_page:1000});
    };

    /*
     * Pagination change
     */
    this.pageChange = function(pageNumber,per_page) {
        // GET /api/permission?page=2
        return _permissionService.getList({page:pageNumber,per_page:per_page});
    };


    /*
     * Show specific permission by Id
     */
    this.show = function(id) {
        // GET /api/permission/:id
        return _permissionService.get(id);
    };


    /*
     * Create permission (POST)
     */
    this.create = function(permission) {
        // POST /api/permission/:id
        _permissionService.post(permission).then(function() {
            $rootScope.$broadcast('permission.create');
        },function(response) {
            $rootScope.$broadcast('permission.validationError',response.data.error);
        });
    };


    /*
     * Update permission (PUT)
     */
    this.update = function(permission) {
        // PUT /api/permission/:id
        permission.put().then(function() {
            $rootScope.$broadcast('permission.update');
        },function(response) {
            $rootScope.$broadcast('permission.validationError',response.data.error);
        });
    };


    /*
     * Delete permission
     * To delete multi record you should must use 'Restangular.several'
     */
    this.delete = function(selection) {
        // DELETE /api/permission/:id
        Restangular.several('permission',selection).remove().then(function() {
            $rootScope.$broadcast('permission.delete');
        },function(response){
            $rootScope.$broadcast('permission.not.delete');
        });
    };


    /*
     * Search in permissions
     */
    this.search = function(query,per_page) {
        // GET /api/permission/search?query=test&per_page=10
        if(query !=''){
            return _permissionService.customGETLIST("search",{query:query, per_page:per_page});
        }else{
            return _permissionService.getList();
        }
    }


});

