<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthenticateController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AmzConfigController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ContactController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['cors'])->group(function () {
    Route::post('authenticate', [AuthenticateController::class, 'authenticate']);
});

Route::group(['prefix' => 'v1'], function() {
    Route::resource('authenticate', AuthenticateController::class, ['only' => ['index']]);
    Route::post('authenticate', [AuthenticateController::class, 'authenticate']);
    Route::resource('review', ReviewController::class, ['only' => ['index']]);
    Route::get('review/search', [ReviewController::class, 'search']);
    Route::get('review/{id}', [ReviewController::class, 'getById']);
    Route::post('review/update', [ReviewController::class, 'update']);
    Route::post('review/destroy/{id}', [ReviewController::class, 'destroy']);
    Route::post('review/publish/{id}', [ReviewController::class, 'publish']);
    Route::post('review/unpublish/{id}', [ReviewController::class, 'unpublish']);
    Route::post('review/export/{id}', [ReviewController::class, 'exportReviews']);
    Route::post('review/refetchAmzReviews', [ReviewController::class, 'refetchAmzReviews']);
    Route::post('review/downloadAllSelected/{id}', [ProductController::class, 'downloadAllSelected']);
    Route::post('review/fetchAllSelected/{id}', [ProductController::class, 'fetchAllSelected']);
    Route::resource('import', ImportController::class);
    Route::get('product/search', [ProductController::class, 'search']);
    Route::get('product/incompletesearch', [ProductController::class, 'incompletesearch']);
    Route::get('product/incompleteProducts', [ProductController::class, 'incompleteProducts']);
    Route::get('product/asin_search', [ProductController::class, 'asin_search']);
    Route::get('product/productlist2', [ProductController::class, 'productlist2']);
    Route::post('product/createsingle/{asin}', [ProductController::class, 'createsingle']);
    Route::post('product/createmany/{parentasin}', [ProductController::class, 'createmany']);
    Route::post('product/destroy/{id}', [ProductController::class, 'destroy']);
    Route::post('product/update', [ProductController::class, 'update']);
    Route::post('product/updateList', [ProductController::class, 'update']);
    Route::post('product/block/{id}', [ProductController::class, 'block']);
    Route::post('product/unblock/{id}', [ProductController::class, 'unblock']);
    Route::post('product/reimport/{id}', [ProductController::class, 'reimport']);
    Route::post('product/forceSync/{id}', [ProductController::class, 'forceSync']);
    Route::post('product/changeLink', [ProductController::class, 'changeLink']);
    Route::post('product/add', [ProductController::class, 'addProductByCrawl']);
    Route::post('fetch-amazon-product', [ProductController::class, 'fetchAmazonProduct']);
    Route::post('product/addmultiple', [ProductController::class, 'addProductByCrawl1']);
    Route::post('product/addBulk', [ProductController::class, 'store']);
    Route::post('product/fetchReviews', [ProductController::class, 'fetchReviews']);
    Route::post('product/fetchAmzReviews', [ProductController::class, 'fetchAmzReviews']);
    Route::post('product/count', [ProductController::class, 'productCount']);
    Route::post('product/hasReviews/{id}', [ProductController::class, 'hasReviews']);
    Route::post('product/exportAllProducts', [ProductController::class, 'downloadAllProducts']);
    Route::post('product/exportProducts/{id}', [ProductController::class, 'exportProducts']);
    Route::post('product/syncAllSelected/{id}', [ProductController::class, 'syncAllSelected']);
    Route::post('product/update1', [ProductController::class, 'update1']);
    Route::resource('product', ProductController::class, ['only' => ['index', 'show']]);
    Route::resource('category', CategoryController::class);
    Route::get('amzconfig', [AmzConfigController::class, 'index']);
    Route::post('amzconfig', [AmzConfigController::class, 'store']);
    Route::get('settings', [SettingsController::class, 'index']);
    Route::post('settings', [SettingsController::class, 'store']);
    Route::post('settings/buynow', [SettingsController::class, 'saveBuyNowSettings']);
    Route::post('settings/pricingrules', [SettingsController::class, 'savePricingRulesSettings']);
    Route::post('settings/sync', [SettingsController::class, 'saveSyncSettings']);
    Route::post('settings/reviews', [SettingsController::class, 'saveReviewsSettings']);
    Route::post('contact/requestquote', [ContactController::class, 'requestquote']);
});
