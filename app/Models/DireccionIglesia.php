<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DireccionIglesia extends Model
{
    protected $table = 'direcciones_iglesias';

    protected $primaryKey = 'id_direccion_iglesia';

    protected $fillable = [
        'id_iglesia',
        'calle',
        'numero_exterior',
        'numero_interior',
        'colonia',
        'codigo_postal',
        'municipio',
        'estado',
        'referencias',
    ];

    public function iglesia(): BelongsTo
    {
        return $this->belongsTo(
            Iglesia::class,
            'id_iglesia',
            'id_iglesia'
        );
    }
}
