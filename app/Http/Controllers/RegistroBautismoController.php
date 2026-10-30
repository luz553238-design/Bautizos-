<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Throwable;

class RegistroBautismoController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | AUTORIZACIÓN
    |--------------------------------------------------------------------------
    */

    private function autorizar(): void
    {
        $usuario = Auth::user();

        abort_unless($usuario, 401);

        abort_unless(
            (bool) $usuario->activo,
            403,
            'Tu cuenta está desactivada.'
        );

        $autorizado = DB::table('usuario_rol as ur')
            ->join('roles as r', 'r.id_rol', '=', 'ur.id_rol')
            ->where('ur.id_usuario', $usuario->id_usuario)
            ->whereIn(
                DB::raw('LOWER(TRIM(r.nombre))'),
                ['superadministrador', 'administrador']
            )
            ->exists();

        abort_unless(
            $autorizado,
            403,
            'No tienes permiso para registrar bautismos.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | LISTADO DE SOLICITUDES APROBADAS
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $this->autorizar();

        $buscar = trim((string) $request->input('buscar', ''));

        $solicitudes = DB::table('solicitudes_bautismo as s')
            ->join(
                'estados_solicitud as e',
                'e.id_estado_solicitud',
                '=',
                's.id_estado_solicitud'
            )
            ->join(
                'citas as c',
                'c.id_solicitud',
                '=',
                's.id_solicitud'
            )
            ->join(
                'horarios_bautizo as h',
                'h.id_horario',
                '=',
                'c.id_horario'
            )
            ->leftJoin(
                'bautismos as b',
                'b.id_cita',
                '=',
                'c.id_cita'
            )
            ->whereRaw(
                'LOWER(TRIM(e.nombre)) IN (?, ?)',
                ['aprobada', 'autorizada']
            )
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(
                    's.folio',
                    'like',
                    '%' . $buscar . '%'
                );
            })
            ->select(
                's.id_solicitud',
                's.folio',
                'c.id_cita',
                'c.estado as estado_cita',
                'h.fecha',
                'h.hora_inicio',
                'b.id_bautismo',
                'b.folio_acta'
            )
            ->orderByDesc('h.fecha')
            ->paginate(10)
            ->withQueryString();

        return view(
            'bautismos.index',
            compact('solicitudes', 'buscar')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MOSTRAR FORMULARIO
    |--------------------------------------------------------------------------
    */

    public function crear(int $id)
    {
        $this->autorizar();

        $solicitud = DB::table('solicitudes_bautismo as s')
            ->join(
                'estados_solicitud as e',
                'e.id_estado_solicitud',
                '=',
                's.id_estado_solicitud'
            )
            ->join(
                'citas as c',
                'c.id_solicitud',
                '=',
                's.id_solicitud'
            )
            ->join(
                'horarios_bautizo as h',
                'h.id_horario',
                '=',
                'c.id_horario'
            )
            ->where('s.id_solicitud', $id)
            ->select(
                's.id_solicitud',
                's.folio',
                'e.nombre as estado_solicitud',
                'c.id_cita',
                'c.estado as estado_cita',
                'h.fecha',
                'h.hora_inicio'
            )
            ->first();

        abort_unless($solicitud, 404);

        abort_unless(
            in_array(
                mb_strtolower(trim($solicitud->estado_solicitud)),
                ['aprobada', 'autorizada'],
                true
            ),
            403,
            'La solicitud no está aprobada.'
        );

        abort_if(
            in_array(
                $solicitud->estado_cita,
                ['cancelada', 'realizada'],
                true
            ),
            403,
            'Esta cita no admite nuevos registros.'
        );

        $registrado = DB::table('bautismos')
            ->where('id_cita', $solicitud->id_cita)
            ->exists();

        abort_if(
            $registrado,
            403,
            'Este bautismo ya fue registrado.'
        );

        return view(
            'bautismos.crear',
            compact('solicitud')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GUARDAR BAUTISMO REALIZADO
    |--------------------------------------------------------------------------
    */

    public function guardar(Request $request, int $id)
    {
        $this->autorizar();

        $datos = $request->validate([
            'fecha_realizacion' => [
                'required',
                'date',
                'before_or_equal:today'
            ],
            'folio_acta' => [
                'required',
                'string',
                'max:50'
            ],
            'numero_libro' => [
                'required',
                'string',
                'max:50'
            ],
            'numero_pagina' => [
                'required',
                'string',
                'max:50'
            ],
            'observaciones' => [
                'nullable',
                'string',
                'max:5000'
            ]
        ]);

        DB::transaction(function () use ($id, $datos) {

            /*
            | Bloquear solicitud para evitar registros simultáneos.
            */

            $solicitud = DB::table('solicitudes_bautismo')
                ->where('id_solicitud', $id)
                ->lockForUpdate()
                ->first();

            if (!$solicitud) {
                throw ValidationException::withMessages([
                    'solicitud' => 'La solicitud no existe.'
                ]);
            }

            $estado = DB::table('estados_solicitud')
                ->where(
                    'id_estado_solicitud',
                    $solicitud->id_estado_solicitud
                )
                ->value('nombre');

            if (!in_array(
                mb_strtolower(trim((string) $estado)),
                ['aprobada', 'autorizada'],
                true
            )) {
                throw ValidationException::withMessages([
                    'solicitud' => 'La solicitud no está aprobada.'
                ]);
            }

            $cita = DB::table('citas')
                ->where('id_solicitud', $id)
                ->lockForUpdate()
                ->first();

            if (!$cita) {
                throw ValidationException::withMessages([
                    'solicitud' => 'No existe una cita para esta solicitud.'
                ]);
            }

            if (!in_array(
                $cita->estado,
                ['programada', 'confirmada', 'reprogramada'],
                true
            )) {
                throw ValidationException::withMessages([
                    'solicitud' => 'La cita no está disponible para registrar el bautismo.'
                ]);
            }

            $horario = DB::table('horarios_bautizo')
                ->where('id_horario', $cita->id_horario)
                ->first();

            if (!$horario) {
                throw ValidationException::withMessages([
                    'solicitud' => 'No se encontró el horario de la ceremonia.'
                ]);
            }

            $fechaReal = Carbon::parse(
                $datos['fecha_realizacion']
            )->startOfDay();

            $fechaProgramada = Carbon::parse(
                $horario->fecha
            )->startOfDay();

            if ($fechaReal->lt($fechaProgramada)) {
                throw ValidationException::withMessages([
                    'fecha_realizacion' =>
                        'La fecha de realización no puede ser anterior a la fecha programada.'
                ]);
            }

            if (DB::table('bautismos')
                ->where('id_cita', $cita->id_cita)
                ->exists()) {

                throw ValidationException::withMessages([
                    'solicitud' => 'Este bautismo ya fue registrado.'
                ]);
            }

            if (DB::table('bautismos')
                ->where('folio_acta', trim($datos['folio_acta']))
                ->exists()) {

                throw ValidationException::withMessages([
                    'folio_acta' => 'El folio del acta ya existe.'
                ]);
            }

            DB::table('bautismos')->insert([
                'id_cita' => $cita->id_cita,
                'fecha_realizacion' => $datos['fecha_realizacion'],
                'folio_acta' => trim($datos['folio_acta']),
                'numero_libro' => trim($datos['numero_libro']),
                'numero_pagina' => trim($datos['numero_pagina']),
                'observaciones' => $datos['observaciones'] ?? null,
                'registrado_por' => Auth::user()->id_usuario,
                'fecha_registro' => now()
            ]);

            DB::table('citas')
                ->where('id_cita', $cita->id_cita)
                ->update([
                    'estado' => 'realizada',
                    'fecha_actualizacion' => now()
                ]);
        }, 3);

        return redirect()
            ->route('bautismos.index')
            ->with(
                'success',
                'El bautismo se registró correctamente.'
            );
    }
    
    /*
    |--------------------------------------------------------------------------
    | HISTORIAL DE BAUTISMOS REGISTRADOS
    |--------------------------------------------------------------------------
    */

    public function historial(Request $request)
    {
        $this->autorizar();

        $buscar = trim((string) $request->input('buscar', ''));
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');

        $request->validate([
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
        ]);

        $bautismos = DB::table('bautismos as b')
            ->join('citas as c', 'c.id_cita', '=', 'b.id_cita')
            ->join(
                'solicitudes_bautismo as s',
                's.id_solicitud',
                '=',
                'c.id_solicitud'
            )
            ->join(
                'bautizados as bz',
                'bz.id_bautizado',
                '=',
                's.id_bautizado'
            )
            ->join(
                'personas as p',
                'p.id_persona',
                '=',
                'bz.id_persona'
            )
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('b.folio_acta', 'like', "%{$buscar}%")
                        ->orWhere('s.folio', 'like', "%{$buscar}%")
                        ->orWhere('p.nombre', 'like', "%{$buscar}%")
                        ->orWhere('p.apellido_paterno', 'like', "%{$buscar}%")
                        ->orWhere('p.apellido_materno', 'like', "%{$buscar}%");
                });
            })
            ->when($fechaDesde, function ($query) use ($fechaDesde) {
                $query->whereDate(
                    'b.fecha_realizacion',
                    '>=',
                    $fechaDesde
                );
            })
            ->when($fechaHasta, function ($query) use ($fechaHasta) {
                $query->whereDate(
                    'b.fecha_realizacion',
                    '<=',
                    $fechaHasta
                );
            })
            ->select(
                'b.id_bautismo',
                'b.folio_acta',
                'b.fecha_realizacion',
                'b.numero_libro',
                'b.numero_pagina',
                'b.fecha_registro',
                's.folio as folio_solicitud',
                'p.nombre',
                'p.apellido_paterno',
                'p.apellido_materno'
            )
            ->orderByDesc('b.fecha_realizacion')
            ->orderByDesc('b.id_bautismo')
            ->paginate(10)
            ->withQueryString();

        $totalBautismos = DB::table('bautismos')->count();

        return view(
            'bautismos.historial',
            compact(
                'bautismos',
                'totalBautismos',
                'buscar',
                'fechaDesde',
                'fechaHasta'
            )
        );
    }

}
