<?php
namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Wallet;
use App\Models\Tender;
use App\Models\Order;
use App\Services\CryptoPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TenderController extends Controller
{
    /**
     * Zeigt alle öffentlichen Ausschreibungen sowie die vom Benutzer angenommenen Ausschreibungen an.
     */
    public function index()
    {
        $publicTenders = Tender::where("Status", "OPEN")->get();
        $acceptedTenders = Tender::where('Provider_ID', auth()->id())->get();
        Log::info('Ausschreibungen abgefragt.', [
            'user_id' => auth()->id(),
            'public_tenders_count' => $publicTenders->count(),
            'accepted_tenders_count' => $acceptedTenders->count(),
        ]);

        return view('tenders', compact('publicTenders', 'acceptedTenders'));
    }

    /**
     * Zeigt alle Ausschreibungen des aktuellen Benutzers an.
     */
    public function indexMy()
    {
        $tenders = Tender::where('Tenderer_ID', auth()->id())->get();
        Log::info('Eigene Ausschreibungen abgefragt.', [
            'user_id' => auth()->id(),
            'tenders_count' => $tenders->count(),
        ]);

        return view('mytenders', compact('tenders'));
    }

    /**
     * Erstellt eine neue Ausschreibung.
     *
     * Es werden Eingaben validiert, das Wallet und der Kontostand geprüft.
     * Schreibende Aktionen und etwaige Fehler (systemisch/sicherheitsrelevant) werden geloggt.
     *
     * @param Request $request
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'order_id'    => 'required|exists:App\Models\Order,Order_ID',
            'bid'         => 'required|numeric',
            'filament'    => 'required|string',
            'infill'      => 'required|integer',
            'description' => 'required|string',
        ]);

        // Prüfen des Wallets des aktuellen Benutzers
        $wallet = Wallet::where('user_id', auth()->id())->first();
        if (!$wallet) {
            Log::error('Wallet des Benutzers nicht gefunden.', [
                'user_id' => auth()->id()
            ]);
            return back()->with("error", "Wallet nicht gefunden.");
        }

        // Guthabenabfrage
        $balance = CryptoPayment::get_balance($wallet->Address, env('BLOCKCYPHER_API_KEY'));
        if ($balance < floatval($validated["bid"])) {
            Log::info('Unzureichendes Guthaben für Ausschreibung.', [
                'user_id'      => auth()->id(),
                'balance'      => $balance,
                'benötigt_bid' => $validated["bid"]
            ]);
            return back()->with("error", "Nicht genügend Guthaben im Wallet.");
        }

        // Erstellen der Ausschreibung
        $tender = Tender::create([
            "Bid"          => $validated["bid"],
            "Order_ID"     => $validated["order_id"],
            "Filament"     => $validated["filament"],
            "Infill"       => $validated["infill"],
            "Description"  => $validated["description"],
            "Tender_Date"  => date("Y-m-d H:i:s"),
            "Tenderer_ID"  => auth()->id(),
            "Status"       => "OPEN",
        ]);
        if (!$tender) {
            Log::error('Ausschreibung konnte nicht erstellt werden.', [
                'user_id'  => auth()->id(),
                'order_id' => $validated["order_id"]
            ]);
            return back()->with('error', 'Ausschreibung konnte nicht erstellt werden.');
        }

        Log::info('Ausschreibung erfolgreich erstellt.', [
            'user_id'  => auth()->id(),
            'tender_id'=> $tender->id,
            'bid'      => $validated["bid"]
        ]);

        return redirect("/tenders/my")->with('success', 'Ausschreibung erfolgreich erstellt.');
    }

    /**
     * Akzeptiert eine Ausschreibung.
     *
     * Neben der Berechtigungsprüfung wird der Status der Ausschreibung aktualisiert und eine Transaktion ausgeführt.
     * Schreibende Aktionen, systemische Fehler und Sicherheitsvorfälle (z. B. fehlende Rolle) werden geloggt.
     *
     * @param Request $request
     */
    public function accept(Request $request)
    {
        $request->validate([
            'tender_id' => 'required|exists:App\Models\Tender,Tender_ID',
        ]);

        $user = auth()->user();
        $hasRole = false;
        foreach ($user->roles as $role) {
            if ($role->Role === 'Provider') {
                $hasRole = true;
                break;
            }
        }
        if (!$hasRole) {
            Log::warning('Unberechtigter Akzeptanzversuch einer Ausschreibung.', [
                'user_id'   => auth()->id(),
                'tender_id' => $request->tender_id
            ]);
            return redirect()->back()->with('error', 'Nicht berechtigt.');
        }

        $tender = Tender::find($request->tender_id);
        if ($tender->Status !== 'OPEN') {
            Log::info('Akzeptanzversuch für bereits bearbeitete Ausschreibung.', [
                'user_id'   => auth()->id(),
                'tender_id' => $tender->id,
                'current_status' => $tender->Status
            ]);
            return redirect()->back()->with('error', 'Ausschreibung ist bereits bearbeitet.');
        }

        // Setzen des Status und Zuordnung des Providers
        $tender->Status = 'ACCEPTED';
        $tender->Provider_ID = auth()->id();

        // Wallets für Transaktion abfragen
        $providerWallet = Wallet::where('user_id', auth()->id())->first();
        $tendererWallet = Wallet::where('user_id', $tender->Tenderer_ID)->first();
        if (!$providerWallet || !$tendererWallet) {
            Log::error('Wallets nicht gefunden bei der Ausschreibungsakzeptanz.', [
                'provider_id'  => auth()->id(),
                'tenderer_id'  => $tender->Tenderer_ID,
                'tender_id'    => $tender->id,
            ]);
            return back()->with('error', 'Wallet(s) nicht gefunden.');
        }

        // Ausführen der Transaktion
        $txHash = CryptoPayment::make_transaction(
            $tendererWallet->Address,
            $providerWallet->Address,
            $tendererWallet->Priv_Key,
            $tendererWallet->Pub_Key,
            env("BLOCKCYPHER_API_KEY"),
            $tender->Bid
        );
        if (array_key_exists("error", $txHash) || !array_key_exists("tx_hash", $txHash)) {
            Log::error('Transaktion bei Ausschreibungsakzeptanz fehlgeschlagen.', [
                'user_id'    => auth()->id(),
                'tender_id'  => $tender->id,
                'tx_response'=> $txHash
            ]);
            return back()->with("error", "Fehler beim Durchführen der Transaktion.");
        }

        $tender->Transaction_Hash = $txHash["tx_hash"];

        if (!$tender->save()) {
            Log::error('Speichern der akzeptierten Ausschreibung fehlgeschlagen.', [
                'user_id'   => auth()->id(),
                'tender_id' => $tender->id
            ]);
            return back()->with('error', 'Fehler beim Bearbeiten der Ausschreibung.');
        }

        Log::info('Ausschreibung erfolgreich akzeptiert und Transaktion ausgeführt.', [
            'user_id'         => auth()->id(),
            'tender_id'       => $tender->id,
            'transaction_hash'=> $txHash["tx_hash"]
        ]);

        return redirect("/tenders")->with('success', 'Ausschreibung erfolgreich angenommen. Die Transaktion ist in Bearbeitung...');
    }

    /**
     * Markiert eine Ausschreibung als verschickt.
     *
     * Validiert die Eingabedaten, setzt Versandinformationen und protokolliert alle relevanten Aktionen und Fehler.
     *
     * @param Request $request
     */
    public function ship(Request $request)
    {
        $validated = $request->validate([
            'tender_id'         => 'required|exists:App\Models\Tender,Tender_ID',
            'shipping_number'   => 'required|string',
            'shipping_provider' => 'required|string'
        ]);

        $tender = Tender::find($validated['tender_id']);
        if (!$tender) {
            Log::error('Ausschreibung nicht gefunden zum Versand.', [
                'tender_id' => $validated['tender_id'],
                'user_id'   => auth()->id()
            ]);
            return back()->with('error', 'Ausschreibung nicht gefunden.');
        }

        // Beispielhaft: Setzen von Versandinformationen.
        // Hier könnten Felder wie "Shipping_Number" oder "Shipping_Provider" gesetzt werden.
        $tender->Shipping_Number = $validated['shipping_number'];
        $tender->Shipping_Provider = $validated['shipping_provider'];
        $tender->Status = 'SHIPPED';

        if (!$tender->save()) {
            Log::error('Fehler beim Aktualisieren der Versandinformationen einer Ausschreibung.', [
                'tender_id' => $tender->id,
                'user_id'   => auth()->id()
            ]);
            return back()->with('error', 'Fehler beim Aktualisieren der Versandinformationen.');
        }

        Log::info('Ausschreibung erfolgreich als verschickt markiert.', [
            'tender_id'         => $tender->id,
            'user_id'           => auth()->id(),
            'shipping_number'   => $validated['shipping_number'],
            'shipping_provider' => $validated['shipping_provider']
        ]);

        return redirect("/tenders")->with('success', 'Ausschreibung erfolgreich als verschickt markiert.');
    }
}
