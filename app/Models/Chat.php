<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $table = 'Chat';
    protected $primaryKey = 'Chat_ID';

    protected $fillable = ['Tender_ID', 'User_ID', 'Timestamp', 'Content'];
    public $timestamps = false;

    public function tender(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tender::class, 'Tender_ID');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'User_ID');
    }
}
