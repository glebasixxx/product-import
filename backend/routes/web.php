<?php

use App\Http\Controllers\DocsController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/docs');

Route::get('/docs', [DocsController::class, 'ui'])->name('docs');
Route::get('/docs/openapi.yaml', [DocsController::class, 'spec'])->name('docs.spec');
