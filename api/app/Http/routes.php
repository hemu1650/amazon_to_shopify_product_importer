<?php

Route::group(['prefix' => 'v1'], function()
{
	// authentication
    Route::resource('authenticate', 'AuthenticateController', ['only' => ['index']]);
    Route::post('authenticate', 'AuthenticateController@authenticate');
    
    Route::resource('review','ReviewController', ['only' => ['index']]);
	
	Route::get('review/search','ReviewController@search');
	Route::get('review/{id}','ReviewController@getById');
	Route::post('review/update','ReviewController@update');
	Route::post('review/destroy/{id}','ReviewController@destroy');
    Route::post('review/publish/{id}','ReviewController@publish');
    Route::post('review/unpublish/{id}','ReviewController@unpublish');
    Route::post('review/export/{id}','ReviewController@exportReviews');
    Route::post('review/refetchAmzReviews',"ReviewController@refetchAmzReviews");

    Route::resource('import','ImportController');
    
    
	//	Route::resource('product', 'ProductController');//, ['only' => ['index']]);
    Route::get('product/search','ProductController@search');  
	Route::get('product/incompletesearch','ProductController@incompletesearch');  
	Route::get('product/incompleteProducts','ProductController@incompleteProducts');  
	Route::get('product/asin_search','ProductController@asin_search');
	Route::get('product/productlist2','ProductController@productlist2');
	//Route::post('product/getproduct/{product_id}','ProductController@getproduct');//added tushar
	//Route::post('product/updateproduct','ProductController@updateproduct');//added tushar	
	//Route::post('product/addProductImg','ProductController@addImage');//added tushar
	//Route::post('product/addImageByUrl','ProductController@addImageByUrl');//added tushar
	//Route::post('product/deleteImageByUrl','ProductController@deleteImageByUrl');//added tushar
	Route::post('product/createsingle/{asin}','ProductController@createsingle');
	Route::post('product/createmany/{parentasin}','ProductController@createmany');
	Route::post('product/destroy/{id}','ProductController@destroy');
	Route::post('product/update','ProductController@update');
	Route::post('product/updateList','ProductController@update');
	Route::post('product/block/{id}', 'ProductController@block'); 
	Route::post('product/unblock/{id}', 'ProductController@unblock'); 
	Route::post('product/reimport/{id}', 'ProductController@reimport');
	Route::post('product/forceSync/{id}', 'ProductController@forceSync');
	Route::post('product/changeLink', 'ProductController@changeLink');
	Route::post('product/add', 'ProductController@addProductByCrawl');
	
	Route::post('submit-form', 'ProductController@submitForm');


	// added harsh
	Route::post('product/addmultiple', 'ProductController@addProductByCrawl1');
	Route::post('product/addBulk','ProductController@store');
	Route::post('product/fetchReviews', 'ProductController@fetchReviews');
	//Route::get('review', 'ProductController@fetchReviews');//added tushar
	Route::post('product/fetchAmzReviews', 'ProductController@fetchAmzReviews');
    Route::post('product/count',"ProductController@productCount");
    Route::post('product/hasReviews/{id}',"ProductController@hasReviews");
    Route::post('review/downloadAllSelected/{id}',"ProductController@downloadAllSelected");
    Route::post('product/exportAllProducts',"ProductController@downloadAllProducts");
    Route::post('product/exportProducts/{id}',"ProductController@exportProducts");
    Route::post('review/fetchAllSelected/{id}',"ProductController@fetchAllSelected");
    Route::post('product/syncAllSelected/{id}',"ProductController@syncAllSelected");
    Route::post('product/update1','ProductController@update1');
	Route::resource('product', 'ProductController', ['only' => ['index','show']]);
    //commenting next 2 routes as their controller doesn't exist
	//Route::post('products/{product_id}/variant','ProductVariantController@store');
	//Route::post('products/{variant_id}/images/uploadimage','ProductImageController@store');
	
	Route::resource('category', 'CategoryController');

    Route::get('amzconfig','AmzConfigController@index');
	Route::post('amzconfig','AmzConfigController@store');

	Route::get('settings','SettingsController@index');
	Route::post('settings','SettingsController@store');
	Route::post('settings/buynow','SettingsController@saveBuyNowSettings');
	Route::post('settings/pricingrules','SettingsController@savePricingRulesSettings');
	Route::post('settings/sync','SettingsController@saveSyncSettings');
	Route::post('settings/reviews','SettingsController@saveReviewsSettings');

	Route::post('contact/requestquote','ContactController@requestquote');
});