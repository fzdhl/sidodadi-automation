<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentController;

Route::redirect('/', '/documents/create');

Route::get('/documents/create', [DocumentController::class, 'create'])->name('documents.create');
Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
