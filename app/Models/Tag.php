<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
