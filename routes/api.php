<?php

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

use Illuminate\Http\Request;
use App\Business_category;

Auth::routes();

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::post('v1', 'VersionController@index');

Route::post('v1/progress', 'ProgressController@index'); // ステータス一覧取得

Route::post('v1/business_category',  function(Request $request) {
    return Business_category::all();
});

Route::post('v1/users', 'UserController@index');

Route::post('v1/users/{user_id}', 'UserController@show');

Route::post('v1/shops', 'ShopController@index');

Route::post('v1/shops/{shop_id}', 'ShopController@show');

Route::post('v1/maintenance/post', 'MaintenanceController@post');

Route::post('v1/maintenance/{mantenance_id}/put/update', 'MaintenanceController@update');

Route::post('v1/maintenance/{mantenance_id}/put/approve', 'MaintenanceController@approve');

Route::post('v1/maintenance/{mantenance_id}/put/delete', 'MaintenanceController@destroy');

Route::post('v1/maintenance/{mantenance_id}/put/sendback', 'MaintenanceController@sendback');

Route::post('v1/maintenance/{mantenance_id}/put/reject', 'MaintenanceController@reject');

Route::post('v1/maintenance/{mantenance_id}/put/suspend', 'MaintenanceController@suspend');

Route::post('v1/maintenance/{mantenance_id}', 'MaintenanceController@show'); // メンテナンス詳細
Route::get('v1/maintenance/{mantenance_id}', 'MaintenanceController@show'); // メンテナンス詳細

// Route::post('v1/maintenance/search', 'MaintenanceController@search'); // メンテナンス絞り込み

Route::post('v1/maintenance', 'MaintenanceController@index'); // メンテナンス一覧
Route::get('v1/maintenance', 'MaintenanceController@index'); // メンテナンス一覧

Route::post('v1/admin/blocks', 'BlockController@index');

Route::post('v1/admin/districts', 'DistrictController@index');

Route::post('v1/admin/clients', 'ClientController@index');

Route::post('v1/admin/csv/export', 'CsvController@export');
Route::get('v1/admin/csv/export', 'CsvController@export');

Route::get('v1/maintenance/{maintenance_id}/changeprogress', 'MaintenanceController@changeProgress')->middleware('auth');