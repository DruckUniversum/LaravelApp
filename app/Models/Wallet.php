<?php

namespace App\Models;

use App\Casts\EncryptedCast;
use Illuminate\Database\Eloquent\Model;

/**
 * Dieses Model repräsentiert ein Wallet in der Anwendung.
 * Ein Wallet gehört zu genau einem Nutzer, d.h. einem Wallet ist immer genau ein Nutzer zugeordnet.
 */
class Wallet extends Model
{
    protected $table = 'Wallet';
    protected $primaryKey = 'Address';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['Address', 'Coin_Symbol', 'Pub_Key', 'Priv_Key', 'User_ID'];

    protected $casts = [
        'Priv_Key' => EncryptedCast::class,
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'User_ID');
    }
}
