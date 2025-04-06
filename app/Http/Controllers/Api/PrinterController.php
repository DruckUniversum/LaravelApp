<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Printer;
use App\Models\Tender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PrinterController extends Controller
{
    public function getJob (Request $request)
    {
        $validated = $request->validate([
            'api_key' => 'required|string',
        ]);

        $printers = Printer::all();
        $printer = null;
        foreach($printers as $candidate) {
            if(Hash::check($validated["api_key"], $candidate["API_Key"])) {
                $printer = $candidate;
            }
        }
        if(!$printer) {
            Log::warning('API: Authentifizierungs-Fehler (API-Key)');
            return response()->json([
                'error' => 'Ungültiger API Key'
            ], 401);
        }

        $tender = Tender::where('Provider_ID', $printer->User_ID)->where('Status', 'PAID')->first();
        if(!$tender) {
            return response()->json([
                'message' => 'Keine Druck-Jobs verfügbar.'
            ]);
        }

        return response()->json([
            'message' => 'Druck-Job gefunden',
            'tender_id' => $tender->Tender_ID,
        ]);
    }
    public function downloadStl(Request $request)
    {
        $validated = $request->validate([
            'api_key' => 'required|string',
            'tender_id' => 'required|exists:App\Models\Tender,Tender_ID',
        ]);

        $printers = Printer::all();
        $printer = null;
        foreach($printers as $candidate) {
            if(Hash::check($validated["api_key"], $candidate["API_Key"])) {
                $printer = $candidate;
            }
        }
        if(!$printer) {
            Log::warning('API: Authentifizierungs-Fehler (API-Key)');
            return response()->json([
                'error' => 'Ungültiger API Key'
            ], 401);
        }

        $tender = Tender::find($validated['tender_id']);
        if(!$tender) {
            Log::warning('API: Ausschreibung nicht gefunden.', [
                'printer_id' => $printer->Printer_ID,
                'tender_id' => $validated["tender_id"]
            ]);
            return response()->json([
                'message' => 'Ausschreibung nicht gefunden.'
            ]);
        }

        if($tender->Provider_ID != $printer->User_ID) {
            Log::error('API: Sicherheitsauffälligkeit! Es wurde versucht, eine Ausschreibung von einem anderen Druckdienstleister herunterzuladen.', [
                'printer_id' => $printer->Printer_ID,
                'tender_id' => $validated["tender_id"]
            ]);
            return response()->json([
                'message' => 'Nicht berechtigt.'
            ]);
        }

        return Storage::download("stl/{$tender->order->design->STL_File}.stl", "{$tender->design->Name}.stl");
    }
    public function setJobStatus(Request $request)
    {
        $validated = $request->validate([
            'api_key' => 'required|string',
            'tender_id' => 'required|exists:App\Models\Tender,Tender_ID',
            'status' => 'required|string|in:PROCESSING,ERROR',
            'message' => 'string',
        ]);

        $printers = Printer::all();
        $printer = null;
        foreach($printers as $candidate) {
            if(Hash::check($validated["api_key"], $candidate["API_Key"])) {
                $printer = $candidate;
            }
        }
        if(!$printer) {
            Log::warning('API: Authentifizierungs-Fehler (API-Key)');
            return response()->json([
                'error' => 'Ungültiger API Key'
            ], 401);
        }

        $tender = Tender::find($validated['tender_id']);
        if(!$tender) {
            Log::warning('API: Ausschreibung nicht gefunden.', [
                'printer_id' => $printer->Printer_ID,
                'tender_id' => $validated["tender_id"]
            ]);
            return response()->json([
                'message' => 'Ausschreibung nicht gefunden.'
            ]);
        }

        if($tender->Provider_ID != $printer->User_ID) {
            Log::error('API: Sicherheitsauffälligkeit! Es wurde versucht, eine Ausschreibung von einem anderen Druckdienstleister herunterzuladen.', [
                'printer_id' => $printer->Printer_ID,
                'tender_id' => $validated["tender_id"]
            ]);
            return response()->json([
                'message' => 'Nicht berechtigt.'
            ]);
        }

        if($validated['status'] == 'ERROR') {
            Log::error('API: Fehler beim Drucken.', [
                'printer_id' => $printer->Printer_ID,
                'tender_id' => $validated["tender_id"],
                'message' => $validated['message']
            ]);
            return response()->json([
                'success' => 'Fehlermeldung hinterlegt.'
            ]);
        }

        if($validated['status'] != 'PROCESSING') {
            Log::warning('API: Ungültiger zu setzender Zustand.', [
                'printer_id' => $printer->Printer_ID,
                'tender_id' => $validated["tender_id"],
                'new_status' => $validated['status']
            ]);
            return response()->json([
                'error' => 'Der zu setzende Zustand ist ungültig.'
            ]);
        }

        if($tender->Status != 'PAID') {
            Log::warning('API: Ungültiger Zustand der Ausschreibung.', [
                'printer_id' => $printer->Printer_ID,
                'tender_id' => $validated["tender_id"]
            ]);
            return response()->json([
                'error' =>
                    'Die Ausschreibung befindet sich in einem ungültigen Status.
                    Möglicherweise hat ein anderer Drucker-Client die Ausschreibung bereits bearbeitet oder
                    die Bearbeitung erfolgt manuell.'
            ]);
        }

        $tender->Status = "PROCESSING";

        return response()->json([
            'success' => 'Zustand erfolgreich gesetzt.'
        ]);
    }
}
