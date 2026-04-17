<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImageProduit extends Model
{
    protected $table = 'images_produit';

    protected $fillable = [
        'produit_id',
        'chemin_image',
        'is_principale',
    ];

    protected $casts = [
        'is_principale' => 'boolean',
    ];

    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }
}
