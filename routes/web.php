<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ExcelImportController;

Route::get('/', function () {
    return redirect('/upload');
});

Route::get('/upload', [ExcelImportController::class, 'index']);
Route::post('/upload', [ExcelImportController::class, 'upload']);
Route::get('/preview/{filename}', [ExcelImportController::class, 'preview'])->name('preview');
Route::post('/import', [ExcelImportController::class, 'import']);
