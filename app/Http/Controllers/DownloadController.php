<?php
namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Design;
use App\Models\Order;
use App\Models\Tag;
use App\Models\Tender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DownloadController extends Controller
{
    /**
     * Lädt eine STL-Datei basierend auf einer gegebenen Anfrage herunter.
     *
     * Es werden schreibende Aktionen, systemische Fehler und sicherheitsrelevante Auffälligkeiten geloggt.
     *
     * Mögliche Parameter:
     * - order_id: Für den Download über eine Bestellung.
     * - tender_id: Für den Download über eine Ausschreibung.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\Response
     */
    public function download(Request $request)
    {
        // Download über Bestellung
        if ($request->has('order_id')) {
            $order = Order::find($request->order_id);
            if (!$order) {
                Log::error('Systemfehler: Bestellung nicht gefunden', [
                    'order_id' => $request->order_id,
                    'user_id'  => auth()->id()
                ]);
                return App::abort(404);
            }
            if ($order->User_ID != auth()->id()) {
                Log::error('Sicherheitsrelevant: Unautorisierter Download-Versuch (Bestellung)', [
                    'order_id' => $order->Order_ID,
                    'user_id'  => auth()->id()
                ]);
                return App::abort(403);
            }

            Log::info('Download initiiert (Bestellung)', [
                'order_id' => $order->Order_ID,
                'user_id'  => auth()->id()
            ]);
            return Storage::download("stl/{$order->design->STL_File}.stl", "{$order->design->Name}.stl");

            // Download über Ausschreibung
        } elseif ($request->has('tender_id')) {
            $tender = Tender::find($request->tender_id);
            if (!$tender) {
                Log::error('Systemfehler: Ausschreibung nicht gefunden', [
                    'tender_id' => $request->tender_id,
                    'user_id'   => auth()->id()
                ]);
                return App::abort(404);
            }
            if ($tender->Provider_ID != auth()->id()) {
                Log::error('Sicherheitsrelevant: Unautorisierter Download-Versuch (Ausschreibung)', [
                    'tender_id' => $tender->Tender_ID,
                    'user_id'   => auth()->id()
                ]);
                return App::abort(403);
            }

            // Änderung des Ausschreibungsstatus (schreibende Aktion)
            $tender->Status = "PROCESSING";
            if (!$tender->save()) {
                Log::error('Systemfehler: Aktualisierung des Ausschreibungsstatus fehlgeschlagen', [
                    'tender_id' => $tender->Tender_ID,
                    'user_id'   => auth()->id()
                ]);
                return App::abort(500);
            }

            Log::info('Ausschreibungsstatus erfolgreich auf PROCESSING gesetzt', [
                'tender_id' => $tender->Tender_ID,
                'user_id'   => auth()->id()
            ]);

            return Storage::download("stl/{$tender->order->design->STL_File}.stl", "{$tender->order->design->Name}.stl");

            // Ungültiger oder fehlender Parameter
        } else {
            Log::error('Systemfehler: Ungültiger Download-Aufruf ohne gültige Parameter', [
                'request_parameters' => $request->all(),
                'user_id'            => auth()->id()
            ]);
            return App::abort(404);
        }
    }
}
