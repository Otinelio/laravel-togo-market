<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Produit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
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
        'vues',
        'statut'
    ];

    protected $casts = [
        'variations_possibles' => 'array',
        'prix'  => 'decimal:2',
        'vues'  => 'integer',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

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

    public function favoris()
    {
        return $this->hasMany(Favori::class);
    }

    // ── Attribut dynamique : est-ce un favori de l'utilisateur connecté ? ─────

    public function getIsFavorisAttribute(): bool
    {
        if (!auth()->check()) return false;
        return $this->favoris()->where('user_id', auth()->id())->exists();
    }

    public function getIsPersonnelAttribute(): bool
    {
        return is_null($this->boutique_id);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /**
     * Produits actifs triés par score cumulé : vues + (nombre_favoris × 3).
     * Sans limite : utiliser ->limit(n) ou ->paginate() dans le contrôleur.
     */
    public function scopeTrendingScore($query)
    {
        return $query
            ->withCount('favoris')
            ->where('statut', 'actif')
            ->orderByRaw('(vues + (favoris_count * 3)) DESC');
    }

    /**
     * Scope Zone : produits dont la localisation ou l'adresse de la boutique
     * contient le texte de la zone recherchée.
     */
    public function scopeByZone($query, string $zone)
    {
        return $query->where(function ($q) use ($zone) {
            $q->where('localisation', 'LIKE', "%{$zone}%")
              ->orWhereHas('boutique', fn($bq) => $bq->where('adresse', 'LIKE', "%{$zone}%"));
        });
    }
}
