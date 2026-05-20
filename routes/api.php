<?php

use App\Http\Controllers\BonusController;
use App\Http\Controllers\PronetController;
use Illuminate\Support\Facades\Route;




Route::post('bonus/request', [BonusController::class, 'bonusRequest']);


Route::group(['prefix' => 'v1', 'as' => 'api.', 'namespace' => 'Api\V1\Admin', 'middleware' => ['auth:sanctum']], function () {
    // Site
    Route::apiResource('sites', 'SiteApiController');

    // Worker
    Route::apiResource('workers', 'WorkerApiController');

    // Bonus Request
    Route::apiResource('bonus-requests', 'BonusRequestApiController');

    // Players
    Route::apiResource('players', 'PlayersApiController');

    // Bonuses
    Route::post('bonus/media', 'BonusesApiController@storeMedia')->name('bonus.storeMedia');
    Route::apiResource('bonus', 'BonusesApiController');
});


Route::prefix('pronet')->group(function () {
    Route::get('ping', [PronetController::class, 'ping']);
    Route::post('transactionsList', [PronetController::class, 'transactionsList']);
    Route::post('memberBalance', [PronetController::class, 'memberBalance']);
    Route::post('claimedbonusesList', [PronetController::class, 'claimedbonusesList']);
    Route::post('accountsList', [PronetController::class, 'accountsList']);
    Route::post('getBonusesAndFreeBets', [PronetController::class, 'getBonusesAndFreeBets']);
    Route::post('assignBonusesAndFreebets', [PronetController::class, 'assignBonusesAndFreebets']);


    Route::post('sportbetmastersList', [PronetController::class, 'sportbetmastersList']);
    Route::post('memberSummary', [PronetController::class, 'memberSummary']);

});
