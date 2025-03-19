<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Services\CryptoPayment;
use App\Services\SendSatoshiService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    protected $cryptoPayment;

    public function __construct(CryptoPayment $cryptoPayment)
    {
        $this->cryptoPayment = $cryptoPayment;
    }

    public function index()
    {
        $wallet = Wallet::where('user_id', auth()->id())->first();
        $balance = $wallet ? $this->getBalance($wallet->Address) : 0;

        return view('wallet', compact('wallet', 'balance'));
    }


    public function sendTransaction(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'address' => 'required|string',
        ]);

        $wallet = Wallet::where('user_id', auth()->id())->first();

        if ($wallet->balance < $request->amount) {
            return redirect()->back()->with('error', 'Nicht genügend Guthaben.');
        }

        $success
            = $this->cryptoPayment->make_transaction(
            $wallet->address,
            $request->address,
            $wallet->private_key,
            $wallet->public_key,
            $request->amount,
            env('BLOCKCYPHER_API_KEY')
        );

        if ($success) {
            return redirect()->back()->with('success', 'Transaktion erfolgreich.');
        }

        return redirect()->back()->with('error', 'Transaktion fehlgeschlagen.');
    }

    private function getBalance($address)
    {
        // API-Aufruf, um das Guthaben abzurufen
        return CryptoPayment::get_balance($address, env('BLOCKCYPHER_API_KEY'));
    }

}
