<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ResidentController;

Route::redirect('/', '/documents/create');

Route::get('/documents/create', [DocumentController::class, 'create'])->name('documents.create');
Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
Route::post('/documents/preview', [DocumentController::class, 'preview'])->name('documents.preview');
Route::get('/documents/download/{filename}', [DocumentController::class, 'download'])->name('documents.download');

Route::get('/residents', [ResidentController::class, 'index'])->name('residents.index');
Route::post('/residents/lookup', [ResidentController::class, 'lookup'])->name('residents.lookup');
Route::get('/residents/search', [ResidentController::class, 'searchJson'])->name('residents.search');
Route::post('/residents', [ResidentController::class, 'store'])->name('residents.store');
Route::post('/residents/import', [ResidentController::class, 'import'])->name('residents.import');
Route::get('/residents/template', [ResidentController::class, 'template'])->name('residents.template');
Route::get('/residents/export', [ResidentController::class, 'export'])->name('residents.export');
Route::get('/residents/export/xlsx', [ResidentController::class, 'exportXlsx'])->name('residents.export.xlsx');
