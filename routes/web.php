<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TenderController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\DesignController;
use Illuminate\Support\Facades\Route;

// Authentifizierung (Login/Registrierung - öffentlich)
Route::get('/auth/login', [AuthController::class, 'showSsoForm']);

Route::get('/auth/redirect/google', [GoogleController::class, 'redirectToGoogle']);
Route::get('/auth/callback/google', [GoogleController::class, 'handleGoogleCallback']);

// Designs-Routen (Öffentlich zugänglich)
Route::get('designs', [DesignController::class, 'index']);

// Authentifizierte Routen (erfordern Login)
Route::middleware(['auth'])->group(function () {
    // Benutzer abmelden
    Route::get('auth/logout', [GoogleController::class, 'logout']);

    // Download Page
    Route::get('download', [DownloadController::class, 'download']);

    // Designs
    Route::get('designs/manage', [DesignController::class, 'indexManage']);
    Route::post('designs/manage/create', [DesignController::class, 'create']);
    Route::post('designs/manage/delete', [DesignController::class, 'delete']);
    Route::post('designs/manage/edit', [DesignController::class, 'update']);

    // Bestellungen
    Route::post('orders/create', [OrderController::class, 'create']);
    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/download', [OrderController::class, 'downloadStl']);
    Route::post('orders/tender', [TenderController::class, 'create']);

    // Benutzerprofil/Einstellungen bearbeiten
    Route::get('settings', [UserController::class, 'settings']);
    Route::post('settings/update', [UserController::class, 'settingsUpdate']);
    Route::post('settings/verify/designer', [UserController::class, 'verifyDesigner']);
    Route::post('settings/verify/provider', [UserController::class, 'verifyProvider']);

    // Wallet-Routen
    Route::get('wallet', [WalletController::class, 'index']);
    Route::post('wallet/send', [WalletController::class, 'sendTransaction']);

    // Tender-Routen
    Route::get('tenders', [TenderController::class, 'index']);
    Route::post('tenders/create', [TenderController::class, 'create']);
    Route::post('tenders/accept', [TenderController::class, 'accept']);
    Route::post('tenders/confirm', [TenderController::class, 'confirm']);
    Route::post('tenders/ship', [TenderController::class, 'ship']);

    Route::get('tenders/my', [TenderController::class, 'indexMy']);
    Route::post('tenders/my/confirm', [TenderController::class, 'confirm']);
    Route::post('tenders/my/close', [TenderController::class, 'close']);

    Route::post('tenders/chat', [TenderController::class, 'chat']);
});
