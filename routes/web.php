<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TenderController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\DesignController;
use Illuminate\Support\Facades\Route;

// Authentifizierung (Login/Registrierung - öffentlich)
Route::get('auth/login', [AuthController::class, 'showLoginForm']);
Route::post('auth/login', [AuthController::class, 'login']);

Route::get('auth/register', [AuthController::class, 'showRegisterForm']);
Route::post('auth/register', [AuthController::class, 'register']);

// Designs-Routen (Öffentlich zugänglich)
Route::get('designs', [DesignController::class, 'index']);

// Authentifizierte Routen (erfordern Login)
Route::middleware(['auth'])->group(function () {
    // Benutzer abmelden
    Route::get('auth/logout', [AuthController::class, 'logout']);

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
    Route::get('user/settings', [UserController::class, 'index']);
    Route::post('user/settings', [UserController::class, 'update']);

    // Wallet-Routen
    Route::get('wallet', [WalletController::class, 'index']);
    Route::post('wallet/send', [WalletController::class, 'send']);

    // Tender-Routen
    Route::get('tenders', [TenderController::class, 'index']);
    Route::post('tenders/create', [TenderController::class, 'create']);
    Route::post('tenders/accept', [TenderController::class, 'accept']);
    Route::post('tenders/ship', [TenderController::class, 'ship']);
    Route::post('tenders/chat', [TenderController::class, 'chat'])->name('tenders.chat');

    Route::get('tenders/my', [TenderController::class, 'indexMy']);
    Route::post('tenders/my/chat', [TenderController::class, 'chat'])->name('tenders.my.chat');
    Route::post('tenders/my/close', [TenderController::class, 'close']);
});
