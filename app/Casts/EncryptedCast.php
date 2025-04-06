<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\Crypt;

class EncryptedCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            // Falls die Entschlüsselung fehlschlägt, kann alternativ der Originalwert zurückgegeben werden
            return $value;
        }
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return Crypt::encryptString($value);
    }
}
