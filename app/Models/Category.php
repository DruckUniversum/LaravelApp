<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Dieses Model repräsentiert eine Kategorie für Designs in der Anwendung.
 * Eine Kategorie hat eine 1:n-Beziehung zu Designs, d.h. einer Kategorie können mehrere Designs zugeordnet werden.
 */
class Category extends Model
{
    protected $table = 'Category';
    protected $primaryKey = 'Category_ID';

    protected $fillable = ['Name'];
    public $timestamps = false;

    public function designs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Design::class, 'Category_ID');
    }
}
