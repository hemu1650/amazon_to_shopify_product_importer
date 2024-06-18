'use strict';

angular.module('ng-laravel').service('ProductService', function ($rootScope, Restangular, CacheFactory) {
    /*
     * Build collection /product
     */
    var _incompleteProductService = Restangular.all('product/incompleteProducts');
    var _productService = Restangular.all('product');
    var _importService = Restangular.all('import');
    if (!CacheFactory.get('productsCache')) {
        var productsCache = CacheFactory('productsCache');
    }
    /*
     * Get list of products from cache.
     * if cache is empty, data fetched and cache create else retrieve from cache
     */
    this.cachedList = function () {

        // GET /api/product
        if (!productsCache.get('list')) {

            return this.list();
        } else {

            return productsCache.get('list');
        }
    };


    this.getProductCount = function () {
        return _productService.customPOST({}, 'count', {}, {});
    }

    this.setCacheList = function (data) {
        productsCache.put('list2', data);
    }
    this.cachedList2 = function () {
        if (!productsCache.get('list2')) {
            //console.log('finding data in server');
            return [];
        } else {
            //console.log('finding data in cachelist 2');
            return productsCache.get('list2');
        }
    }
    this.cachedShow = function (id) {
        if (!productsCache.get('show' + id)) {
            return this.show(id);
        } else {
            return productsCache.get('show' + id);
        }
    };

    this.update = function (product) {
        Restangular.all('product').customPOST(product, "update1").then(function () {
            $rootScope.$broadcast('product.update', product);
        }, function (response) {
            $rootScope.$broadcast('product.validationError', response.data.error);
        });
    };

    this.show = function (id) {
        var data = _productService.get(id);
        productsCache.put('show' + id, data);
        return data;
    };

    this.importHistory = function () {
        return _importService.getList();
    }

    this.incompleteProductHistory = function () {
        return _incompleteProductService.getList();
    }

    this.addFieldList = function (product) {
        Restangular.all('product').customPOST(product, "updateList").then(function () {
            $rootScope.$broadcast('product.updateList', product);
        }, function (response) {
            $rootScope.$broadcast('product.validationError', response.data.error);
        });
    };

    this.list2 = function () {

        //console.log('list2 fetching');
        var data = _productService.customGETLIST('productlist2');
        productsCache.put('list2', data);
        //console.log("list generated");

        return data;
    }
    /*
     * Get list of products
     */
    this.list = function () {
        // GET /api/product
        var data = _productService.getList();
        productsCache.put('list', data);
        return data;
    };

    this.createsingle = function (asin) {
        Restangular.several('product/createsingle', asin).post().then(function () {
            $rootScope.$broadcast('product.createsingle');
        }, function (response) {
            //console.log(JSON.stringify(response));
            $rootScope.$broadcast('product.validationError', response.data.error);
        });
    };

    this.addProduct = function (prodObj) {
    
        return _productService.customPOST(prodObj, "add", {}, {});
      
    };
    //added harsh
    this.addmultiple = function (prodObj) {
        console.log(prodObj);
        return _productService.customPOST(prodObj, "add", {}, {});
       
    };

    this.addField = function (product) {
        return _productService.customPOST(product, "update", {}, {});
    };

  

    this.addBlkProduct = function (products, url) {
        //console.log(url);
        return _importService.post({ file: products, url: url });
    }

    this.fetchReviews = function (product) {
        return _productService.customPOST({ id: product }, 'fetchReviews', {}, {});
    }

    this.fetchAmzReviews = function (product) {
        return _productService.customPOST({ id: product }, 'fetchAmzReviews', {}, {});
    }

    this.downloadAllSelected = function (selection) {
        return _productService.customPOST({ id: selection }, 'downloadAllSelected', {}, {});
    }
    this.exportAllProducts = function () {
        return Restangular.several('product/exportAllProducts').post();
    }
    this.exportProducts = function (selection) {
        return Restangular.several('product/exportProducts', [selection]).post();
    }
    this.forceSyncSelected = function (selection) {
        //return _productService.customPOST({id:selection},'syncAllSelected',{},{});
        return Restangular.several('product/syncAllSelected', [selection]).post();
    }

    this.hasReviews = function (product) {
        return _productService.customPOST({ id: product }, 'hasReviews', {}, {});
    }

    this.createmany = function (parentasin) {
        Restangular.several('product/createmany', parentasin).post().then(function () {
            $rootScope.$broadcast('product.createmany');
        }, function (response) {
            $rootScope.$broadcast('product.validationError', response.data.error);
        });
    };

    this.destroy = function (id) {
        Restangular.several('product/destroy', id).post().then(function () {
            $rootScope.$broadcast('product.destroy');
        }, function (response) {
            $rootScope.$broadcast('product.validationError');
        });
    };

    

    /*
     * Pagination change
     */
    this.pageChange = function (pageNumber, per_page, end, pagination) {
        // GET /api/product?page=2
        if (end == pagination) {
            pageNumber = 1;
            return _productService.getList({ page: pageNumber, per_page: per_page });
        } else {
            return _productService.getList({ page: pageNumber, per_page: per_page });
        }
    };

    /*
     * Pagination change
     */
    this.incompletepageChange = function (incompletepageNumber, incompleteper_page, incompleteend, incompletepagination) {
        // GET /api/product?page=2
        if (incompleteend == incompletepagination) {
            incompletepageNumber = 1;
            return _productService.getList({ page: incompletepageNumber, per_page: incompleteper_page });
        } else {
            return _productService.getList({ page: incompletepageNumber, per_page: incompleteper_page });
        }
    };

    this.amzpageChange = function (keyword, category, per_page, pageNumber, end, pagination) {
        // GET /api/product?page=2		
        if (end == pagination) {
            pageNumber = 1;
            return _productService.getList({ keyword: keyword, category: category, page: pageNumber, per_page: per_page });
        } else {
            return _productService.getList({ keyword: keyword, category: category, page: pageNumber, per_page: per_page });
        }
    };



    /*
     * Search in product
     */
    this.search = function (query, per_page, pageNumber, end, pagination) {
        // GET /api/product/search?query=test&per_page=10
        if (query != '') {
            if (end == pagination) {
                pageNumber = 1;
                return _productService.customGETLIST("search", { page: pageNumber, query: query, per_page: per_page });
            } else {
                return _productService.customGETLIST("search", { page: pageNumber, query: query, per_page: per_page });
            }
        } else {
            return _productService.getList({ page: pageNumber, per_page: per_page });
        }
    }

    /*
     * Search in product
     */
    this.incompletesearch = function (incompletequery, incompleteper_page, incompletepageNumber, incompleteend, incompletepagination) {
        // GET /api/product/search?query=test&per_page=10
        if (incompletequery != '') {
            if (incompleteend == incompletepagination) {
                incompletepageNumber = 1;
                return _productService.customGETLIST("incompletesearch", { page: incompletepageNumber, query: incompletequery, per_page: incompleteper_page });
            } else {
                return _productService.customGETLIST("incompletesearch", { page: incompletepageNumber, query: incompletequery, per_page: incompleteper_page });
            }
        } else {
            return _productService.getList({ page: incompletepageNumber, per_page: incompleteper_page });
        }
    }

    /*
    * Search in product
    */
    this.asin_search = function (keyword, category, per_page, pageNumber, end, pagination) {
        if (keyword != '') {
            if (end == pagination) {
                pageNumber = 1;
                return _productService.customGETLIST("asin_search", { page: pageNumber, keyword: keyword, category: category, per_page: per_page });
            } else {
                return _productService.customGETLIST("asin_search", { page: pageNumber, keyword: keyword, category: category, per_page: per_page });
            }
        } else {
            return _productService.getList({ page: pageNumber, per_page: per_page });
        }
    };

    /*
     * reimport Product 
     */
    this.reimport = function (selection, asin) {
        Restangular.several('product/reimport', selection).post().then(function (response) {
            $rootScope.$broadcast('product.reimport', [selection, response, asin]);
        }, function (response) {
            $rootScope.$broadcast('product.validationError', response.data.error);
        });
    };

    this.forceSync = function (selection, asin = "NA") {
        if (Array.isArray(selection)) {
            Restangular.several('product/forceSync', selection).post().then(function (response) {
                $rootScope.$broadcast('product.forceSync', [selection, response, asin]);
            }, function (response) {
                $rootScope.$broadcast('product.validationError', response.data.error);
            });
        } else {
            Restangular.several('product/forceSync', selection).post().then(function (response) {
                $rootScope.$broadcast('product.forceSync', [selection, response, asin]);
            }, function (response) {
                $rootScope.$broadcast('product.validationError', response.data.error);
            });
        }
    }
    /*
     * block Product
     */
    this.block = function (selection) {
        Restangular.several('product/block', selection).post().then(function () {
            $rootScope.$broadcast('product.block', selection);
        }, function (response) {
            $rootScope.$broadcast('product.validationError', response.data.error);
        });
    };

    /*
     * unblock Product
     */
    this.unblock = function (selection) {
        Restangular.several('product/unblock', selection).post().then(function () {
            $rootScope.$broadcast('product.unblock', selection);
        }, function (response) {
            $rootScope.$broadcast('product.validationError', response.data.error);
        });
    };

    this.changeLink = function (productobj) {
        //console.log(productobj);
        if (Array.isArray(productobj)) {
            Restangular.all('product').customPOST(productobj, "changeLink").then(function () {
                $rootScope.$broadcast('product.changeLink', productobj);
            }, function (response) {
                $rootScope.$broadcast('product.validationError', response.productobj.error);
            });
        } else {
            Restangular.all('product').customPOST(productobj, "changeLink").then(function () {
                $rootScope.$broadcast('product.changeLink', productobj);
            }, function (response) {
                $rootScope.$broadcast('product.validationError', response.productobj.error);
            });
        }
    }

    this.getVariants = function(product_id) {
		// GET /api/product/variant
		var res = _productService.customGETLIST("variants", {product_id:product_id});
		return res;
    };
});






