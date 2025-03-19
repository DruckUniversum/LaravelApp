<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $table = 'Chat_Message';
    protected $primaryKey = 'Message_ID';

    protected $fillable = ['Chat_ID', 'User_ID', 'Timestamp', 'Content'];
    public $timestamps = false;

    public function chat(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Chat::class, 'Chat_ID');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'User_ID');
    }
}
