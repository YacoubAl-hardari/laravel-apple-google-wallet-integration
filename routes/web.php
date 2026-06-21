<?php

use Illuminate\Support\Facades\Route;
use Yacoubalhaidari\AppleGoogleWallet\Http\Controllers\WalletStudioController;

Route::get('/', [WalletStudioController::class, 'index'])->name('index');
Route::post('/export', [WalletStudioController::class, 'export'])->name('export');
Route::post('/download-zip', [WalletStudioController::class, 'downloadZip'])->name('download-zip');
Route::post('/upload', [WalletStudioController::class, 'upload'])->name('upload');
Route::post('/preview', [WalletStudioController::class, 'preview'])->name('preview');
Route::post('/test-pass', [WalletStudioController::class, 'testPass'])->name('test-pass');
