<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $table = 'Wallet';
    protected $primaryKey = 'Address';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['Address', 'Coin_Symbol', 'Pub_Key', 'Priv_Key', 'User_ID'];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'User_ID');
    }
}
