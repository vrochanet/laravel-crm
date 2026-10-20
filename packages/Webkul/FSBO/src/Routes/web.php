<?php

use Illuminate\Support\Facades\Route;
use Webkul\FSBO\Http\Controllers\FSBOController;

Route::middleware(['web', 'admin_locale', 'user'])
    ->prefix(config('app.admin_path'))
    ->controller(FSBOController::class)
    ->group(function () {
        Route::get('fsbo/create', 'create')->name('admin.fsbo.create');

        Route::post('fsbo', 'store')->name('admin.fsbo.store');
    });
