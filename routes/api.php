<?php

use App\Http\Controllers\Api\PrinterController;

// Route um Jobs abzurufen
Route::get('/printer/get_job', [PrinterController::class, 'getJob']);

// Route um Design runterzuladen
Route::get('/printer/download_stl', [PrinterController::class, 'downloadStl']);

// Route um Status zu Job zurückzumelden
Route::post('/printer/job_status', [PrinterController::class, 'setJobStatus']);
