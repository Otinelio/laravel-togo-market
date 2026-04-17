<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Produit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'boutique_id',
        'categorie_id',
        'titre',
        'description',
        'prix',
        'etat',
        'localisation',
        'variations_possibles',
        'prix_negociable',
        'stock',
        'statut'
    ];

    protected $casts = [
        'variations_possibles' => 'array',
        'prix' => 'decimal:2',
    ];

    public function boutique()
    {
        return $this->belongsTo(Boutique::class);
    }

    public function categorie()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ImageProduit::class);
    }
}
