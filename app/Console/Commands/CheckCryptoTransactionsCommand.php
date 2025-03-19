<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Tender;
use App\Services\CryptoPayment;
use Illuminate\Console\Command;

class CheckCryptoTransactionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:crypto-transactions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Überprüft die Transkationen der offenen Orders und Tenders auf ausreichende Blockchain-Bestätigungen.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Laden aller Bestellungen mit dem Status "OPEN"
        $orders = Order::where('Payment_Status', 'OPEN')
            ->whereNotNull('Transaction_Hash')
            ->get();
        foreach($orders as $order) {
            $txHash = $order->Transaction_Hash; // Abrufen des Transaktions-Hashes (z. B. einer Blockchain-Zahlung)
            if(!$txHash) continue; // Falls kein Transaktions-Hash vorhanden ist, überspringen

            // Überprüfung der Transaktion auf Blockchain-Confirmations
            $check = intval(CryptoPayment::check_confirmations(
                $txHash
            ));

            // Wenn genügend Bestätigungen vorhanden sind, wird der Zahlungsstatus aktualisiert
            if($check) {
                $order->Payment_Status = "PAID"; // Zahlungsstatus der Bestellung auf "BEZAHLT" setzen
                $order->save(); // Änderungen speichern
            }
        }


        $tenders = Tender::where('Status', 'ACCEPTED')
            ->whereNotNull('Transaction_Hash')
            ->get();
        foreach($tenders as $tender) {
            $txHash = $tender->Transaction_Hash;
            $check = CryptoPayment::check_confirmations(
                $txHash
            );

            // Wenn die Transaktion valide ist, wird der Status der Ausschreibung auf "BEZAHLT" geändert
            if($check) {
                $tender->Status = "PAID"; // Status der Ausschreibung auf "BEZAHLT" setzen
                $tender->save(); // Änderungen speichern
            }
        }

    }
}
