<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Boutique extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nom',
        'telephone',
        'slug',
        'description',
        'logo_url',
        'adresse',
        'details_adresse',
        'localisation',
        'latitude',
        'longitude',
        'note_moyenne',
        'temps_reponse',
        'statut',
        'contacts',
        'horaires'
    ];

    protected $casts = [
        'contacts' => 'array',
        'horaires' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function produits()
    {
        return $this->hasMany(Produit::class);
    }
}
