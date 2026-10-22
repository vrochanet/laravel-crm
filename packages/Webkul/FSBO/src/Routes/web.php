<?php

use Illuminate\Support\Facades\Route;
use Webkul\FSBO\Http\Controllers\CommunicationTemplateController;
use Webkul\FSBO\Http\Controllers\FSBOController;

Route::middleware(['web', 'admin_locale', 'user'])
    ->prefix(config('app.admin_path'))
    ->group(function () {
        // Templates first so "fsbo/templates" is not captured by "fsbo/{id}".
        Route::controller(CommunicationTemplateController::class)
            ->prefix('fsbo/templates')
            ->group(function () {
                Route::get('/', 'index')->name('admin.fsbo.templates.index');
                Route::get('/create', 'create')->name('admin.fsbo.templates.create');
                Route::post('/', 'store')->name('admin.fsbo.templates.store');
                Route::get('/{id}/edit', 'edit')->name('admin.fsbo.templates.edit');
                Route::put('/{id}', 'update')->name('admin.fsbo.templates.update');
                Route::post('/{id}/duplicate', 'duplicate')->name('admin.fsbo.templates.duplicate');
                Route::post('/{id}/deactivate', 'deactivate')->name('admin.fsbo.templates.deactivate');
                Route::post('/{id}/activate', 'activate')->name('admin.fsbo.templates.activate');
            });

        Route::controller(FSBOController::class)->group(function () {
            Route::get('fsbo', 'index')->name('admin.fsbo.index');
            Route::get('fsbo/create', 'create')->name('admin.fsbo.create');
            Route::post('fsbo', 'store')->name('admin.fsbo.store');

            Route::get('fsbo/{id}/edit', 'edit')->name('admin.fsbo.edit');
            Route::put('fsbo/{id}', 'update')->name('admin.fsbo.update');

            Route::post('fsbo/{id}/communicate/prepare', 'prepareCommunication')
                ->name('admin.fsbo.communicate.prepare');
            Route::post('fsbo/{id}/communicate/send', 'sendCommunication')
                ->name('admin.fsbo.communicate.send');

            Route::get('fsbo/{id}/whatsapp', 'whatsapp')->name('admin.fsbo.whatsapp');
            Route::get('fsbo/{id}/email', 'email')->name('admin.fsbo.email');
            Route::get('fsbo/{id}', 'show')->name('admin.fsbo.show');
        });
    });
