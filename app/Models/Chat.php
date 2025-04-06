<?php

namespace App\Models;

use App\Casts\EncryptedCast;
use Illuminate\Database\Eloquent\Model;

/**
 * Dieses Model repräsentiert einen Chat in der Anwendung.
 * Ein Chat gehört zu einem Tender sowie zu einem User, d.h. er ist mit beiden Entitäten verknüpft.
 */
class Chat extends Model
{
    protected $table = 'Chat';
    protected $primaryKey = 'Chat_ID';

    protected $fillable = ['Tender_ID', 'User_ID', 'Timestamp', 'Content'];

    protected $casts = [
        'Content' => EncryptedCast::class,
    ];
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
