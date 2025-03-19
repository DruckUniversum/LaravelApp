<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Wallet;
use App\Services\CryptoPayment;
use Illuminate\Http\Request;
use App\Models\Tender;

class TenderController extends Controller
{
    // Alle Ausschreibungen anzeigen
    public function index()
    {
        $publicTenders = Tender::where("Status", "OPEN")->get();
        $acceptedTenders = Tender::where('Provider_ID', auth()->id())->get();

        return view('tenders', compact('publicTenders', 'acceptedTenders'));
    }

    // Eigene Ausschreibungen anzeigen
    public function indexMy()
    {
        $tenders = Tender::where('Tenderer_ID', auth()->id())->get();

        return view('mytenders', compact('tenders'));
    }

    // Bearbeitung einer Ausschreibung
    public function create(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:App\Models\Order,Order_ID',
            'bid' => 'required|numeric',
            'filament' => 'required|string',
            'infill' => 'required|integer',
            'description' => 'required|string',
        ]);

        $wallet = Wallet::where('user_id', auth()->id())->first();
        $balance = CryptoPayment::get_balance($wallet->Address, env('BLOCKCYPHER_API_KEY'));

        if($balance < floatval($validated["bid"])) return back()->with("error", "Nicht genügend Guthaben im Wallet.");

        $tender = Tender::create([
            "Bid" => $validated["bid"],
            "Order_ID" => $validated["order_id"],
            "Filament" => $validated["filament"],
            "Infill" => $validated["infill"],
            "Description" => $validated["description"],
            "Tender_Date" => date("Y-m-d H:i:s"),
            "Tenderer_ID" => auth()->id(),
            "Status" => "OPEN",
        ]);
        if(!$tender) return back()->with('error', 'Ausschreibung konnte nicht erstellt werden.');

        return redirect("/tenders/my")->with('success', 'Ausschreibung erfolgreich erstellt.');
    }

    public function accept(Request $request)
    {
        $request->validate([
            'tender_id' => 'required|exists:App\Models\Tender,Tender_ID',
        ]);

        $user = auth()->user();
        $hasRole = false;
        foreach($user->roles as $role) {
            if($role->Role == 'Provider') {
                $hasRole = true;
                break;
            }
        }
        if(!$hasRole) return redirect()->back()->with('error', 'Nicht berechtigt.');

        $tender = Tender::find($request->tender_id);
        if($tender->Status != 'OPEN') return redirect()->back()->with('error', 'Ausschreibung ist bereits bearbeitet.');

        $tender->Status = 'ACCEPTED';
        $tender->Provider_ID = auth()->id();

        $providerWallet = Wallet::where('user_id', auth()->id())->first();
        $tendererWallet = Wallet::where('user_id', $tender->Tenderer_ID)->first();
        $txHash = CryptoPayment::make_transaction(
            $tendererWallet->Address,
            $providerWallet->Address,
            $tendererWallet->Priv_Key,
            $tendererWallet->Pub_Key,
            env("BLOCKCYPHER_API_KEY"),
            $tender->Bid
        );
        if(array_key_exists("error", $txHash) || !array_key_exists("tx_hash", $txHash)) return back()->with("error", "Fehler beim Durchführen der Transaktion.");

        $tender->Transaction_Hash = $txHash["tx_hash"];

        if(!$tender->save()) return back()->with('error', 'Fehler beim Bearbeiten der Ausschreibung.');

        return redirect("/tenders")->with('success', 'Ausschreibung erfolgreich angenommen. Die Transaktion ist in Bearbeitung...');
    }

    public function ship(Request $request)
    {
        $validated = $request->validate([
            'tender_id' => 'required|exists:App\Models\Tender,Tender_ID',
            "shipping_number" => "required|string",
            "shipping_provider" => "required|string"
        ]);

        $user = auth()->user();
        $hasRole = false;
        foreach($user->roles as $role) {
            if($role->Role == 'Provider') {
                $hasRole = true;
                break;
            }
        }
        if(!$hasRole) return redirect()->back()->with('error', 'Nicht berechtigt.');

        $tender = Tender::find($validated["tender_id"]);
        $tender->Status = 'SHIPPING';
        $tender->Shipping_Number = $validated["shipping_number"];
        $tender->Shipping_Provider = $validated["shipping_provider"];

        if(!$tender->save()) return back()->with('error', 'Fehler beim Versenden der Ausschreibung.');

        return redirect("/tenders")->with('success', 'Ausschreibung erfolgreich versendet.');
    }

    public function close(Request $request)
    {
        $validated = $request->validate([
            'tender_id' => 'required|exists:App\Models\Tender,Tender_ID'
        ]);

        $tender = Tender::find($validated["tender_id"]);
        if($tender->Tenderer_ID != auth()->id()) return redirect()->back()->with('error', 'Nicht berechtigt.');

        $tender->Status = 'CLOSED';

        if(!$tender->save()) return back()->with('error', 'Fehler beim Schließen der Ausschreibung.');

        return redirect("/tenders/my")->with('success', 'Ausschreibung erfolgreich geschlossen.');
    }

    public function chat(Request $request)
    {
        $validated = $request->validate([
            'tender_id' => 'required|exists:App\Models\Tender,Tender_ID',
            'message' => 'required|string'
        ]);

        $tender = Tender::find($validated["tender_id"]);
        if($tender->Tenderer_ID != auth()->id() && $tender->Provider_ID != auth()->id()) return redirect()->back()->with('error', 'Nicht berechtigt.');

        $chat = Chat::create([
            'Tender_ID' => $validated["tender_id"],
            'User_ID' => auth()->id(),
            'Content' => $validated["message"],
            'Timestamp' => date("Y-m-d H:i:s")
        ]);
        if(!$chat) return back()->with('error', 'Fehler beim Senden der Nachricht.');

        return back()->with('success', 'Nachricht erfolgreich versendet.');
    }
}
