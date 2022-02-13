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
})->middleware('auth');


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



Route::group(['namespace' => 'V2'], function () {

    Route::get('v2/shops', 'ShopController@index');
    Route::get('v2/shops/{shop_id}', 'ShopController@show');

    Route::get('v2/maintenance', 'MaintenanceController@index'); // メンテナンス一覧
    Route::post('v2/maintenance', 'MaintenanceController@index'); // メンテナンス一覧

    Route::any('v2/maintenance/class_history', 'MaintenanceController@classHistory'); // メンテナンス一覧
    Route::any('v2/maintenance/shop_history', 'MaintenanceController@shopHistory'); // メンテナンス一覧
    Route::get('v2/maintenance/customsList', 'MaintenanceController@customsList'); // メンテナンス一覧

    Route::get('v2/maintenance/eventcheckCountfunc', 'MaintenanceController@eventcheckCountfunc'); // メンテナンス一覧
    
    

    Route::post('v2/maintenance/{mantenance_id}/progress/create', 'MaintenanceController@createProgress');

    Route::post('v2/maintenance/{mantenance_id}/quotation/create', 'MaintenanceController@createQuotation');//tie9.5 add
    Route::post('v2/maintenance/{mantenance_id}/accounting/create', 'MaintenanceController@createAccounting');
    Route::post('v2/maintenance/{mantenance_id}/accounting/update', 'MaintenanceController@editAccountingId');
    Route::post('v2/maintenance/{mantenance_id}/deleteAccountingId', 'MaintenanceController@deleteAccountingId');
    Route::post('v2/maintenance/{mantenance_id}/selectreason', 'MaintenanceController@selectreason');
    Route::post('v2/maintenance/{mantenance_id}/getAccountingSubjects', 'MaintenanceController@getAccountingSubjects');

    Route::get('v2/maintenance/getSubjects', 'MaintenanceController@getSubjects');

    Route::post('v2/maintenance/csv/export', 'CsvController@export');
    Route::get('v2/maintenance/csv/export', 'CsvController@export');

    //csv import & export
    Route::post('v2/maintenance/csv/loadexport', 'MaintenanceController@exportTables');
    Route::post('v2/maintenance/csv/import', 'MaintenanceController@importTables');

  

    Route::post('v2/maintenance/upload/report/{mantenance_id}', 'MaintenanceController@uploadReport');
    Route::post('v2/maintenance/upload/photo/{mantenance_id}', 'MaintenanceController@uploadPhoto');
    Route::post('v2/maintenance/uploadQuotation/photo/{mantenance_id}', 'MaintenanceController@uploadQuotationPhoto');
    Route::post('v2/maintenance/upload/quotation/{mantenance_id}', 'MaintenanceController@uploadQuotation');

    Route::get('v2/maintenance/{mantenance_id}/big_middleconnect', 'MaintenanceController@big_middleconnect');
    Route::get('v2/maintenance/{mantenance_id}/middle_bigconnect', 'MaintenanceController@middle_bigconnect');
    Route::post('v2/maintenance/{mantenance_id}/deleteQuotationId', 'MaintenanceController@deleteQuotationId');
    
    
    // Route::get('v2/maintenance/{mantenance_id}/depart_name', 'MaintenanceController@depart_name');
    
    Route::post('v2/maintenance/{mantenance_id}/update_customerid', 'MaintenanceController@update_customerid');
    Route::get('v2/maintenance/{mantenance_id}/customCodeSearch', 'MaintenanceController@customCodeSearch');
    Route::post('v2/maintenance/{mantenance_id}/ultimateCustomSearch', 'MaintenanceController@ultimateCustomSearch');

    Route::get('v2/maintenance/{mantenance_id}/getUploadFiles', 'MaintenanceController@getUploadFiles');

    Route::get('v2/maintenance/{mantenance_id}/photo_files', 'MaintenanceController@getPhotoFiles');//pdf view image 

    Route::post('v2/maintenance/{mantenance_id}/saveNotes', 'MaintenanceController@saveNotes');//save note1. note2 for shop
    
    
    Route::get('v2/maintenance/{mantenance_id}/report_files', 'MaintenanceController@getReportFiles');
    Route::get('v2/maintenance/{mantenance_id}/quotation_files', 'MaintenanceController@getQuotationFiles');//tie9.5 add

    
    Route::post('v2/maintenance/getParents', 'MaintenanceController@getParents');
    Route::post('v2/maintenance/getPartners_staff', 'MaintenanceController@getPartners_staff');

    // progress mail
    Route::post('v2/maintenance/progressSendMail', 'MaintenanceController@progressSendMail');
    Route::get('v2/maintenance/chkMaintenanceId', 'MaintenanceController@chkMaintenanceId');
    Route::get('v2/maintenance/chkShopCode', 'MaintenanceController@chkShopCode');
    Route::get('v2/maintenance/chkpartner', 'MaintenanceController@chkpartner');

    Route::get('v2/maintenance/getStatusDeadline', 'MaintenanceController@getStatusDeadline');

    Route::post('v2/maintenance/post', 'MaintenanceController@post');

    Route::post('v2/maintenance/{mantenance_id}/put/update', 'MaintenanceController@update');

    Route::post('v2/maintenance/{mantenance_id}/put/approve', 'MaintenanceController@approve');

    Route::post('v2/maintenance/{mantenance_id}/put/delete', 'MaintenanceController@destroy');

    Route::post('v2/maintenance/{mantenance_id}/put/sendback', 'MaintenanceController@sendback');

    Route::post('v2/maintenance/{mantenance_id}/put/reject', 'MaintenanceController@reject');

    Route::post('v2/maintenance/{mantenance_id}/put/suspend', 'MaintenanceController@suspend');

    Route::post('v2/maintenance/{mantenance_id}', 'MaintenanceController@show'); // メンテナンス詳細
    Route::get('v2/maintenance/{mantenance_id}', 'MaintenanceController@show'); // メンテナンス詳細

    Route::get('v2/maintenance/{maintenance_id}/changeprogress', 'MaintenanceController@changeProgress')->middleware('auth');

    Route::get('v2/categories', 'CategoryController@index');
    Route::get('v2/sub_categories', 'SubCategoryController@index');
    Route::get('v2/order_types', 'OrderTypeController@index');
    Route::get('v2/matter_options', 'MatterOptionController@index');
    Route::get('v2/matter_values', 'MatterValueController@index');

});
