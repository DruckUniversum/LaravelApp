<?php
namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Wallet;
use App\Models\Design;
use App\Services\CryptoPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    protected $cryptoPayment;

    public function __construct(CryptoPayment $cryptoPayment) {
        $this->cryptoPayment = $cryptoPayment;
    }

    /**
     * Zeigt alle Bestellungen des aktuellen Benutzers an.
     */
    public function index()
    {
        $orders = Order::where('user_id', auth()->id())->paginate(10);
        return view('orders', compact('orders'));
    }

    /**
     * Legt eine neue Bestellung an.
     *
     * Es werden schreibende Aktionen, systemische Fehler und sicherheitsrelevante Auffälligkeiten geloggt.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function create(Request $request)
    {
        // Validierung der Eingabedaten.
        $orderData = $request->validate([
            'design_id' => 'required|exists:App\Models\Design,Design_ID'
        ]);

        $design = Design::find($orderData["design_id"]);

        // Prüfe, ob das Wallet des Benutzers existiert.
        $wallet = Wallet::where('user_id', auth()->id())->first();
        if (!$wallet) {
            Log::error('Systemfehler: Wallet des Benutzers nicht gefunden', [
                'user_id' => auth()->id()
            ]);
            return back()->with("error", "Wallet nicht gefunden.");
        }

        // Abfrage des aktuellen Guthabens
        $balance = $this->cryptoPayment->get_balance($wallet->Address, env('BLOCKCYPHER_API_KEY'));
        if ($balance < $design->Price) {
            Log::info('Schreibende Aktion: Unzureichendes Guthaben im Wallet', [
                'user_id'  => auth()->id(),
                'balance'  => $balance,
                'erforderlich' => $design->Price
            ]);
            return back()->with("error", "Nicht genügend Guthaben im Wallet.");
        }

        // Abruf des Wallets des Designers
        $designerWallet = Wallet::where('user_id', $design->Designer_ID)->first();
        if (!$designerWallet) {
            Log::error('Systemfehler: Wallet des Designers nicht gefunden', [
                'designer_id' => $design->Designer_ID
            ]);
            return back()->with('error', 'Designer Wallet nicht gefunden.');
        }

        // Versuch, die Transaktion durchzuführen
        $txHash = $this->cryptoPayment->make_transaction(
            $wallet->Address,
            $designerWallet->Address,
            $wallet->Priv_Key,
            $wallet->Pub_Key,
            env("BLOCKCYPHER_API_KEY"),
            $design->Price
        );
        if (array_key_exists("error", $txHash) || !array_key_exists("tx_hash", $txHash)) {
            Log::error('Systemfehler: Transaktion fehlgeschlagen', [
                'user_id'   => auth()->id(),
                'design_id' => $design->Design_ID,
                'tx_response' => $txHash
            ]);
            return back()->with("error", "Fehler beim Durchführen der Transaktion.");
        }

        // Erstellung der Bestellung
        $order = Order::create([
            "User_ID"           => auth()->id(),
            "Design_ID"         => $design->Design_ID,
            "Paid_Price"        => $design->Price,
            "Payment_Status"    => "OPEN",
            "Order_Date"        => date("Y-m-d H:i:s"),
            "Transaction_Hash"  => $txHash["tx_hash"]
        ]);
        if (!$order) {
            Log::error('Systemfehler: Bestellung konnte nicht erstellt werden', [
                'user_id'   => auth()->id(),
                'design_id' => $design->Design_ID
            ]);
            return back()->with('error', 'Bestellung konnte nicht durchgeführt werden.');
        }

        // Protokolliere die erfolgreiche, schreibende Aktion.
        Log::info('Schreibende Aktion: Bestellung erfolgreich erstellt', [
            'order_id'         => $order->id,
            'user_id'          => auth()->id(),
            'design_id'        => $design->Design_ID,
            'transaction_hash' => $txHash["tx_hash"]
        ]);

        return redirect('/orders')->with("success", "Bestellung erfolgreich erstellt.");
    }
}
