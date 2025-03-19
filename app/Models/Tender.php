<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tender extends Model
{
    protected $table = 'Tender';
    protected $primaryKey = 'Tender_ID';

    protected $fillable = [
        'Status',
        'Bid',
        'Infill',
        'Filament',
        'Description',
        'Tenderer_ID',
        'Provider_ID',
        'Order_ID',
        'Tender_Date',
        'Shipping_Provider',
        'Shipping_Number',
        'Transaction_Hash'
    ];
    public $timestamps = false;

    public function tenderer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'Tenderer_ID');
    }

    public function provider(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'Provider_ID');
    }

    public function order(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Order::class, 'Order_ID');
    }

    public function chat(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Chat::class, 'Tender_ID');
    }
}
