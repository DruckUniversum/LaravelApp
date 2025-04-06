<?php

namespace App\Services;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class CryptoPayment
{
    /**
     * Erstellt ein Wallet für verschiedene Kryptowährungen (Bitcoin, Ethereum, Dogecoin, Litecoin oder Dash).
     *
     * @param string $apiKey Der API-Schlüssel für den Zugriff auf die Blockcypher-API.
     * @return array Gibt ein Array mit den generierten Wallet-Daten zurück (private_key, public_key, address und wif).
     */
    public function generate_wallet(string $apiKey): array
    {
        $url = "https://api.blockcypher.com/v1/bcy/test/addrs?token=" . $apiKey;
        // Initialisierung der cURL-Sitzung
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // ⬅️ `-k` entspricht `CURLOPT_SSL_VERIFYPEER = false`
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // ⬅️ Deaktiviert Host-Zertifikatsprüfung
        $response = curl_exec($ch);
        // Fehlerbehandlung
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            return ['error' => 'cURL Fehler: ' . $error_msg];
        }
        curl_close($ch);
        // Überprüfen, ob die Antwort gültig ist
        $data = json_decode($response, true);
        // Überprüfen, ob die Antwort erfolgreich war
        if (isset($data['error'])) {
            return ['error' => 'API Fehler: ' . $data['error']];
        }
        // Überprüfen, ob die erforderlichen Daten vorhanden sind
        if (isset($data['private'], $data['public'], $data['address'], $data['wif'])) {
            return $data;
        } else {
            return ['error' => 'Fehler: Ungültige API-Antwort oder fehlende Daten.'];
        }
    }

    /**
     * Erstellt ein Wallet für verschiedene Kryptowährungen (Bitcoin, Ethereum, Dogecoin, Litecoin oder Dash).
     *
     * @param string $apiKey Der API-Schlüssel für den Zugriff auf die Blockcypher-API.
     * @return array Gibt ein Array mit den generierten Wallet-Daten zurück (private_key, public_key, address und wif).
     */
    public function add_bcy(string $address, int $amount, string $apiKey): array
    {
        $url = "https://api.blockcypher.com/v1/bcy/test/faucet?token=" . $apiKey;
        $body = [
            "amount" => $amount,
            "address" => $address
        ];
        // Initialisierung der cURL-Sitzung
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // ⬅️ `-k` entspricht `CURLOPT_SSL_VERIFYPEER = false`
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // ⬅️ Deaktiviert Host-Zertifikatsprüfung
        curl_setopt($ch, CURLOPT_POSTFIELDS,     json_encode($body) );
        curl_setopt($ch, CURLOPT_HTTPHEADER,     array('Content-Type: text/plain'));
        $response = curl_exec($ch);
        // Fehlerbehandlung
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            return ['error' => 'cURL Fehler: ' . $error_msg];
        }
        curl_close($ch);
        // Überprüfen, ob die Antwort gültig ist
        $data = json_decode($response, true);
        // Überprüfen, ob die Antwort erfolgreich war
        if (isset($data['error'])) {
            return ['error' => 'API Fehler: ' . $data['error']];
        }
        // Überprüfen, ob die erforderlichen Daten vorhanden sind
        if (isset($data['tx_ref'])) {
            return ['success' => 'BCY Transkation erstellt.'];
        } else {
            return ['error' => 'Fehler: Ungültige API-Antwort oder fehlende Daten.'];
        }
    }

    /**
     * Führt das Python-Skript aus, um Satoshis zu senden.
     *
     * @param string $senderAddress Absenderadresse
     * @param string $receiverAddress Empfängeradresse
     * @param string $privateKey Privater Schlüssel des Absenders
     * @param string $publicKey Öffentlicher Schlüssel des Absenders
     * @param string $apiKey Blockcypher API-Schlüssel
     * @param int $amount Transfer-Betrag in Satoshi
     *
     * @return array Ergebnis der Transaktion oder Fehler
     */
    public function make_transaction(
        string $senderAddress,
        string $receiverAddress,
        string $privateKey,
        string $publicKey,
        string $apiKey,
        int $amount
    ): array {
        try {
            // Pfad zum Python-Skript
            $scriptPath = base_path('app/Scripts/send_satoshi.py');

            // Kommando zur Ausführung des Scripts mit notwendigen Parametern
            $command = sprintf(
                'python3 %s %s %s %s %s %s %s',
                escapeshellarg($scriptPath),
                escapeshellarg($apiKey),
                escapeshellarg($senderAddress),
                escapeshellarg($receiverAddress),
                escapeshellarg($privateKey),
                escapeshellarg($publicKey),
                escapeshellarg($amount)
            );

            // Shell-Ausführung
            $output = shell_exec($command);

            // Falls die Ausgabe null ist, kann dies auf einen schwerwiegenden Fehler hindeuten
            if ($output === null) {
                throw new \RuntimeException('shell_exec hat null zurückgegeben. Möglicherweise ist ein Fehler aufgetreten.');
            }

            // JSON-Antwort vom Python-Skript parsen
            $result = json_decode($output, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Fehler beim JSON-Dekodieren: ' . json_last_error_msg());
            }

            // Resultat zurückgeben
            return $result;
        } catch (\Exception $e) {
            // Fehlerbehandlung
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Überprüft die Bestätigungen einer Transaktion auf der BlockCypher API.
     *
     * Diese Funktion überprüft regelmäßig den Status einer Transaktion auf der BlockCypher API,
     * indem sie die Anzahl der Bestätigungen überwacht. Sie gibt `true` zurück, wenn die Transaktion
     * mindestens 3 Bestätigungen erreicht hat. Sollte die Transaktion nach 5 Minuten noch nicht
     * mindestens 3 Bestätigungen haben, gibt die Funktion `false` zurück.
     *
     * @param string $tx_hash Der Transaktions-Hash, der überprüft werden soll.
     *
     * @return bool Gibt `true` zurück, wenn mindestens 3 Bestätigungen erreicht wurden,
     *              andernfalls `false`, wenn entweder die Transaktion nicht gefunden wird oder
     *              nach 5 Minuten keine Bestätigungen vorliegen.
     */
    public function check_confirmations(string $tx_hash): bool
    {
        $url = "https://api.blockcypher.com/v1/bcy/test/txs/$tx_hash";
        $response = file_get_contents($url);
        if ($response === FALSE) {
            echo "Transaktion noch nicht gefunden! Versuche es später erneut.\n";
            return false;
        }
        $data = json_decode($response, true);
        $confirmations = $data['confirmations'] ?? 0;
        if ($confirmations >= 3) {
            return true;
        }
        return false;
    }

    /**
     * Ruft das Guthaben einer Wallet-Adresse über die BlockCypher-API ab.
     *
     * @param string $address Die Adresse, deren Guthaben abgefragt werden soll.
     * @param string $apiKey Der API-Schlüssel für den Zugriff auf die BlockCypher-API.
     * @return int Gibt nur den Wert des Guthabens (balance) zurück oder 0 bei Fehlern.
     */
    public function get_balance(string $address, string $apiKey): int {
        $url = "https://api.blockcypher.com/v1/bcy/test/addrs/$address/balance?token=$apiKey";
        // API-Anfrage durchführen
        $response = file_get_contents($url);
        if ($response === FALSE) {
            return 0;
        }
        $data = json_decode($response, true);
        if (isset($data['error'])) {
            return 0;
        }
        return $data['balance'] ?? 0;
    }
}
