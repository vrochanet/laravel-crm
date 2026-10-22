<?php

use Illuminate\Support\Facades\Route;
use Webkul\Prospeccao\Http\Controllers\CommunicationTemplateController;
use Webkul\Prospeccao\Http\Controllers\ProspeccaoController;

Route::middleware(['web', 'admin_locale', 'user'])
    ->prefix(config('app.admin_path'))
    ->group(function () {
        // Templates first so "prospeccao/templates" is not captured by "prospeccao/{id}".
        Route::controller(CommunicationTemplateController::class)
            ->prefix('prospeccao/templates')
            ->group(function () {
                Route::get('/', 'index')->name('admin.prospeccao.templates.index');
                Route::get('/create', 'create')->name('admin.prospeccao.templates.create');
                Route::post('/', 'store')->name('admin.prospeccao.templates.store');
                Route::get('/{id}/edit', 'edit')->name('admin.prospeccao.templates.edit');
                Route::put('/{id}', 'update')->name('admin.prospeccao.templates.update');
                Route::post('/{id}/duplicate', 'duplicate')->name('admin.prospeccao.templates.duplicate');
                Route::post('/{id}/deactivate', 'deactivate')->name('admin.prospeccao.templates.deactivate');
                Route::post('/{id}/activate', 'activate')->name('admin.prospeccao.templates.activate');
            });

        Route::controller(ProspeccaoController::class)->group(function () {
            Route::get('prospeccao', 'index')->name('admin.prospeccao.index');
            Route::get('prospeccao/create', 'create')->name('admin.prospeccao.create');
            Route::post('prospeccao', 'store')->name('admin.prospeccao.store');

            Route::get('prospeccao/{id}/edit', 'edit')->name('admin.prospeccao.edit');
            Route::put('prospeccao/{id}', 'update')->name('admin.prospeccao.update');

            Route::post('prospeccao/{id}/communicate/prepare', 'prepareCommunication')
                ->name('admin.prospeccao.communicate.prepare');
            Route::post('prospeccao/{id}/communicate/send', 'sendCommunication')
                ->name('admin.prospeccao.communicate.send');

            Route::get('prospeccao/{id}/whatsapp', 'whatsapp')->name('admin.prospeccao.whatsapp');
            Route::get('prospeccao/{id}/email', 'email')->name('admin.prospeccao.email');
            Route::get('prospeccao/{id}', 'show')->name('admin.prospeccao.show');
        });
    });

