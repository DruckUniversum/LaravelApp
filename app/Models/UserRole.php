<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Dieses Model repräsentiert eine Nutzerrolle in der Anwendung.
 * Eine Nutzerrolle ist einem Nutzer zugeordnet, d.h. ein Nutzer kann mehrere Rollen besitzen.
 */
class UserRole extends Model
{
    protected $table = 'User_Roles';
    public $incrementing = false;

    protected $fillable = ['User_ID', 'Role'];
    public $timestamps = false;

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'User_ID');
    }
}
