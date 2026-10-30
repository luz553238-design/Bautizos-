<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Iglesia extends Model
{
    protected $table = 'iglesias';

    protected $primaryKey = 'id_iglesia';

    protected $fillable = [
        'nombre',
        'tipo',
        'telefono',
        'correo',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function direccion(): HasOne
    {
        return $this->hasOne(
            DireccionIglesia::class,
            'id_iglesia',
            'id_iglesia'
        );
    }
}
