<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Dieses Model repräsentiert ein Design in der Anwendung.
 * Ein Design gehört zu einer Kategorie und einem Designer, d.h. es ist einer Kategorie und einem Nutzer zugeordnet.
 * Zudem kann ein Design mit mehreren Tags verknüpft sein.
 */
class Design extends Model
{
    protected $table = 'Design';
    protected $primaryKey = 'Design_ID';

    protected $fillable = [
        'Name',
        'STL_File',
        'Price',
        'Description',
        'Cover_Picture_File',
        'License',
        'Category_ID',
        'Designer_ID'
    ];
    public $timestamps = false;

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class, 'Category_ID');
    }

    public function designer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'Designer_ID');
    }

    public function tags(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'Design_Tags', 'Design_ID', 'Tag_ID');
    }
}
