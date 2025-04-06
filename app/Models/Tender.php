<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Dieses Model repräsentiert einen Tag für Designs in der Anwendung.
 * Ein Tag ist in einer n:m-Beziehung zu Designs eingebunden, d.h. ein Tag kann mehreren Designs zugeordnet sein.
 */
class Tender extends Model
{
    protected $table = 'Tender';
    protected $primaryKey = 'Tender_ID';

    protected $fillable = [
        'Status', // Values = [OPEN, ACCEPTED, CONFIRM_USER, CONFIRM_PROVIDER, PROCESSING, SHIPPED, CLOSED]
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

    public function chats(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Chat::class, 'Tender_ID');
    }
}
