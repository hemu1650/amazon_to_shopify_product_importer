'use strict';

angular.module('ng-laravel').service('ReviewService', function($rootScope,$stateParams, Restangular,CacheFactory) {
    /*
     * Build collection /review
     */
    var _reviewService = Restangular.all('review');
    if (!CacheFactory.get('reviewsCache')) {
        var reviewsCache = CacheFactory('reviewsCache');
    }
    
    
    /*
     * Get list of reviews from cache.
     * if cache is empty, data fetched and cache create else retrieve from cache
     */
    this.cachedList = function(id) {
        // GET /api/review
       // console.log(id);
        if (!reviewsCache.get('list')) {
            console.log('fetching review from server');
            return this.list(id);
        } else{
            console.log('fetching from list');
            return reviewsCache.get('list');
        }
    };
    
    this.downloadAllSelected = function(selection){
        return Restangular.several('review/downloadAllSelected', [selection]).post();
    }
    this.fetchAllSelected =  function(selection){
        return Restangular.several('review/fetchAllSelected', [selection]).post();
    }
    /*
     * Get list of reviews
     */
     this.list1 = function() {
        // GET /api/review
        //console.log($stateParams.id);
        var data = _reviewService.GETList();
        reviewsCache.put('list1',data);
        return data;
    };

    this.list = function(id) {
        // GET /api/review
        console.log('generating list');
        console.log({id:id});
        var data = _reviewService.getList({id:id});
        reviewsCache.put('list',data);
        console.log('list generated');
        return data;
    };
	
	
	
	this.addreview = function(prodObj) {   
		return _reviewService.customPOST(prodObj, "add", {}, {});
    };

    this.fetchReviews = function(review){
        return _reviewService.customPOST({id:review},'fetchReviews',{}, {});
    }
    
    this.reFetchAllReviews = function(review){
        return _reviewService.customPOST({id:review},'refetchAmzReviews',{}, {});
    }

    this.destroy = function(key,id) {
        return Restangular.several('review/destroy', [key,id]).post();
    };
    this.publish = function(review) {
        return Restangular.several('review/publish', review).post();
    };
    this.unpublish = function(review) {
        return Restangular.several('review/unpublish', review).post();
    };
    
    this.exportReviews = function(review){
        return Restangular.several('review/export',review).post();
    }
	
    /*
     * Pagination change
     */
    this.pageChange = function(id,pageNumber, per_page) {
        // GET /api/review?page=2
	   return _reviewService.getList({id:id,page:pageNumber,per_page:per_page});
    };
	
	this.amzpageChange = function(keyword, category, per_page, pageNumber, end, pagination) {
        // GET /api/review?page=2		
		if(end == pagination) {
			pageNumber = 1;
		    return _reviewService.getList({keyword:keyword, category:category,page:pageNumber,per_page:per_page});
		} else {
		    return _reviewService.getList({keyword:keyword, category:category,page:pageNumber,per_page:per_page});
		}
    };

    this.show = function(id){
        var data = _reviewService.get(id);
        reviewsCache.put('list'+id,data);
        return data;
    }

    this.update = function(review){
		return _reviewService.customPOST(review, "update", {}, {});
    }

    /*
     * Search in review
     */
    this.search = function(id, query, per_page, pageNumber, end, pagination) {
		// GET /api/review/search?query=test&per_page=10
        if(query !=''){
            if(end == pagination) {
				pageNumber = 1;
            	return _reviewService.customGETLIST("search",{id:id, page:pageNumber, query:query, per_page:per_page});
			} else {
				return _reviewService.customGETLIST("search",{id:id, page:pageNumber, query:query, per_page:per_page});	
			}
        } else {
            return _reviewService.getList({id:id,page:pageNumber,per_page:per_page});
        }
    }
	
	 /*
     * Search in review
     */ 
    this.asin_search = function(keyword, category, per_page, pageNumber, end, pagination) {
   		if(keyword !=''){
            if(end == pagination) {
                pageNumber = 1;
                return _reviewService.customGETLIST("asin_search",{page:pageNumber, keyword:keyword, category:category, per_page:per_page});
			} else {
                return _reviewService.customGETLIST("asin_search",{page:pageNumber, keyword:keyword, category:category, per_page:per_page});	
			}
        } else {
            return _reviewService.getList({page:pageNumber,per_page:per_page});
        }
	};	

	/*
     * reimport review 
     */
    this.reimport = function(selection) {
		Restangular.several('review/reimport',selection).post().then(function(response) {
			$rootScope.$broadcast('review.reimport', selection);
        }, function(response){
			$rootScope.$broadcast('review.validationError', response.data.error);
        });
    };

	/*
     * block review
     */
    this.block = function(selection) {  
	    Restangular.several('review/block', selection).post().then(function() {
		    $rootScope.$broadcast('review.block', selection);
        }, function(response){
			$rootScope.$broadcast('review.validationError',response.data.error);
        });
    };

	/*
     * unblock review
     */
    this.unblock = function(selection) {  
	    Restangular.several('review/unblock', selection).post().then(function() {
		    $rootScope.$broadcast('review.unblock', selection);
        }, function(response){
			$rootScope.$broadcast('review.validationError',response.data.error);
        });
    };
});