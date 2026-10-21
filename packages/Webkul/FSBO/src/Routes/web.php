<?php

use Illuminate\Support\Facades\Route;
use Webkul\FSBO\Http\Controllers\FSBOController;

Route::middleware(['web', 'admin_locale', 'user'])
    ->prefix(config('app.admin_path'))
    ->controller(FSBOController::class)
    ->group(function () {
        Route::get('fsbo', 'index')->name('admin.fsbo.index');
        Route::get('fsbo/create', 'create')->name('admin.fsbo.create');
        Route::get('fsbo/{id}/edit', 'edit')->name('admin.fsbo.edit');
        Route::put('fsbo/{id}', 'update')->name('admin.fsbo.update');
        Route::get('fsbo/{id}', 'show')->name('admin.fsbo.show');
        Route::post('fsbo', 'store')->name('admin.fsbo.store');
    });
