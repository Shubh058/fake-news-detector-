<?php

use Illuminate\Support\Facades\Route;


use App\Http\Controllers\NewsController;
use App\Http\Controllers\AnalysisController;


Route::middleware(['auth'])->group(function () {
	Route::get('/', [NewsController::class, 'create'])->name('news.create');
	Route::post('/analyze', [NewsController::class, 'store'])->name('news.analyze');

	// Resource routes for admin panel (optional, can be expanded)
	Route::resource('news', NewsController::class)->except(['create', 'store']);
	Route::resource('analyses', AnalysisController::class)->except(['index']);
});

// Resource routes for admin panel (optional, can be expanded)
Route::resource('news', NewsController::class)->except(['create', 'store']);
Route::resource('analyses', AnalysisController::class)->except(['index']);

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
