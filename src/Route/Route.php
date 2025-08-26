<?php
use Illuminate\Support\Facades\Route;
    Route::get('setup',Amerhendy\Setup\App\SetupController::class)->middleware((array) 'web')->name('Setup');
    Route::post('setup','Amerhendy\Setup\App\SetupController@post')->middleware((array) 'web');