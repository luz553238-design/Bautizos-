<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SolicitudBautismoController extends Controller
{
    private const HORARIOS = ['11:00', '12:00', '13:00'];
    private const CUPO_MAXIMO = 2;

    // =====================================================
    // USUARIO AUTENTICADO
    // =====================================================

    private function usuarioId(): int
    {
        abort_unless(Auth::check(), 401);

        return (int) Auth::user()->id_usuario;
    }

    private function esPersonalAutorizado(): bool
    {
        return DB::table('usuario_rol as ur')
            ->join('roles as r', 'r.id_rol', '=', 'ur.id_rol')
            ->where('ur.id_usuario', $this->usuarioId())
            ->whereIn(DB::raw('LOWER(r.nombre)'), [
                'sacerdote',
                'superadministrador',
                'superadmin',
            ])
            ->exists();
    }

    private function solicitudPropia(int $id): object
    {
        $solicitud = DB::table('solicitudes_bautismo')
            ->where('id_solicitud', $id)
            ->where('creado_por', $this->usuarioId())
            ->first();

        abort_unless(
            $solicitud,
            404,
            'La solicitud no existe o no te pertenece.'
        );

        return $solicitud;
    }

    // =====================================================
    // VALIDAR FECHA DE CEREMONIA
    // =====================================================

    private function validarFechaCeremonia(
        string $fecha,
        string $hora
    ): void {
        if (!in_array($hora, self::HORARIOS, true)) {
            throw ValidationException::withMessages([
                'hora_ceremonia' =>
                    'Solo se permiten los horarios 11:00, 12:00 y 13:00.',
            ]);
        }

        $fechaHora = Carbon::parse($fecha . ' ' . $hora);

        if (!$fechaHora->isFuture()) {
            throw ValidationException::withMessages([
                'fecha_ceremonia' =>
                    'Selecciona una fecha y hora futuras.',
            ]);
        }
    }

    // =====================================================
    // CONTAR BAUTISMOS POR HORARIO
    // =====================================================

    private function ocupacionHorario(
        int $idHorario,
        ?int $excluirSolicitud = null
    ): int {
        return DB::table('citas as c')
            ->join(
                'solicitudes_bautismo as s',
                's.id_solicitud',
                '=',
                'c.id_solicitud'
            )
            ->join(
                'estados_solicitud as e',
                'e.id_estado_solicitud',
                '=',
                's.id_estado_solicitud'
            )
            ->where('c.id_horario', $idHorario)
            ->whereNotIn('c.estado', [
                'cancelada',
                'reprogramada',
            ])
            ->whereNotIn(DB::raw('LOWER(e.nombre)'), [
                'rechazada',
                'cancelada',
            ])
            ->when(
                $excluirSolicitud !== null,
                function ($consulta) use ($excluirSolicitud) {
                    $consulta->where(
                        'c.id_solicitud',
                        '!=',
                        $excluirSolicitud
                    );
                }
            )
            ->count();
    }

    // =====================================================
    // OBTENER O CREAR HORARIO
    // =====================================================

    private function obtenerOCrearHorario(
        string $fecha,
        string $hora
    ): object {
        $this->validarFechaCeremonia($fecha, $hora);

        $inicio = Carbon::parse($fecha . ' ' . $hora);
        $fin = $inicio->copy()->addHour();

        DB::table('horarios_bautizo')->insertOrIgnore([
            'fecha' => $fecha,
            'hora_inicio' => $inicio->format('H:i:s'),
            'hora_fin' => $fin->format('H:i:s'),
            'capacidad' => self::CUPO_MAXIMO,
            'disponible' => true,
        ]);

        $horario = DB::table('horarios_bautizo')
            ->where('fecha', $fecha)
            ->where('hora_inicio', $inicio->format('H:i:s'))
            ->lockForUpdate()
            ->first();

        abort_unless(
            $horario,
            500,
            'No se pudo obtener el horario.'
        );

        if (!$horario->disponible) {
            throw ValidationException::withMessages([
                'hora_ceremonia' =>
                    'El horario seleccionado no está disponible.',
            ]);
        }

        return $horario;
    }

    // =====================================================
    // VALIDAR CUPO MÁXIMO DE DOS BAUTISMOS
    // =====================================================

    private function validarCupo(
        object $horario,
        ?int $excluirSolicitud = null
    ): void {
        $ocupados = $this->ocupacionHorario(
            (int) $horario->id_horario,
            $excluirSolicitud
        );

        $capacidad = min(
            self::CUPO_MAXIMO,
            (int) $horario->capacidad
        );

        if ($ocupados >= $capacidad) {
            throw ValidationException::withMessages([
                'hora_ceremonia' =>
                    'Este horario ya alcanzó su capacidad máxima de ' .
                    '2 bautismos. Selecciona otra fecha u horario.',
            ]);
        }
    }

    // =====================================================
    // VALIDAR PLÁTICA PREBAUTISMAL
    // =====================================================

    private function validarPlatica(
        int $idPlatica,
        string $fechaCeremonia,
        bool $exigirFutura = true
    ): object {
        $platica = DB::table('platicas_prebautismales')
            ->where('id_platica', $idPlatica)
            ->lockForUpdate()
            ->first();

        if (!$platica || !$platica->disponible) {
            throw ValidationException::withMessages([
                'id_platica' =>
                    'La plática seleccionada no está disponible.',
            ]);
        }

        $fechaPlatica = Carbon::parse(
            $platica->fecha . ' ' . $platica->hora_inicio
        );

        if (
            $fechaPlatica->toDateString() >=
            $fechaCeremonia
        ) {
            throw ValidationException::withMessages([
                'id_platica' =>
                    'La plática debe realizarse antes del día del bautismo.',
            ]);
        }

        if ($exigirFutura && !$fechaPlatica->isFuture()) {
            throw ValidationException::withMessages([
                'id_platica' =>
                    'Debes seleccionar una plática futura.',
            ]);
        }

        return $platica;
    }

    // =====================================================
    // MOSTRAR FORMULARIO
    // =====================================================

    public function crear()
    {
        $platicas = DB::table('platicas_prebautismales')
            ->where('disponible', true)
            ->where(function ($consulta) {
                $consulta
                    ->where('fecha', '>', today()->toDateString())
                    ->orWhere(function ($subconsulta) {
                        $subconsulta
                            ->where(
                                'fecha',
                                today()->toDateString()
                            )
                            ->where(
                                'hora_inicio',
                                '>',
                                now()->format('H:i:s')
                            );
                    });
            })
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get();

        return view('solicitudes.crear', compact('platicas'));
    }

    // =====================================================
    // GUARDAR SOLICITUD
    // =====================================================

    public function guardar(Request $request)
    {
        $data = $request->validate([

            // Datos del menor
            'menor.nombre' => 'required|string|max:100',
            'menor.apellido_paterno' => 'required|string|max:100',
            'menor.apellido_materno' => 'nullable|string|max:100',
            'menor.fecha_nacimiento' =>
                'required|date|before_or_equal:today',
            'menor.sexo' =>
                'required|in:masculino,femenino,otro',
            'menor.lugar_nacimiento' =>
                'required|string|max:150',

            'acta_nacimiento' =>
                'required|file|mimes:pdf|max:20480',

            // Padre o tutor principal
            // Ya NO tiene que coincidir con el usuario autenticado
            'padre.nombre' => 'required|string|max:100',
            'padre.apellido_paterno' =>
                'required|string|max:100',
            'padre.apellido_materno' =>
                'nullable|string|max:100',
            'padre.telefono' =>
                'required|string|max:15',
            'padre.parentesco' => [
                'required',
                Rule::in([
                    'Padre',
                    'Madre',
                    'Tutor legal',
                ]),
            ],

            // Segundo tutor opcional
            'segundo_tutor.nombre' =>
                'nullable|required_with:segundo_tutor.apellido_paterno|string|max:100',
            'segundo_tutor.apellido_paterno' =>
                'nullable|required_with:segundo_tutor.nombre|string|max:100',
            'segundo_tutor.apellido_materno' =>
                'nullable|string|max:100',
            'segundo_tutor.telefono' =>
                'nullable|string|max:15',
            'segundo_tutor.parentesco' =>
                'nullable|string|max:50',

            // Tipo de padrinos
            'tipo_padrinos' =>
                'required|in:pareja,unico',
            'tipo_unico' =>
                'required_if:tipo_padrinos,unico|nullable|in:padrino,madrina',

            // Primer padrino o madrina
            'padrino.nombre' =>
                'required|string|max:100',
            'padrino.apellido_paterno' =>
                'required|string|max:100',
            'padrino.apellido_materno' =>
                'nullable|string|max:100',
            'padrino.telefono' =>
                'required|string|max:15',
            'padrino.estado_civil' =>
                'nullable|string|max:50',

            // Segundo padrino o madrina
            'madrina.nombre' =>
                'required_if:tipo_padrinos,pareja|nullable|string|max:100',
            'madrina.apellido_paterno' =>
                'required_if:tipo_padrinos,pareja|nullable|string|max:100',
            'madrina.apellido_materno' =>
                'nullable|string|max:100',
            'madrina.telefono' =>
                'required_if:tipo_padrinos,pareja|nullable|string|max:15',
            'madrina.estado_civil' =>
                'nullable|string|max:50',

            'acta_matrimonio' =>
                'required_if:tipo_padrinos,pareja|nullable|file|mimes:pdf|max:20480',

            // Ceremonia
            'fecha_ceremonia' =>
                'required|date|after_or_equal:today',
            'hora_ceremonia' => [
                'required',
                Rule::in(self::HORARIOS),
            ],

            // Plática
            'id_platica' =>
                'required|integer|exists:platicas_prebautismales,id_platica',

            'observaciones' =>
                'nullable|string|max:2000',
        ]);

        $archivosGuardados = [];

        try {
            DB::transaction(function () use (
                $request,
                $data,
                &$archivosGuardados
            ) {

                $fecha = $data['fecha_ceremonia'];
                $hora = $data['hora_ceremonia'];

                // -----------------------------------------
                // Validar horario y cupo
                // -----------------------------------------

                $horario = $this->obtenerOCrearHorario(
                    $fecha,
                    $hora
                );

                $this->validarCupo($horario);

                // -----------------------------------------
                // Validar plática
                // -----------------------------------------

                $this->validarPlatica(
                    (int) $data['id_platica'],
                    $fecha
                );

                // -----------------------------------------
                // Estado inicial: Pendiente
                // -----------------------------------------

                $estado = DB::table('estados_solicitud')
                    ->whereRaw(
                        'LOWER(nombre) = ?',
                        ['pendiente']
                    )
                    ->value('id_estado_solicitud');

                if (!$estado) {
                    throw ValidationException::withMessages([
                        'general' =>
                            'No existe el estado Pendiente.',
                    ]);
                }

                // -----------------------------------------
                // Registrar menor
                // -----------------------------------------

                $idMenor = $this->crearPersona(
                    $data['menor'],
                    $data['menor']['fecha_nacimiento'],
                    $data['menor']['sexo']
                );

                $idBautizado = DB::table('bautizados')
                    ->insertGetId([
                        'id_persona' => $idMenor,
                        'lugar_nacimiento' =>
                            $data['menor']['lugar_nacimiento'],
                    ], 'id_bautizado');

                // -----------------------------------------
                // Registrar solicitud
                // -----------------------------------------

                $folio = 'BAU-' .
                    now()->format('YmdHis') .
                    '-' .
                    strtoupper(Str::random(8));

                $idSolicitud = DB::table('solicitudes_bautismo')
                    ->insertGetId([
                        'folio' => $folio,
                        'id_bautizado' => $idBautizado,
                        'fecha_solicitud' =>
                            today()->toDateString(),
                        'id_estado_solicitud' => $estado,
                        'observaciones' =>
                            $data['observaciones'] ?? null,

                        // La cuenta que presenta el trámite.
                        // No tiene que ser padre o tutor.
                        'creado_por' => $this->usuarioId(),
                    ], 'id_solicitud');

                // =========================================
                // RESPONSABLE PRINCIPAL
                // =========================================
                //
                // CORRECCIÓN:
                // Se crea con los datos del formulario.
                // No se compara con la cuenta autenticada.
                // =========================================

                $idPersonaPrincipal = $this->crearPersona(
                    $data['padre']
                );

                $idTutorPrincipal = DB::table('padres_tutores')
                    ->insertGetId([
                        'id_persona' => $idPersonaPrincipal,
                        'activo' => true,
                    ], 'id_padre_tutor');

                DB::table('solicitud_tutores')->insert([
                    'id_solicitud' => $idSolicitud,
                    'id_padre_tutor' => $idTutorPrincipal,
                    'parentesco' =>
                        $data['padre']['parentesco'],
                    'tutor_principal' => true,
                ]);

                // =========================================
                // SEGUNDO TUTOR OPCIONAL
                // =========================================

                if (
                    !empty(
                        $data['segundo_tutor']['nombre']
                    )
                ) {
                    $idSegundo = $this->crearPersona(
                        $data['segundo_tutor']
                    );

                    $idTutorSegundo = DB::table('padres_tutores')
                        ->insertGetId([
                            'id_persona' => $idSegundo,
                            'activo' => true,
                        ], 'id_padre_tutor');

                    DB::table('solicitud_tutores')->insert([
                        'id_solicitud' => $idSolicitud,
                        'id_padre_tutor' => $idTutorSegundo,
                        'parentesco' =>
                            $data['segundo_tutor']['parentesco']
                                ?? 'Tutor',
                        'tutor_principal' => false,
                    ]);
                }

                // =========================================
                // PADRINOS
                // =========================================

                $padrinos = [
                    [
                        'datos' => $data['padrino'],
                        'tipo' =>
                            $data['tipo_padrinos'] === 'unico'
                                ? $data['tipo_unico']
                                : 'padrino',
                    ],
                ];

                if ($data['tipo_padrinos'] === 'pareja') {
                    $padrinos[] = [
                        'datos' => $data['madrina'],
                        'tipo' => 'madrina',
                    ];
                }

                $personasPadrinos = [];

                foreach ($padrinos as $item) {
                    $idPersona = $this->crearPersona(
                        $item['datos']
                    );

                    $personasPadrinos[] = $idPersona;

                    $idPadrino = DB::table('padrinos')
                        ->insertGetId([
                            'id_persona' => $idPersona,
                            'estado_civil' =>
                                $item['datos']['estado_civil']
                                    ?? null,
                            'activo' => true,
                        ], 'id_padrino');

                    DB::table('solicitud_padrinos')->insert([
                        'id_solicitud' => $idSolicitud,
                        'id_padrino' => $idPadrino,
                        'tipo_participacion' =>
                            $item['tipo'],
                    ]);
                }

                // =========================================
                // REGISTRAR CITA
                // =========================================

                DB::table('citas')->insert([
                    'id_solicitud' => $idSolicitud,
                    'id_horario' => $horario->id_horario,
                    'id_sacerdote' => null,
                    'estado' => 'programada',
                ]);

                // =========================================
                // INSCRIPCIÓN A PLÁTICA
                // =========================================

                DB::table('inscripciones_platicas')
                    ->insert([
                        'id_solicitud' => $idSolicitud,
                        'id_platica' =>
                            $data['id_platica'],
                        'estado' => 'inscrita',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                // =========================================
                // ACTA DE NACIMIENTO
                // =========================================

                $this->guardarDocumento(
                    $request->file('acta_nacimiento'),
                    $idSolicitud,
                    $idMenor,
                    'Acta de nacimiento',
                    $archivosGuardados
                );

                // =========================================
                // ACTA DE MATRIMONIO
                // =========================================

                if ($data['tipo_padrinos'] === 'pareja') {
                    $this->guardarDocumento(
                        $request->file('acta_matrimonio'),
                        $idSolicitud,
                        $personasPadrinos[0],
                        'Acta de matrimonio',
                        $archivosGuardados
                    );
                }
            });

        } catch (\Throwable $e) {

            foreach ($archivosGuardados as $ruta) {
                Storage::disk('local')->delete($ruta);
            }

            throw $e;
        }

        return redirect()
            ->route('solicitudes.mis')
            ->with(
                'success',
                'Solicitud registrada correctamente. Estado: Pendiente.'
            );
    }

    // =====================================================
    // CREAR PERSONA
    // =====================================================

    private function crearPersona(
        array $datos,
        ?string $fecha = null,
        ?string $sexo = null
    ): int {
        return DB::table('personas')
            ->insertGetId([
                'nombre' => $datos['nombre'],
                'apellido_paterno' =>
                    $datos['apellido_paterno'],
                'apellido_materno' =>
                    $datos['apellido_materno'] ?? null,
                'fecha_nacimiento' =>
                    $fecha
                        ?? $datos['fecha_nacimiento']
                        ?? null,
                'sexo' =>
                    $sexo
                        ?? $datos['sexo']
                        ?? 'otro',
                'telefono' =>
                    $datos['telefono'] ?? null,
                'activo' => true,
            ], 'id_persona');
    }

    // =====================================================
    // GUARDAR DOCUMENTO PRIVADO
    // =====================================================

    private function guardarDocumento(
        $archivo,
        int $solicitud,
        int $persona,
        string $tipo,
        array &$archivosGuardados
    ): void {
        if (!$archivo || !$archivo->isValid()) {
            throw ValidationException::withMessages([
                'general' =>
                    'El archivo PDF no se recibió correctamente.',
            ]);
        }

        $idTipo = DB::table('tipos_documento')
            ->whereRaw(
                'LOWER(nombre) = ?',
                [mb_strtolower($tipo)]
            )
            ->value('id_tipo_documento');

        if (!$idTipo) {
            throw ValidationException::withMessages([
                'general' =>
                    "Falta configurar el tipo de documento: {$tipo}.",
            ]);
        }

        $ruta = $archivo->store(
            'bautizos/' . $solicitud,
            'local'
        );

        if (!$ruta) {
            throw ValidationException::withMessages([
                'general' =>
                    'No se pudo guardar el archivo PDF.',
            ]);
        }

        $archivosGuardados[] = $ruta;

        DB::table('documentos')->insert([
            'id_solicitud' => $solicitud,
            'id_persona' => $persona,
            'id_tipo_documento' => $idTipo,
            'nombre_archivo' =>
                $archivo->getClientOriginalName(),
            'ruta_archivo' => $ruta,
            'extension' => 'pdf',
            'mime_type' => $archivo->getMimeType(),
            'tamano_bytes' => $archivo->getSize(),
            'estado_revision' => 'pendiente',
        ]);
    }

    // =====================================================
    // MIS SOLICITUDES
    // =====================================================

    public function misSolicitudes()
    {
        $solicitudes = DB::table(
            'solicitudes_bautismo as s'
        )
            ->join(
                'estados_solicitud as e',
                'e.id_estado_solicitud',
                '=',
                's.id_estado_solicitud'
            )
            ->leftJoin(
                'citas as c',
                'c.id_solicitud',
                '=',
                's.id_solicitud'
            )
            ->leftJoin(
                'horarios_bautizo as h',
                'h.id_horario',
                '=',
                'c.id_horario'
            )
            ->where(
                's.creado_por',
                $this->usuarioId()
            )
            ->select(
                's.id_solicitud',
                's.folio',
                's.fecha_solicitud',
                'e.nombre as estado',
                'h.fecha',
                'h.hora_inicio'
            )
            ->orderByDesc('s.id_solicitud')
            ->paginate(10);

        $propuestas = DB::table(
            'propuestas_cambio_cita as p'
        )
            ->join(
                'solicitudes_bautismo as s',
                's.id_solicitud',
                '=',
                'p.id_solicitud'
            )
            ->join(
                'horarios_bautizo as h',
                'h.id_horario',
                '=',
                'p.id_horario_propuesto'
            )
            ->where(
                's.creado_por',
                $this->usuarioId()
            )
            ->where('p.estado', 'pendiente')
            ->select(
                'p.id_propuesta',
                'p.id_solicitud',
                'p.motivo',
                's.folio',
                'h.fecha',
                'h.hora_inicio'
            )
            ->orderByDesc('p.id_propuesta')
            ->get();

        return view(
            'solicitudes.mis',
            compact('solicitudes', 'propuestas')
        );
    }

    // =====================================================
    // DESCARGAR DOCUMENTO PDF
    // =====================================================

    public function descargarDocumento(int $id)
    {
        $documento = DB::table('documentos')
            ->where('id_documento', $id)
            ->first();

        abort_unless(
            $documento,
            404,
            'El documento no existe.'
        );

        if (!$this->esPersonalAutorizado()) {
            $this->solicitudPropia(
                (int) $documento->id_solicitud
            );
        }

        $disco = Storage::disk('local');

        abort_unless(
            $disco->exists($documento->ruta_archivo),
            404,
            'No se encontró el archivo PDF.'
        );

        return response()->download(
            $disco->path($documento->ruta_archivo),
            basename($documento->nombre_archivo),
            [
                'Content-Type' => 'application/pdf',
            ]
        );
    }

    // =====================================================
    // RESPONDER PROPUESTA DE CAMBIO DE CITA
    // =====================================================

    public function responderPropuesta(
        Request $request,
        int $id
    ) {
        $data = $request->validate([
            'respuesta' =>
                'required|in:aceptar,rechazar',
        ]);

        DB::transaction(function () use ($id, $data) {

            $propuesta = DB::table(
                'propuestas_cambio_cita'
            )
                ->where('id_propuesta', $id)
                ->lockForUpdate()
                ->first();

            abort_unless(
                $propuesta,
                404,
                'La propuesta no existe.'
            );

            $this->solicitudPropia(
                (int) $propuesta->id_solicitud
            );

            abort_unless(
                $propuesta->estado === 'pendiente',
                409,
                'Esta propuesta ya fue respondida.'
            );

            if ($data['respuesta'] === 'aceptar') {

                $horario = DB::table('horarios_bautizo')
                    ->where(
                        'id_horario',
                        $propuesta->id_horario_propuesto
                    )
                    ->lockForUpdate()
                    ->first();

                abort_unless(
                    $horario,
                    404,
                    'El horario no existe.'
                );

                $this->validarFechaCeremonia(
                    $horario->fecha,
                    substr($horario->hora_inicio, 0, 5)
                );

                if (!$horario->disponible) {
                    throw ValidationException::withMessages([
                        'respuesta' =>
                            'El horario propuesto no está disponible.',
                    ]);
                }

                $inscripcion = DB::table(
                    'inscripciones_platicas'
                )
                    ->where(
                        'id_solicitud',
                        $propuesta->id_solicitud
                    )
                    ->first();

                if (!$inscripcion) {
                    throw ValidationException::withMessages([
                        'respuesta' =>
                            'La solicitud no tiene una plática registrada.',
                    ]);
                }

                $this->validarPlatica(
                    (int) $inscripcion->id_platica,
                    $horario->fecha,
                    false
                );

                $this->validarCupo(
                    $horario,
                    (int) $propuesta->id_solicitud
                );

                DB::table('citas')
                    ->where(
                        'id_solicitud',
                        $propuesta->id_solicitud
                    )
                    ->update([
                        'id_horario' =>
                            $horario->id_horario,
                        'estado' => 'programada',
                    ]);

                // Cerrar otras propuestas pendientes
                DB::table('propuestas_cambio_cita')
                    ->where(
                        'id_solicitud',
                        $propuesta->id_solicitud
                    )
                    ->where('estado', 'pendiente')
                    ->where(
                        'id_propuesta',
                        '!=',
                        $id
                    )
                    ->update([
                        'estado' => 'rechazada',
                        'respondida_en' => now(),
                        'updated_at' => now(),
                    ]);
            }

            DB::table('propuestas_cambio_cita')
                ->where('id_propuesta', $id)
                ->update([
                    'estado' =>
                        $data['respuesta'] === 'aceptar'
                            ? 'aceptada'
                            : 'rechazada',
                    'respondida_en' => now(),
                    'updated_at' => now(),
                ]);
        });

        return back()->with(
            'success',
            'Tu respuesta fue registrada correctamente.'
        );
    }
}
