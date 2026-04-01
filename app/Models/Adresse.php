<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['quartier_id', 'details', 'latitude', 'longitude'])]
class Adresse extends Model
{
    public function addressable()
    {
        return $this->morphTo();
    }

    public function quartier()
    {
        return $this->belongsTo(Quartier::class);
    }
}
