<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Dieses Model repräsentiert eine Nutzerrolle in der Anwendung.
 * Eine Nutzerrolle ist einem Nutzer zugeordnet, d.h. ein Nutzer kann mehrere Rollen besitzen.
 */
class Printer extends Model
{
    protected $table = 'Printer';
    protected $primaryKey = 'Printer_ID';

    protected $fillable = ['User_ID', 'API_Key'];
    public $timestamps = false;

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'User_ID');
    }
}
