<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Services\CryptoPayment;
use App\Services\SendSatoshiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    protected $cryptoPayment;

    public function __construct(CryptoPayment $cryptoPayment)
    {
        $this->cryptoPayment = $cryptoPayment;
    }

    /**
     * Zeigt das Wallet des eingeloggten Benutzers sowie das aktuelle Guthaben an.
     *
     * Loggt den Zugriff auf das Wallet und protokolliert, wenn kein Wallet gefunden wird.
     */
    public function index()
    {
        $wallet = Wallet::where('user_id', auth()->id())->first();
        if (!$wallet) {
            Log::warning('Kein Wallet für den Benutzer gefunden.', [
                'user_id' => auth()->id()
            ]);
            $balance = 0;
        } else {
            $balance = $this->getBalance($wallet->Address);
            Log::info('Wallet und Guthaben abgerufen.', [
                'user_id' => auth()->id(),
                'wallet_address' => $wallet->Address,
                'balance' => $balance
            ]);
        }

        return view('wallet', compact('wallet', 'balance'));
    }

    /**
     * Führt eine Transaktion zum Versenden von Geldern durch.
     *
     * Validiert die Eingabedaten, überprüft das Guthaben und führt eine Transaktion aus.
     * Schreibende Aktionen, systemische Fehler und sicherheitsrelevante Auffälligkeiten werden geloggt.
     *
     * @param Request $request
     */
    public function sendTransaction(Request $request)
    {
        $request->validate([
            'amount'  => 'required|numeric|min:0.01',
            'address' => 'required|string',
        ]);

        // Protokolliere den Transaktionsversuch inklusive der angefragten Daten (ohne sensible Informationen)
        Log::info('Transaktionsversuch gestartet.', [
            'user_id' => auth()->id(),
            'amount'  => $request->amount,
            'target_address' => $request->address
        ]);

        $wallet = Wallet::where('user_id', auth()->id())->first();

        if (!$wallet) {
            Log::error('Transaktion fehlgeschlagen, da kein Wallet gefunden wurde.', [
                'user_id' => auth()->id()
            ]);
            return redirect()->back()->with('error', 'Wallet nicht gefunden.');
        }

        // Prüfe, ob das Wallet über ausreichendes Guthaben verfügt
        if ($wallet->balance < $request->amount) {
            Log::warning('Transaktionsversuch fehlgeschlagen aufgrund unzureichenden Guthabens.', [
                'user_id'   => auth()->id(),
                'wallet_balance' => $wallet->balance,
                'requested_amount' => $request->amount
            ]);
            return redirect()->back()->with('error', 'Nicht genügend Guthaben.');
        }

        // Führe die Transaktion aus
        $success = $this->cryptoPayment->make_transaction(
            $wallet->address,
            $request->address,
            $wallet->private_key,
            $wallet->public_key,
            $request->amount,
            env('BLOCKCYPHER_API_KEY')
        );

        if ($success) {
            Log::info('Transaktion erfolgreich durchgeführt.', [
                'user_id'         => auth()->id(),
                'amount'          => $request->amount,
                'from_wallet'     => $wallet->address,
                'to_wallet'       => $request->address,
            ]);
            return redirect()->back()->with('success', 'Transaktion erfolgreich.');
        } else {
            Log::error('Transaktion fehlgeschlagen.', [
                'user_id'         => auth()->id(),
                'amount'          => $request->amount,
                'from_wallet'     => $wallet->address,
                'to_wallet'       => $request->address,
            ]);
            return redirect()->back()->with('error', 'Transaktion fehlgeschlagen.');
        }
    }

    /**
     * Ruft das Guthaben für die angegebene Adresse ab.
     *
     * @param string $address
     * @return float
     */
    private function getBalance($address)
    {
        try {
            $balance = CryptoPayment::get_balance($address, env('BLOCKCYPHER_API_KEY'));
            Log::info('Guthaben erfolgreich abgerufen.', [
                'address' => $address,
                'balance' => $balance
            ]);
            return $balance;
        } catch (\Exception $e) {
            Log::error('Fehler beim Abrufen des Guthabens.', [
                'address' => $address,
                'error_message' => $e->getMessage()
            ]);
            return 0;
        }
    }
}
