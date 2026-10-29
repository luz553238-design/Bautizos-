<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CrudController extends Controller
{
    // =====================================================
    // ESQUEMA Y UTILIDADES
    // =====================================================

    public static function schema(): array
    {
        $path = database_path('schema.json');

        abort_unless(
            is_file($path),
            500,
            'No se encontró database/schema.json.'
        );

        $schema = json_decode(file_get_contents($path), true);

        abort_unless(
            is_array($schema),
            500,
            'El esquema JSON no es válido.'
        );

        return $schema;
    }

    public static function label(string $name): string
    {
        return ucfirst(str_replace('_', ' ', $name));
    }

    public static function token(object|array $row, array $meta): string
    {
        $values = (array) $row;
        $keys = [];

        foreach ($meta['keys'] as $key) {
            $keys[$key] = $values[$key] ?? null;
        }

        return rtrim(
            strtr(
                base64_encode(json_encode($keys)),
                '+/',
                '-_'
            ),
            '='
        );
    }

    public static function display(object|array $row): string
    {
        $r = (array) $row;

        if (isset($r['apellido_paterno'])) {
            return trim(
                ($r['nombre'] ?? '') . ' ' .
                $r['apellido_paterno'] . ' ' .
                ($r['apellido_materno'] ?? '')
            );
        }

        foreach ([
            'nombre',
            'folio',
            'nombre_usuario',
            'folio_acta',
            'numero_identificacion'
        ] as $key) {
            if (isset($r[$key])) {
                return (string) $r[$key];
            }
        }

        if (isset($r['id_persona'])) {
            $persona = DB::table('personas')
                ->where('id_persona', $r['id_persona'])
                ->first();

            if ($persona) {
                return self::display($persona);
            }
        }

        if (isset($r['fecha'])) {
            return $r['fecha'] . ' ' . ($r['hora_inicio'] ?? '');
        }

        $parts = [];

        foreach (array_slice($r, 0, 2, true) as $key => $value) {
            $parts[] = self::label($key) . ': ' .
                (is_scalar($value) ? $value : '');
        }

        return implode(' · ', $parts);
    }

    private function context(string $module): array
    {
        $schema = self::schema();

        abort_unless(
            isset($schema[$module]),
            404,
            'Módulo no encontrado.'
        );

        return [
            'module' => $module,
            'meta' => $schema[$module],
            'modules' => $schema
        ];
    }

    // =====================================================
    // AUTENTICACIÓN Y ROLES
    // =====================================================

    private function usuarioActual(): Usuario
    {
        $usuario = Auth::user();

        abort_unless(
            $usuario instanceof Usuario,
            401,
            'Debes iniciar sesión.'
        );

        if (!(bool) $usuario->activo) {
            Auth::logout();

            abort(403, 'Tu cuenta está desactivada.');
        }

        return $usuario;
    }

    private function rolesDelUsuario(Usuario $usuario): array
    {
        return DB::table('usuario_rol as ur')
            ->join('roles as r', 'ur.id_rol', '=', 'r.id_rol')
            ->where('ur.id_usuario', $usuario->id_usuario)
            ->pluck('r.nombre')
            ->all();
    }

    private function esSuperadministrador(Usuario $usuario): bool
    {
        return in_array(
            'superadministrador',
            $this->rolesDelUsuario($usuario),
            true
        );
    }

    private function esPersonalAdministrativo(Usuario $usuario): bool
    {
        return count(array_intersect(
            $this->rolesDelUsuario($usuario),
            [
                'superadministrador',
                'administrador',
                'secretaria',
                'sacerdote'
            ]
        )) > 0;
    }

    private function esUsuarioParticular(Usuario $usuario): bool
    {
        return !$this->esPersonalAdministrativo($usuario);
    }

    private function modulosProtegidos(): array
    {
        return [
            'usuarios',
            'roles',
            'usuario_rol',
            'permisos',
            'rol_permiso'
        ];
    }

    private function permisosDelUsuario(Usuario $usuario): array
    {
        return DB::table('usuario_rol as ur')
            ->join('rol_permiso as rp', 'ur.id_rol', '=', 'rp.id_rol')
            ->join('permisos as p', 'rp.id_permiso', '=', 'p.id_permiso')
            ->where('ur.id_usuario', $usuario->id_usuario)
            ->select('p.modulo', 'p.accion')
            ->distinct()
            ->get()
            ->map(fn ($p) => [
                'modulo' => $p->modulo,
                'accion' => $p->accion
            ])
            ->all();
    }

    private function tienePermiso(
        Usuario $usuario,
        string $module,
        string $accion
    ): bool {
        if ($this->esSuperadministrador($usuario)) {
            return true;
        }

        if (in_array($module, $this->modulosProtegidos(), true)) {
            return false;
        }

        return DB::table('usuario_rol as ur')
            ->join('rol_permiso as rp', 'ur.id_rol', '=', 'rp.id_rol')
            ->join('permisos as p', 'rp.id_permiso', '=', 'p.id_permiso')
            ->where('ur.id_usuario', $usuario->id_usuario)
            ->where('p.modulo', $module)
            ->where('p.accion', $accion)
            ->exists();
    }

    private function modulosPermitidos(): array
    {
        $usuario = $this->usuarioActual();
        $schema = self::schema();

        if ($this->esSuperadministrador($usuario)) {
            return array_keys($schema);
        }

        $modulos = [];

        foreach ($this->permisosDelUsuario($usuario) as $permiso) {
            if (
                $permiso['accion'] === 'ver' &&
                isset($schema[$permiso['modulo']]) &&
                !in_array(
                    $permiso['modulo'],
                    $this->modulosProtegidos(),
                    true
                )
            ) {
                $modulos[] = $permiso['modulo'];
            }
        }

        return array_values(array_unique($modulos));
    }

    private function convertirAccion(string $action): string
    {
        return match ($action) {
            'index', 'show' => 'ver',
            'create', 'store' => 'crear',
            'edit', 'update' => 'editar',
            'destroy' => 'eliminar',
            default => abort(403, 'Acción no autorizada.')
        };
    }

    private function autorizarPermiso(
        string $module,
        string $action
    ): void {
        $usuario = $this->usuarioActual();

        $this->context($module);

        if ($this->esSuperadministrador($usuario)) {
            return;
        }

        abort_if(
            in_array($module, $this->modulosProtegidos(), true),
            403,
            'Módulo exclusivo del superadministrador.'
        );

        $accion = $this->convertirAccion($action);

        abort_unless(
            $this->tienePermiso($usuario, $module, 'ver'),
            403,
            'No tienes acceso a este módulo.'
        );

        abort_unless(
            $this->tienePermiso($usuario, $module, $accion),
            403,
            'No tienes permiso para realizar esta operación.'
        );
    }

    // =====================================================
    // PROPIEDAD DE REGISTROS
    // =====================================================

    private function solicitudesAutorizadas(Usuario $usuario): Builder
    {
        $idUsuario = $usuario->id_usuario;
        $idPersona = $usuario->id_persona;

        return DB::table('solicitudes_bautismo as sb')
            ->select('sb.id_solicitud')
            ->where(function ($q) use ($idUsuario, $idPersona) {
                $q->where('sb.creado_por', $idUsuario)
                    ->orWhereExists(function ($sub) use ($idPersona) {
                        $sub->selectRaw('1')
                            ->from('solicitud_tutores as st')
                            ->join(
                                'padres_tutores as pt',
                                'pt.id_padre_tutor',
                                '=',
                                'st.id_padre_tutor'
                            )
                            ->whereColumn(
                                'st.id_solicitud',
                                'sb.id_solicitud'
                            )
                            ->where('pt.id_persona', $idPersona);
                    });
            });
    }

    private function aplicarAlcance(
        Builder $query,
        string $module,
        Usuario $usuario
    ): Builder {
        if (!$this->esUsuarioParticular($usuario)) {
            return $query;
        }

        $idPersona = $usuario->id_persona;
        $idUsuario = $usuario->id_usuario;

        switch ($module) {
            case 'personas':
                $query->where('id_persona', $idPersona);
                break;

            case 'direcciones':
            case 'padres_tutores':
            case 'padrinos':
            case 'bautizados':
                $query->where('id_persona', $idPersona);
                break;

            case 'solicitudes_bautismo':
                $query->whereIn(
                    'id_solicitud',
                    $this->solicitudesAutorizadas($usuario)
                );
                break;

            case 'solicitud_tutores':
            case 'solicitud_padrinos':
            case 'documentos':
            case 'citas':
                $query->whereIn(
                    'id_solicitud',
                    $this->solicitudesAutorizadas($usuario)
                );
                break;

            case 'historial_citas':
                $query->whereIn(
                    'id_cita',
                    DB::table('citas')
                        ->select('id_cita')
                        ->whereIn(
                            'id_solicitud',
                            $this->solicitudesAutorizadas($usuario)
                        )
                );
                break;

            case 'bautismos':
                $query->whereIn(
                    'id_cita',
                    DB::table('citas')
                        ->select('id_cita')
                        ->whereIn(
                            'id_solicitud',
                            $this->solicitudesAutorizadas($usuario)
                        )
                );
                break;

            case 'sacerdotes':
            case 'secretarias':
                $query->whereRaw('1 = 0');
                break;

            case 'horarios_bautizo':
            case 'estados_solicitud':
            case 'tipos_documento':
                // Catálogos generales: requieren permiso de módulo.
                break;

            default:
                // Denegar por defecto los módulos no contemplados.
                $query->whereRaw('1 = 0');
                break;
        }

        return $query;
    }

    private function consultaVisible(string $module): Builder
    {
        $usuario = $this->usuarioActual();

        return $this->aplicarAlcance(
            DB::table($module),
            $module,
            $usuario
        );
    }

    private function query(
        string $module,
        string $token
    ): Builder {
        $meta = $this->context($module)['meta'];

        $decoded = base64_decode(
            strtr($token, '-_', '+/'),
            true
        );

        abort_unless($decoded !== false, 404);

        $keys = json_decode($decoded, true);

        abort_unless(
            is_array($keys) &&
            count($keys) === count($meta['keys']) &&
            empty(array_diff($meta['keys'], array_keys($keys))) &&
            empty(array_diff(array_keys($keys), $meta['keys'])),
            404,
            'Identificador inválido.'
        );

        $query = $this->consultaVisible($module);

        foreach ($meta['keys'] as $key) {
            $value = $keys[$key];

            abort_unless(
                is_scalar($value) && !is_bool($value),
                404
            );

            $query->where($key, $value);
        }

        return $query;
    }

    // =====================================================
    // VALIDACIÓN DE RELACIONES Y PROPIEDAD
    // =====================================================

    private function validarRelacion(
        Usuario $usuario,
        string $tabla,
        string $clave,
        mixed $valor
    ): void {
        if ($valor === null || $valor === '') {
            return;
        }

        if (!$this->esUsuarioParticular($usuario)) {
            return;
        }

        $consulta = $this->aplicarAlcance(
            DB::table($tabla),
            $tabla,
            $usuario
        );

        abort_unless(
            $consulta->where($clave, $valor)->exists(),
            403,
            'No puedes utilizar un registro relacionado que no te pertenece.'
        );
    }

    private function validarPropiedadDatos(
        string $module,
        array &$data,
        ?object $row
    ): void {
        $usuario = $this->usuarioActual();

        if (!$this->esUsuarioParticular($usuario)) {
            return;
        }

        // Los usuarios particulares no administran
        // catálogos ni registros de personal.
        if (in_array($module, [
            'sacerdotes',
            'secretarias',
            'horarios_bautizo',
            'estados_solicitud',
            'tipos_documento',
            'historial_citas',
            'bautismos'
        ], true)) {
            abort(403, 'Este registro requiere personal autorizado.');
        }

        // Las personas solo pueden modificar su propia ficha.
        if ($module === 'personas') {
            abort_unless(
                $row &&
                (string) $row->id_persona ===
                    (string) $usuario->id_persona,
                403,
                'No puedes administrar otras personas.'
            );
        }

        // No permitir crear perfiles personales
        // de terceros mediante el CRUD genérico.
        if (in_array($module, [
            'direcciones',
            'padres_tutores',
            'padrinos',
            'bautizados'
        ], true)) {
            $data['id_persona'] = $usuario->id_persona;
        }

        // Las solicitudes nuevas pertenecen
        // al usuario autenticado.
        if ($module === 'solicitudes_bautismo') {
            if ($row === null) {
                $data['creado_por'] = $usuario->id_usuario;
            } else {
                $data['creado_por'] = $row->creado_por;
                $data['id_bautizado'] = $row->id_bautizado;
                $data['id_estado_solicitud'] =
                    $row->id_estado_solicitud;
            }
        }

        // No permitir cambiar la solicitud a la
        // que pertenece un registro existente.
        if (
            $row &&
            in_array($module, [
                'solicitud_tutores',
                'solicitud_padrinos',
                'documentos',
                'citas'
            ], true)
        ) {
            $data['id_solicitud'] = $row->id_solicitud;
        }

        // Las revisiones documentales las realiza
        // exclusivamente el personal autorizado.
        if ($module === 'documentos') {
            if ($row === null) {
                $data['estado_revision'] = 'pendiente';
                $data['revisado_por'] = null;
                $data['fecha_revision'] = null;
                $data['observaciones_revision'] = null;
            } else {
                foreach ([
                    'estado_revision',
                    'revisado_por',
                    'fecha_revision',
                    'observaciones_revision'
                ] as $campo) {
                    $data[$campo] = $row->{$campo};
                }
            }
        }

        // Un usuario particular no puede modificar
        // directamente el estado de las citas.
        if ($module === 'citas') {
            if ($row === null) {
                $data['estado'] = 'programada';
            } else {
                $data['estado'] = $row->estado;
            }
        }

        // Las claves foráneas deben pertenecer al
        // ámbito de registros autorizado.
        $meta = $this->context($module)['meta'];

        foreach (($meta['foreign'] ?? []) as $field => $fk) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $this->validarRelacion(
                $usuario,
                $fk['table'],
                $fk['key'],
                $data[$field]
            );
        }
    }

    // =====================================================
    // DASHBOARD
    // =====================================================

    public function dashboard()
    {
        $schema = self::schema();

        $modules = [];
        $counts = [];

        foreach ($this->modulosPermitidos() as $name) {
            if (!isset($schema[$name])) {
                continue;
            }

            $modules[$name] = $schema[$name];

            $counts[$name] = $this->consultaVisible($name)->count();
        }

        return view(
            'crud.dashboard',
            compact('modules', 'counts')
        );
    }

    // =====================================================
    // LISTADO
    // =====================================================

    public function index(Request $request, string $module)
    {
        $this->autorizarPermiso($module, 'index');

        $context = $this->context($module);

        $query = $this->consultaVisible($module);

        $search = trim((string) $request->query('q', ''));

        if ($search !== '') {
            $query->where(function ($q) use ($context, $search) {
                foreach (
                    $context['meta']['columns'] as $column => $field
                ) {
                    if (
                        in_array(
                            $field['type'],
                            ['varchar', 'text', 'enum'],
                            true
                        ) &&
                        $column !== 'contrasena'
                    ) {
                        $q->orWhere(
                            $column,
                            'like',
                            '%' . $search . '%'
                        );
                    }
                }
            });
        }

        $rows = $query
            ->orderBy(
                $context['meta']['keys'][0],
                'desc'
            )
            ->paginate(12)
            ->withQueryString();

        return view(
            'crud.index',
            $context + compact('rows', 'search')
        );
    }

    // =====================================================
    // CREAR Y EDITAR
    // =====================================================

    public function create(string $module)
    {
        $this->autorizarPermiso($module, 'create');

        return $this->form($module);
    }

    public function edit(string $module, string $record)
    {
        $this->autorizarPermiso($module, 'edit');

        return $this->form($module, $record);
    }

    private function form(
        string $module,
        ?string $record = null
    ) {
        $context = $this->context($module);

        $row = $record !== null
            ? $this->query($module, $record)->first()
            : null;

        if ($record !== null) {
            abort_unless($row, 404);
        }

        $usuario = $this->usuarioActual();

        $options = [];

        foreach (
            ($context['meta']['foreign'] ?? []) as $field => $fk
        ) {
            $tabla = $fk['table'];

            $consulta = $this->aplicarAlcance(
                DB::table($tabla),
                $tabla,
                $usuario
            );

            $options[$field] = $consulta
                ->get()
                ->mapWithKeys(function ($r) use ($fk) {
                    return [
                        $r->{$fk['key']} =>
                            self::display($r) .
                            ' (#' . $r->{$fk['key']} . ')'
                    ];
                })
                ->all();
        }

        return view(
            'crud.form',
            $context + compact('row', 'record', 'options')
        );
    }

    // =====================================================
    // DETALLES
    // =====================================================

    public function show(string $module, string $record)
    {
        $this->autorizarPermiso($module, 'show');

        $context = $this->context($module);

        $row = $this->query($module, $record)->first();

        abort_unless($row, 404);

        return view(
            'crud.show',
            $context + compact('row', 'record')
        );
    }

    // =====================================================
    // VALIDACIÓN DE DATOS
    // =====================================================

    private function data(
        Request $request,
        string $module,
        ?object $row
    ): array {
        $meta = $this->context($module)['meta'];

        $usuario = $this->usuarioActual();

        $rules = [];

        foreach ($meta['columns'] as $name => $field) {
            if (
                !empty($field['auto']) ||
                !empty($field['generated'])
            ) {
                continue;
            }

            $esContrasena = $name === 'contrasena';

            $obligatorio = !empty($field['required']);

            if ($row && $esContrasena) {
                $obligatorio = false;
            }

            // Campos gestionados por el servidor.
            if (
                $this->esUsuarioParticular($usuario) &&
                $module === 'solicitudes_bautismo' &&
                $name === 'creado_por'
            ) {
                continue;
            }

            $rules[$name] = [
                $obligatorio ? 'required' : 'nullable'
            ];

            $tipo = $field['type'];

            $rules[$name][] = match ($tipo) {
                'int', 'bigint' => 'integer',
                'boolean' => 'boolean',
                'date', 'datetime' => 'date',
                'time' => 'date_format:H:i',
                default => 'string'
            };

            if (
                !empty($field['length']) &&
                !in_array(
                    $tipo,
                    ['int', 'bigint', 'boolean'],
                    true
                )
            ) {
                $rules[$name][] = 'max:' . $field['length'];
            }

            if (!empty($field['enum'])) {
                $rules[$name][] = Rule::in($field['enum']);
            }

            if (isset($meta['foreign'][$name])) {
                $fk = $meta['foreign'][$name];

                $rules[$name][] = Rule::exists(
                    $fk['table'],
                    $fk['key']
                );
            }

            if ($name === 'correo') {
                $rules[$name][] = 'email';
            }

            if ($name === 'curp') {
                $rules[$name][] = 'size:18';
            }

            if ($esContrasena) {
                $rules[$name][] = 'min:8';
            }

            if (in_array($tipo, ['int', 'bigint'], true)) {
                $rules[$name][] = 'min:0';
            }
        }

        if ($module === 'horarios_bautizo') {
            if (isset($rules['capacidad'])) {
                $rules['capacidad'][] = 'min:1';
            }

            if (isset($rules['hora_fin'])) {
                $rules['hora_fin'][] = 'after:hora_inicio';
            }
        }

        $nombres = [];

        foreach (array_keys($rules) as $key) {
            $nombres[$key] = self::label($key);
        }

        $data = $request->validate($rules, [], $nombres);

        // La contraseña vacía en edición
        // no reemplaza la existente.
        if (array_key_exists('contrasena', $data)) {
            if (
                is_string($data['contrasena']) &&
                $data['contrasena'] !== ''
            ) {
                $data['contrasena'] = Hash::make(
                    $data['contrasena']
                );
            } else {
                unset($data['contrasena']);
            }
        }

        $this->validarPropiedadDatos($module, $data, $row);

        // Comprobar restricciones UNIQUE.
        $groups = $meta['unique'] ?? [];

        if (count($meta['keys']) > 1) {
            $groups[] = $meta['keys'];
        }

        foreach ($groups as $columns) {
            if (is_string($columns)) {
                $columns = [$columns];
            }

            $query = DB::table($module);
            $skip = false;

            foreach ($columns as $column) {
                if (
                    !array_key_exists($column, $data) ||
                    $data[$column] === null
                ) {
                    $skip = true;
                    break;
                }

                $query->where($column, $data[$column]);
            }

            if ($skip) {
                continue;
            }

            if ($row) {
                $query->where(function ($q) use ($meta, $row) {
                    foreach ($meta['keys'] as $key) {
                        $q->orWhere(
                            $key,
                            '!=',
                            $row->{$key}
                        );
                    }
                });
            }

            if ($query->exists()) {
                throw ValidationException::withMessages([
                    $columns[0] =>
                        'Ya existe un registro con esta combinación de datos.'
                ]);
            }
        }

        if (
            $row &&
            isset($meta['columns']['fecha_actualizacion'])
        ) {
            $data['fecha_actualizacion'] = now();
        }

        return $data;
    }

    // =====================================================
    // GUARDAR
    // =====================================================

    public function store(Request $request, string $module)
    {
        $this->autorizarPermiso($module, 'store');

        $data = $this->data($request, $module, null);

        try {
            DB::table($module)->insert($data);
        } catch (QueryException $e) {
            return $this->failure($e);
        }

        return redirect()
            ->route('crud.index', $module)
            ->with('success', 'Registro creado correctamente.');
    }

    // =====================================================
    // ACTUALIZAR
    // =====================================================

    public function update(
        Request $request,
        string $module,
        string $record
    ) {
        $this->autorizarPermiso($module, 'update');

        $row = $this->query($module, $record)->first();

        abort_unless($row, 404);

        $data = $this->data($request, $module, $row);

        try {
            $this->query($module, $record)->update($data);
        } catch (QueryException $e) {
            return $this->failure($e);
        }

        return redirect()
            ->route('crud.index', $module)
            ->with('success', 'Registro actualizado correctamente.');
    }

    // =====================================================
    // ELIMINAR
    // =====================================================

    public function destroy(string $module, string $record)
    {
        $this->autorizarPermiso($module, 'destroy');

        $usuario = $this->usuarioActual();

        // Evitar borrados de información familiar
        // mediante cascadas por usuarios particulares.
        if (
            $this->esUsuarioParticular($usuario) &&
            in_array($module, [
                'personas',
                'padres_tutores',
                'padrinos',
                'bautizados',
                'solicitudes_bautismo',
                'solicitud_tutores',
                'solicitud_padrinos',
                'documentos',
                'citas',
                'historial_citas',
                'bautismos'
            ], true)
        ) {
            abort(
                403,
                'La eliminación de estos registros requiere personal autorizado.'
            );
        }

        $query = $this->query($module, $record);

        abort_unless($query->exists(), 404);

        try {
            $query->delete();
        } catch (QueryException $e) {
            return $this->failure($e);
        }

        return redirect()
            ->route('crud.index', $module)
            ->with('success', 'Registro eliminado correctamente.');
    }

    // =====================================================
    // ERRORES DE MYSQL
    // =====================================================

    private function failure(QueryException $e)
    {
        $codigo = (string) $e->getCode();

        if (!str_starts_with($codigo, '23')) {
            throw $e;
        }

        return back()
            ->withInput()
            ->withErrors([
                'registro' =>
                    'No se pudo completar la operación. ' .
                    'Puede existir un dato duplicado o ' .
                    'un registro relacionado en otra tabla.'
            ]);
    }
}
