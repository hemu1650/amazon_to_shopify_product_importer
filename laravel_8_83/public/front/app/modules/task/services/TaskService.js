'use strict';

angular.module('ng-laravel').service('TaskService', function($rootScope, Restangular,CacheFactory) {
    /*
     * Build collection /task
     */
    var _taskService = Restangular.all('task');
    if (!CacheFactory.get('tasksCache')) {
        var tasksCache = CacheFactory('tasksCache');
    }

    /*
     * Get list of tasks from cache.
     * if cache is empty, data fetched and cache create else retrieve from cache
     */
    this.cachedList = function() {
        // GET /api/task
        if (!tasksCache.get('list')) {
            return this.list();
        } else{
            return tasksCache.get('list');
        }
    };


    /*
     * Get list of tasks
     */
    this.list = function() {
        // GET /api/task
        var data = _taskService.getList();
        tasksCache.put('list',data);
        return data;
    };


    /*
     * Pagination change
     */
    this.pageChange = function(pageNumber,per_page) {
        // GET /api/task?page=2
        return _taskService.getList({page:pageNumber,per_page:per_page});
    };


    this.cachedShow = function(id) {
        // GET /api/task/:id
        if (!tasksCache.get('show'+id)) {
            return this.show(id);
        } else{
            return tasksCache.get('show'+id);
        }
    };

    /*
     * Show specific task by Id
     */
    this.show = function(id) {
        // GET /api/task/:id
        var data = _taskService.get(id);
        tasksCache.put('show'+id,data);
        return data;
    };


    /*
     * Create task (POST)
     */
    this.create = function(task) {
        // POST /api/task/:id
        _taskService.post(task).then(function(data) {
            $rootScope.$broadcast('task.create');
            console.log(data);
        },function(response) {
            $rootScope.$broadcast('task.validationError',response.data.error);
        });
    };


    /*
     * Update task (PUT)
     */
    this.update = function(task) {
        // PUT /api/task/:id
        task.put().then(function() {
            $rootScope.$broadcast('task.update');
        },function(response) {
            $rootScope.$broadcast('task.validationError',response.data.error);
        });
    };


    /*
     * Delete task
     * To delete multi record you should must use 'Restangular.several'
     */
    this.delete = function(selection) {
        // DELETE /api/task/:id
        Restangular.several('task',selection).remove().then(function() {
            $rootScope.$broadcast('task.delete');
        },function(response){
            $rootScope.$broadcast('task.not.delete');
        });
    };


    /*
     * Search in tasks
     */
    this.search = function(query,per_page) {
        // GET /api/task/search?query=test&per_page=10
        if(query !=''){
            return _taskService.customGETLIST("search",{query:query, per_page:per_page});
        }else{
            return _taskService.getList();
        }
    }


    /*
     * Download Exported File
     */
    this.downloadExport = function(recordType,selection,export_type){
        _taskService.withHttpConfig({responseType: 'blob'}).customGET('export/file',{record_type:recordType,export_type:export_type,'selection[]':selection}).then(function(response) {
            var url = (window.URL || window.webkitURL).createObjectURL(response);
            var anchor = document.createElement("a");
            document.body.appendChild(anchor);//required in FF, optional for Chrome
            anchor.download = "exportfile."+export_type;
            anchor.href = url;
            anchor.click();
        })
    };



});

