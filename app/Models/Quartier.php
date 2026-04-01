<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['ville_id', 'nom'])]
class Quartier extends Model
{
    public function ville()
    {
        return $this->belongsTo(Ville::class);
    }
}
