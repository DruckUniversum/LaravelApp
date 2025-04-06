<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Tender;
use App\Services\CryptoPayment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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
        Log::info('Start der Überprüfung von Krypto-Transaktionen.', ['command' => $this->signature]);

        // Laden aller Bestellungen mit dem Status "OPEN"
        $orders = Order::where('Payment_Status', 'OPEN')
            ->whereNotNull('Transaction_Hash')
            ->get();

        Log::info('Überprüfung offener Bestellungen gestartet.', ['order_count' => $orders->count()]);

        foreach ($orders as $order) {
            $txHash = $order->Transaction_Hash; // Abrufen des Transaktions-Hashes (z. B. einer Blockchain-Zahlung)
            if (!$txHash) {
                Log::warning('Überspringe Bestellung ohne Transaktions-Hash.', [
                    'order_id' => $order->Order_ID
                ]);
                continue;
            }

            // Überprüfung der Transaktion auf Blockchain-Confirmations
            $confirmations = intval(CryptoPayment::check_confirmations($txHash));
            Log::info('Transaktionsüberprüfung für Bestellung.', [
                'order_id' => $order->Order_ID,
                'tx_hash'  => $txHash,
                'confirmations' => $confirmations,
            ]);

            // Wenn genügend Bestätigungen vorhanden sind, wird der Zahlungsstatus aktualisiert
            if ($confirmations) {
                $order->Payment_Status = "PAID"; // Zahlungsstatus der Bestellung auf "BEZAHLT" setzen
                if ($order->save()) {
                    Log::info('Zahlungsstatus der Bestellung auf PAID gesetzt.', [
                        'order_id' => $order->Order_ID,
                        'tx_hash'  => $txHash
                    ]);
                } else {
                    Log::error('Fehler beim Speichern des aktualisierten Zahlungsstatus der Bestellung.', [
                        'order_id' => $order->Order_ID,
                        'tx_hash'  => $txHash
                    ]);
                }
            }
        }

        // Laden aller Tenders mit dem Status "ACCEPTED"
        $tenders = Tender::where('Status', 'CONFIRM_PROVIDER')
            ->whereNotNull('Transaction_Hash')
            ->get();

        Log::info('Überprüfung der Tenders gestartet.', ['tender_count' => $tenders->count()]);

        foreach ($tenders as $tender) {
            $txHash = $tender->Transaction_Hash;
            if (!$txHash) {
                Log::warning('Überspringe Tender ohne Transaktions-Hash.', [
                    'tender_id' => $tender->Tender_ID
                ]);
                continue;
            }

            $confirmations = CryptoPayment::check_confirmations($txHash);
            Log::info('Transaktionsüberprüfung für Tender.', [
                'tender_id'   => $tender->Tender_ID,
                'tx_hash'     => $txHash,
                'confirmations' => $confirmations,
            ]);

            // Wenn die Transaktion valide ist, wird der Status der Ausschreibung auf "PAID" geändert
            if ($confirmations) {
                $tender->Status = "PAID"; // Status der Ausschreibung auf "BEZAHLT" setzen
                if ($tender->save()) {
                    Log::info('Status der Ausschreibung auf PAID gesetzt.', [
                        'tender_id' => $tender->Tender_ID,
                        'tx_hash'   => $txHash
                    ]);
                } else {
                    Log::error('Fehler beim Speichern des aktualisierten Status der Ausschreibung.', [
                        'tender_id' => $tender->Tender_ID,
                        'tx_hash'   => $txHash
                    ]);
                }
            }
        }

        Log::info('Überprüfung von Krypto-Transaktionen abgeschlossen.', [
            'order_count_processed' => $orders->count(),
            'tender_count_processed' => $tenders->count()
        ]);
    }
}
