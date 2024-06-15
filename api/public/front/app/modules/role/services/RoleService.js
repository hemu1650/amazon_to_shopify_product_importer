'use strict';

angular.module('ng-laravel').service('RoleService', function($rootScope, Restangular, CacheFactory) {
    /*
     * Build collection /role
     */
    var _roleService = Restangular.all('role');
    if (!CacheFactory.get('rolesCache')) {
        var rolesCache = CacheFactory('rolesCache');
    }

    /*
     * Get list of roles from cache.
     * if cache is empty, data fetched and cache create else retrieve from cache
     */
    this.cachedList = function() {
        // GET /api/role
        if (!rolesCache.get('list')) {
            return this.list();
        } else{
            return rolesCache.get('list');
        }
    };


    /*
     * Get list of roles
     */
    this.list = function() {
        // GET /api/role
        var data = _roleService.getList();
        rolesCache.put('list',data);
        return data;
    };


    /*
     * Pagination change
     */
    this.pageChange = function(pageNumber,per_page) {
        // GET /api/role?page=2
        return _roleService.getList({page:pageNumber,per_page:per_page});
    };


    /*
     * Show specific role by Id
     */
    this.cachedShow = function(id) {
        // GET /api/role/:id
        if (!rolesCache.get('show'+id)) {
            return this.show(id);
        } else{
            return rolesCache.get('show'+id);
        }
    };

    
    /*
     * Show specific role by Id
     */
    this.show = function(id) {
        // GET /api/role/:id
        var data = _roleService.get(id);
        rolesCache.put('show'+id,data);
        return data;
    };



    /*
     * Create role (POST)
     */
    this.create = function(role) {
        // POST /api/role/:id
        console.log(role);
        _roleService.post(role).then(function(data) {
            $rootScope.$broadcast('role.create');
        },function(response) {
            $rootScope.$broadcast('role.validationError',response.data.error);
        });
    };


    /*
     * Update role (PUT)
     */
    this.update = function(role) {
        // PUT /api/role/:id
        role.put().then(function() {
            $rootScope.$broadcast('role.update');
        },function(response) {
            $rootScope.$broadcast('role.validationError',response.data.error);
        });
    };


    /*
     * Delete role
     * To delete multi record you should must use 'Restangular.several'
     */
    this.delete = function(selection) {
        // DELETE /api/role/:id
        Restangular.several('role',selection).remove().then(function(data) {
            $rootScope.$broadcast('role.delete');
        },function(response){
            $rootScope.$broadcast('role.not.delete');
        });
    };


    /*
     * Search in roles
     */
    this.search = function(query,per_page) {
        // GET /api/role/search?query=test&per_page=10
        if(query !=''){
            return _roleService.customGETLIST("search",{query:query, per_page:per_page});
        }else{
            return _roleService.getList();
        }
    }


});

