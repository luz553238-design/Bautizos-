<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RevisionBautismoController extends Controller
{
    private const HORARIOS = ['11:00', '12:00', '13:00'];
    private const CUPO_MAXIMO = 2;

    /*
    |--------------------------------------------------------------------------
    | VALIDAR PERMISOS DEL SACERDOTE
    |--------------------------------------------------------------------------
    */

    private function autorizar(): void
    {
        abort_unless(Auth::check(), 401);

        $autorizado = DB::table('usuario_rol as ur')
            ->join('roles as r', 'r.id_rol', '=', 'ur.id_rol')
            ->where('ur.id_usuario', Auth::user()->id_usuario)
            ->whereIn(DB::raw('LOWER(r.nombre)'), [
                'sacerdote',
                'superadministrador',
                'superadmin'
            ])
            ->exists();

        abort_unless(
            $autorizado,
            403,
            'No tienes permiso para revisar solicitudes.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | LISTAR SOLICITUDES
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
                'bautizados as b',
                'b.id_bautizado',
                '=',
                's.id_bautizado'
            )
            ->join(
                'personas as p',
                'p.id_persona',
                '=',
                'b.id_persona'
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
            ->when($buscar !== '', function ($consulta) use ($buscar) {
                $consulta->where(function ($q) use ($buscar) {
                    $q->where('s.folio', 'like', "%{$buscar}%")
                        ->orWhere('p.nombre', 'like', "%{$buscar}%")
                        ->orWhere('p.apellido_paterno', 'like', "%{$buscar}%");
                });
            })
            ->select(
                's.id_solicitud',
                's.folio',
                's.fecha_solicitud',
                'e.nombre as estado',
                'p.nombre',
                'p.apellido_paterno',
                'p.apellido_materno',
                'h.fecha',
                'h.hora_inicio'
            )
            ->orderByDesc('s.id_solicitud')
            ->paginate(10)
            ->withQueryString();

        return view(
            'revision.index',
            compact('solicitudes', 'buscar')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MOSTRAR DETALLES DE LA SOLICITUD
    |--------------------------------------------------------------------------
    */

    public function show(int $id)
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
                'bautizados as b',
                'b.id_bautizado',
                '=',
                's.id_bautizado'
            )
            ->join(
                'personas as p',
                'p.id_persona',
                '=',
                'b.id_persona'
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
            ->where('s.id_solicitud', $id)
            ->select(
                's.*',
                'e.nombre as estado',
                'p.nombre',
                'p.apellido_paterno',
                'p.apellido_materno',
                'p.fecha_nacimiento',
                'b.lugar_nacimiento',
                'h.fecha',
                'h.hora_inicio'
            )
            ->first();

        abort_unless($solicitud, 404, 'Solicitud no encontrada.');

        $tutores = DB::table('solicitud_tutores as st')
            ->join(
                'padres_tutores as pt',
                'pt.id_padre_tutor',
                '=',
                'st.id_padre_tutor'
            )
            ->join(
                'personas as p',
                'p.id_persona',
                '=',
                'pt.id_persona'
            )
            ->where('st.id_solicitud', $id)
            ->select(
                'p.nombre',
                'p.apellido_paterno',
                'p.apellido_materno',
                'p.telefono',
                'st.parentesco',
                'st.tutor_principal'
            )
            ->get();

        $padrinos = DB::table('solicitud_padrinos as sp')
            ->join(
                'padrinos as pa',
                'pa.id_padrino',
                '=',
                'sp.id_padrino'
            )
            ->join(
                'personas as p',
                'p.id_persona',
                '=',
                'pa.id_persona'
            )
            ->where('sp.id_solicitud', $id)
            ->select(
                'p.nombre',
                'p.apellido_paterno',
                'p.apellido_materno',
                'p.telefono',
                'pa.estado_civil',
                'sp.tipo_participacion'
            )
            ->get();

        $documentos = DB::table('documentos as d')
            ->join(
                'tipos_documento as t',
                't.id_tipo_documento',
                '=',
                'd.id_tipo_documento'
            )
            ->where('d.id_solicitud', $id)
            ->select(
                'd.id_documento',
                'd.nombre_archivo',
                'd.estado_revision',
                't.nombre as tipo_documento'
            )
            ->get();

        $platica = DB::table('inscripciones_platicas as i')
            ->join(
                'platicas_prebautismales as p',
                'p.id_platica',
                '=',
                'i.id_platica'
            )
            ->where('i.id_solicitud', $id)
            ->select(
                'p.fecha',
                'p.hora_inicio',
                'p.hora_fin',
                'p.lugar',
                'i.estado'
            )
            ->first();

        return view('revision.show', compact(
            'solicitud',
            'tutores',
            'padrinos',
            'documentos',
            'platica'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | APROBAR SOLICITUD
    |--------------------------------------------------------------------------
    */

    public function aprobar(int $id)
    {
        $this->autorizar();

        DB::transaction(function () use ($id) {

            $solicitud = DB::table('solicitudes_bautismo')
                ->where('id_solicitud', $id)
                ->lockForUpdate()
                ->first();

            abort_unless($solicitud, 404);

            $estadoActual = DB::table('estados_solicitud')
                ->where(
                    'id_estado_solicitud',
                    $solicitud->id_estado_solicitud
                )
                ->value('nombre');

            if (mb_strtolower(trim((string) $estadoActual)) !== 'pendiente') {
                throw ValidationException::withMessages([
                    'estado' => 'Solo puedes aprobar solicitudes pendientes.'
                ]);
            }

            $documentosPendientes = DB::table('documentos')
                ->where('id_solicitud', $id)
                ->where('estado_revision', 'rechazado')
                ->exists();

            if ($documentosPendientes) {
                throw ValidationException::withMessages([
                    'documentos' => 'La solicitud tiene documentos rechazados.'
                ]);
            }

            $idEstado = DB::table('estados_solicitud')
                ->whereRaw('LOWER(nombre) = ?', ['aprobada'])
                ->value('id_estado_solicitud');

            if (!$idEstado) {
                throw ValidationException::withMessages([
                    'estado' => 'No existe el estado Aprobada en la base de datos.'
                ]);
            }

            DB::table('solicitudes_bautismo')
                ->where('id_solicitud', $id)
                ->update([
                    'id_estado_solicitud' => $idEstado
                ]);
        });

        return redirect()
            ->route('revision.show', $id)
            ->with('success', 'Solicitud aprobada correctamente.');
    }

    /*
    |--------------------------------------------------------------------------
    | RECHAZAR SOLICITUD
    |--------------------------------------------------------------------------
    */

    public function rechazar(Request $request, int $id)
    {
        $this->autorizar();

        $data = $request->validate([
            'motivo' => 'required|string|min:10|max:1000'
        ]);

        DB::transaction(function () use ($id, $data) {

            $solicitud = DB::table('solicitudes_bautismo')
                ->where('id_solicitud', $id)
                ->lockForUpdate()
                ->first();

            abort_unless($solicitud, 404);

            $idEstado = DB::table('estados_solicitud')
                ->whereRaw('LOWER(nombre) = ?', ['rechazada'])
                ->value('id_estado_solicitud');

            if (!$idEstado) {
                throw ValidationException::withMessages([
                    'estado' => 'No existe el estado Rechazada.'
                ]);
            }

            DB::table('solicitudes_bautismo')
                ->where('id_solicitud', $id)
                ->update([
                    'id_estado_solicitud' => $idEstado,
                    'observaciones' => $data['motivo']
                ]);

            DB::table('citas')
                ->where('id_solicitud', $id)
                ->update([
                    'estado' => 'cancelada'
                ]);
        });

        return redirect()
            ->route('revision.show', $id)
            ->with('success', 'Solicitud rechazada correctamente.');
    }

    /*
    |--------------------------------------------------------------------------
    | PROPONER CAMBIO DE FECHA Y HORARIO
    |--------------------------------------------------------------------------
    */

    public function proponerCambio(Request $request, int $id)
    {
        $this->autorizar();

        $data = $request->validate([
            'fecha' => 'required|date|after_or_equal:today',
            'hora' => [
                'required',
                Rule::in(self::HORARIOS)
            ],
            'motivo' => 'required|string|min:10|max:1000'
        ]);

        DB::transaction(function () use ($id, $data) {

            $solicitud = DB::table('solicitudes_bautismo')
                ->where('id_solicitud', $id)
                ->lockForUpdate()
                ->first();

            abort_unless($solicitud, 404);

            $fechaHora = Carbon::parse(
                $data['fecha'] . ' ' . $data['hora']
            );

            if (!$fechaHora->isFuture()) {
                throw ValidationException::withMessages([
                    'fecha' => 'La nueva fecha y hora deben ser futuras.'
                ]);
            }

            $horaInicio = $fechaHora->format('H:i:s');

            DB::table('horarios_bautizo')->insertOrIgnore([
                'fecha' => $data['fecha'],
                'hora_inicio' => $horaInicio,
                'hora_fin' => $fechaHora->copy()->addHour()->format('H:i:s'),
                'capacidad' => self::CUPO_MAXIMO,
                'disponible' => true
            ]);

            $horario = DB::table('horarios_bautizo')
                ->where('fecha', $data['fecha'])
                ->where('hora_inicio', $horaInicio)
                ->lockForUpdate()
                ->first();

            if (!$horario || !$horario->disponible) {
                throw ValidationException::withMessages([
                    'hora' => 'El horario propuesto no está disponible.'
                ]);
            }

            $ocupacion = DB::table('citas as c')
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
                ->where('c.id_horario', $horario->id_horario)
                ->whereNotIn('c.estado', ['cancelada', 'reprogramada'])
                ->whereNotIn(DB::raw('LOWER(e.nombre)'), [
                    'rechazada',
                    'cancelada'
                ])
                ->where('c.id_solicitud', '!=', $id)
                ->count();

            if ($ocupacion >= min(
                self::CUPO_MAXIMO,
                (int) $horario->capacidad
            )) {
                throw ValidationException::withMessages([
                    'hora' => 'El horario ya tiene dos bautismos registrados.'
                ]);
            }

            $inscripcion = DB::table('inscripciones_platicas')
                ->where('id_solicitud', $id)
                ->first();

            if ($inscripcion) {
                $platica = DB::table('platicas_prebautismales')
                    ->where('id_platica', $inscripcion->id_platica)
                    ->first();

                if ($platica && $platica->fecha >= $data['fecha']) {
                    throw ValidationException::withMessages([
                        'fecha' => 'La plática debe ser anterior a la ceremonia.'
                    ]);
                }
            }

            $propuestaPendiente = DB::table('propuestas_cambio_cita')
                ->where('id_solicitud', $id)
                ->where('estado', 'pendiente')
                ->exists();

            if ($propuestaPendiente) {
                throw ValidationException::withMessages([
                    'fecha' => 'Ya existe una propuesta pendiente para esta solicitud.'
                ]);
            }

            DB::table('propuestas_cambio_cita')->insert([
                'id_solicitud' => $id,
                'id_horario_propuesto' => $horario->id_horario,
                'motivo' => $data['motivo'],
                'estado' => 'pendiente',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        });

        return redirect()
            ->route('revision.show', $id)
            ->with(
                'success',
                'Propuesta enviada. El cliente deberá aceptarla o rechazarla.'
            );
    }
}
