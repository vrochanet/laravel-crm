<?php

use Illuminate\Support\Facades\Route;
use Webkul\FSBO\Http\Controllers\FSBOController;

Route::prefix('fsbo')->controller(FSBOController::class)->group(function () {
    Route::get('create', 'create')->name('admin.fsbo.create');

    Route::post('', 'store')->name('admin.fsbo.store');
});
