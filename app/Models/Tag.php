<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Dieses Model repräsentiert einen Tag in der Anwendung.
 * Ein Tag ist in einer n:m-Beziehung zu Designs eingebunden, d.h. ein Tag kann mehreren Designs zugeordnet sein.
 */
class Tag extends Model
{
    protected $table = 'Tag';
    protected $primaryKey = 'Tag_ID';

    protected $fillable = ['Name'];
    public $timestamps = false;

    public function designs(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Design::class, 'Design_Tags', 'Tag_ID', 'Design_ID');
    }
}
