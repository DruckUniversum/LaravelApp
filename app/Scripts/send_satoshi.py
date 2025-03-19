import sys
import json
from blockcypher import create_unsigned_tx, make_tx_signatures, broadcast_signed_transaction

def make_unsigned_tx(sender_address: str, receiver_address: str, api_key: str, amount: int):
    """
    Erstellt eine nicht-signierte Transaktion.
    """
    inputs = [{'address': sender_address}]
    outputs = [{'address': receiver_address, 'value': amount}]
    unsigned_tx = create_unsigned_tx(inputs=inputs, outputs=outputs, preference="low", coin_symbol="bcy", api_key=api_key)
    if 'tosign' not in unsigned_tx:
        return None, None
    to_sign = unsigned_tx['tosign']  # Hash-Werte für die Signatur
    return unsigned_tx, to_sign

def sign_tx(sender_priv_key: str, sender_pub_key: str, to_sign: list):
    """
    Signiert die nicht-signierte Transaktion.
    """
    if not to_sign:
        return None
    privkey_list = [sender_priv_key] * len(to_sign)
    pubkey_list = [sender_pub_key] * len(to_sign)
    tx_signatures = make_tx_signatures(txs_to_sign=to_sign, privkey_list=privkey_list, pubkey_list=pubkey_list)
    return tx_signatures

def send_signed_tx(unsigned_tx: dict, tx_signatures: list, pubkey_list: list, api_key: str):
    """
    Sendet die signierte Transaktion an die Blockchain.
    """
    if not unsigned_tx or not tx_signatures:
        return None
    data = broadcast_signed_transaction(unsigned_tx=unsigned_tx, signatures=tx_signatures, pubkeys=pubkey_list, coin_symbol="bcy", api_key=api_key)
    return data.get("tx", {}).get("hash", None)

def send_satoshi(sender_address: str, receiver_address: str, sender_priv_key: str, sender_pub_key: str, api_key: str, amount: int):
    """
    Sendet eine bestimmte Anzahl an Satoshis von einem Wallet zum anderen.
    """
    unsigned_tx, to_sign = make_unsigned_tx(sender_address, receiver_address, api_key, amount)
    if unsigned_tx is None:
        return {"error": "Fehler beim Erstellen der nicht-signierten Transaktion"}
    tx_signatures = sign_tx(sender_priv_key, sender_pub_key, to_sign)
    if tx_signatures is None:
        return {"error": "Fehler beim Signieren der Transaktion"}
    tx_hash = send_signed_tx(unsigned_tx, tx_signatures, [sender_pub_key] * len(to_sign), api_key)
    if tx_hash is None:
        return {"error": "Fehler beim Senden der Transaktion"}
    return {"tx_hash": tx_hash}

if __name__ == "__main__":
    # Kommandozeilen-Argumente abrufen
    if len(sys.argv) != 7:
        print(json.dumps({"error": "Ungültige Anzahl an Parametern"}))
        sys.exit(1)
    api_key = sys.argv[1]
    sender_address = sys.argv[2]
    receiver_address = sys.argv[3]
    sender_priv_key = sys.argv[4]
    sender_pub_key = sys.argv[5]
    amount = int(sys.argv[6])
    # Transaktion durchführen
    result = send_satoshi(sender_address, receiver_address, sender_priv_key, sender_pub_key, api_key, amount)
    # JSON-Ausgabe
    print(json.dumps(result))