<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Usuario extends Authenticatable
{
    protected $table = 'usuarios';

    protected $primaryKey = 'id_usuario';

    public $timestamps = false;

    protected $guarded = [];

    protected $hidden = ['contrasena'];

    protected $authPasswordName = 'contrasena';

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Rol::class,
            'usuario_rol',
            'id_usuario',
            'id_rol'
        );
    }

    public function tieneRol(string $nombre): bool
    {
        return $this->roles()
            ->where('roles.nombre', $nombre)
            ->exists();
    }

    public function tieneAlgunoDeEstosRoles(array $nombres): bool
    {
        return $this->roles()
            ->whereIn('roles.nombre', $nombres)
            ->exists();
    }
}
