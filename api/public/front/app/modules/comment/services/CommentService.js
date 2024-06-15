'use strict';

angular.module('ng-laravel').service('CommentService', function($rootScope, Restangular) {
    /*
     * Build collection /comment
     */
    var _commentService = Restangular.all('comment');


    /*
     * Get list of comments
     */
    this.list = function() {
        // GET /api/comment
        return _commentService.getList();
    };


    /*
     * Pagination change
     */
    this.pageChange = function(pageNumber,per_page) {
        // GET /api/comment?page=2
        return _commentService.getList({page:pageNumber,per_page:per_page});
    };


    /*
     * Show specific comment by Id
     */
    this.show = function(id) {
        // GET /api/comment/:id
        return _commentService.get(id);
    };


    /*
     * Create comment (POST)
     */
    this.create = function(comment) {
        // POST /api/comment/:id
        _commentService.post(comment).then(function() {
            $rootScope.$broadcast('comment.create');
        },function(response) {
            $rootScope.$broadcast('comment.validationError',response.data.error);
        });
    };


    /*
     * Update comment (PUT)
     */
    this.update = function(comment) {
        // PUT /api/comment/:id
        comment.put().then(function() {
            $rootScope.$broadcast('comment.update');
        },function(response) {
            $rootScope.$broadcast('comment.validationError',response.data.error);
        });
    };


    /*
     * Delete comment
     * To delete multi record you should must use 'Restangular.several'
     */
    this.delete = function(selection) {
        // DELETE /api/comment/:id
        Restangular.several('comment',selection).remove().then(function() {
            $rootScope.$broadcast('comment.delete');
        });
    };


    /*
     * Search in comments
     */
    this.search = function(query,per_page) {
        // GET /api/comment/search?query=test&per_page=10
        if(query !=''){
            return _commentService.customGETLIST("search",{query:query, per_page:per_page});
        }else{
            return _commentService.getList();
        }
    }


});

