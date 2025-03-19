<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'Order';
    protected $primaryKey = 'Order_ID';

    protected $fillable = [
        'User_ID',
        'Design_ID',
        'Paid_Price',
        'Payment_Status',
        'Order_Date',
        'Transaction_Hash'
    ];
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'User_ID');
    }

    public function design()
    {
        return $this->belongsTo(Design::class, 'Design_ID');
    }
}
