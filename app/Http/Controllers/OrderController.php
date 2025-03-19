<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Wallet;
use App\Services\CryptoPayment;
use Illuminate\Http\Request;
use App\Models\Design;

class OrderController extends Controller
{
    // Alle Bestellungen anzeigen
    public function index()
    {
        $orders = Order::where('user_id', auth()->id())->paginate(10);

        return view('orders', compact('orders'));
    }

    // Bestellung anlegen
    public function create(Request $request)
    {
        $orderData = $request->validate([
            'design_id' => 'required|exists:App\Models\Design,Design_ID'
        ]);
        $design = Design::find($orderData["design_id"]);

        $wallet = Wallet::where('user_id', auth()->id())->first();
        $balance = CryptoPayment::get_balance($wallet->Address, env('BLOCKCYPHER_API_KEY'));

        if($balance < $design->Price) return back()->with("error", "Nicht genügend Guthaben im Wallet.");

        $designerWallet = Wallet::where('user_id', $design->Designer_ID)->first();
        $txHash = CryptoPayment::make_transaction(
            $wallet->Address,
            $designerWallet->Address,
            $wallet->Priv_Key,
            $wallet->Pub_Key,
            env("BLOCKCYPHER_API_KEY"),
            $design->Price
        );
        if(array_key_exists("error", $txHash) || !array_key_exists("tx_hash", $txHash)) return back()->with("error", "Fehler beim Durchführen der Transaktion.");

        $order = Order::create([
            "User_ID" => auth()->id(),
            "Design_ID" => $design->Design_ID,
            "Paid_Price" => $design->Price,
            "Payment_Status" => "OPEN",
            "Order_Date" => date("Y-m-d H:i:s"),
            "Transaction_Hash" => $txHash["tx_hash"]
        ]);
        if(!$order) return back()->with('error', 'Bestellung konnte nicht durchgeführt werden.');

        return redirect('/orders')->with("success", "Bestellung erfolgreich erstellt.");
    }

    // STL File runterladen
    public function downloadStl(Request $request)
    {
        return view('orders');
    }
}
