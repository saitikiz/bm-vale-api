<?php


Route::get('/', 'HomeController@index')->name('home');


Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Admin', 'middleware' => ['auth']], function () {
    Route::get('/', 'HomeController@index')->name('home');
    // Permissions
    Route::delete('permissions/destroy', 'PermissionsController@massDestroy')->name('permissions.massDestroy');
    Route::resource('permissions', 'PermissionsController');

    // Roles
    Route::delete('roles/destroy', 'RolesController@massDestroy')->name('roles.massDestroy');
    Route::resource('roles', 'RolesController');

    // Users
    Route::delete('users/destroy', 'UsersController@massDestroy')->name('users.massDestroy');
    Route::resource('users', 'UsersController');

    // Audit Logs
    Route::resource('audit-logs', 'AuditLogsController', ['except' => ['create', 'store', 'edit', 'update', 'destroy']]);

    // User Alerts
    Route::delete('user-alerts/destroy', 'UserAlertsController@massDestroy')->name('user-alerts.massDestroy');
    Route::get('user-alerts/read', 'UserAlertsController@read');
    Route::resource('user-alerts', 'UserAlertsController', ['except' => ['edit', 'update']]);

    // Site
    Route::delete('sites/destroy', 'SiteController@massDestroy')->name('sites.massDestroy');
    Route::resource('sites', 'SiteController');

    // Worker
    Route::delete('workers/destroy', 'WorkerController@massDestroy')->name('workers.massDestroy');
    Route::resource('workers', 'WorkerController');

    // Bonus Request
    Route::delete('bonus-requests/destroy', 'BonusRequestController@massDestroy')->name('bonus-requests.massDestroy');
    Route::resource('bonus-requests', 'BonusRequestController');

    // Players
    Route::delete('players/destroy', 'PlayersController@massDestroy')->name('players.massDestroy');
    Route::resource('players', 'PlayersController');

    // Bonuses
    Route::delete('bonus/destroy', 'BonusesController@massDestroy')->name('bonus.massDestroy');
    Route::post('bonus/media', 'BonusesController@storeMedia')->name('bonus.storeMedia');
    Route::post('bonus/ckmedia', 'BonusesController@storeCKEditorImages')->name('bonus.storeCKEditorImages');
    Route::resource('bonus', 'BonusesController');
});
Route::group(['prefix' => 'profile', 'as' => 'profile.', 'namespace' => 'Auth', 'middleware' => ['auth']], function () {
    // Change password
    if (file_exists(app_path('Http/Controllers/Auth/ChangePasswordController.php'))) {
        Route::get('password', 'ChangePasswordController@edit')->name('password.edit');
        Route::post('password', 'ChangePasswordController@update')->name('password.update');
        Route::post('profile', 'ChangePasswordController@updateProfile')->name('password.updateProfile');
        Route::post('profile/destroy', 'ChangePasswordController@destroy')->name('password.destroyProfile');
    }
});
