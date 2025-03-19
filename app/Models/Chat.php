<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $table = 'Chat';
    protected $primaryKey = 'Chat_ID';

    protected $fillable = ['Tender_ID'];
    public $timestamps = false;

    public function messages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ChatMessage::class, 'Chat_ID');
    }

    public function tender(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tender::class, 'Tender_ID');
    }
}
