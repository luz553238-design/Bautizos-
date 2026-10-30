<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CertificadoBautismoController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | VERIFICAR PERMISOS
    |--------------------------------------------------------------------------
    | Superadministrador: puede descargar cualquier certificado.
    | Administrador: puede descargar cualquier certificado.
    | Cliente: únicamente certificados de sus propias solicitudes.
    */

    private function autorizarAcceso(object $solicitud): void
    {
        $usuario = Auth::user();

        abort_unless(
            $usuario !== null,
            401,
            'Debes iniciar sesión.'
        );

        $idUsuario = (int) $usuario->getAuthIdentifier();

        $esAdministrativo = DB::table('usuario_rol as ur')
            ->join(
                'roles as r',
                'r.id_rol',
                '=',
                'ur.id_rol'
            )
            ->where('ur.id_usuario', $idUsuario)
            ->whereIn(
                DB::raw('LOWER(TRIM(r.nombre))'),
                [
                    'superadministrador',
                    'administrador'
                ]
            )
            ->exists();

        $esPropietario = (
            (int) $solicitud->creado_por === $idUsuario
        );

        abort_unless(
            $esAdministrativo || $esPropietario,
            403,
            'No tienes permiso para descargar este certificado.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CONSTRUIR NOMBRE COMPLETO
    |--------------------------------------------------------------------------
    */

    private function nombreCompleto(?object $persona): string
    {
        if ($persona === null) {
            return 'No registrado';
        }

        $partes = [
            $persona->nombre ?? null,
            $persona->apellido_paterno ?? null,
            $persona->apellido_materno ?? null,
        ];

        $partes = array_filter(
            $partes,
            fn ($valor) => $valor !== null
                && trim((string) $valor) !== ''
        );

        $nombre = trim(implode(' ', $partes));

        return $nombre !== ''
            ? $nombre
            : 'No registrado';
    }

    /*
    |--------------------------------------------------------------------------
    | CONSULTAR DATOS DEL BAUTISMO
    |--------------------------------------------------------------------------
    */

    private function obtenerBautismo(int $idBautismo): ?object
    {
        return DB::table('bautismos as b')
            ->join(
                'citas as c',
                'c.id_cita',
                '=',
                'b.id_cita'
            )
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
            ->where(
                'b.id_bautismo',
                $idBautismo
            )
            ->select(
                'b.*',
                's.id_solicitud',
                's.folio as folio_solicitud',
                's.creado_por',
                'c.estado as estado_cita',
                'bz.lugar_nacimiento',
                'p.nombre',
                'p.apellido_paterno',
                'p.apellido_materno',
                'p.fecha_nacimiento',
                'p.sexo'
            )
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | CONSULTAR PADRES Y TUTORES
    |--------------------------------------------------------------------------
    */

    private function obtenerTutores(int $idSolicitud)
    {
        return DB::table('solicitud_tutores as st')
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
            ->where(
                'st.id_solicitud',
                $idSolicitud
            )
            ->select(
                'st.parentesco',
                'st.tutor_principal',
                'p.nombre',
                'p.apellido_paterno',
                'p.apellido_materno'
            )
            ->orderByDesc('st.tutor_principal')
            ->orderBy('st.parentesco')
            ->get()
            ->map(function ($tutor) {
                return [
                    'nombre' => $this->nombreCompleto($tutor),
                    'parentesco' => $tutor->parentesco,
                    'principal' => (bool) $tutor->tutor_principal,
                ];
            });
    }

    /*
    |--------------------------------------------------------------------------
    | CONSULTAR PADRINOS
    |--------------------------------------------------------------------------
    */

    private function obtenerPadrinos(int $idSolicitud)
    {
        return DB::table('solicitud_padrinos as sp')
            ->join(
                'padrinos as pd',
                'pd.id_padrino',
                '=',
                'sp.id_padrino'
            )
            ->join(
                'personas as p',
                'p.id_persona',
                '=',
                'pd.id_persona'
            )
            ->where(
                'sp.id_solicitud',
                $idSolicitud
            )
            ->select(
                'sp.tipo_participacion',
                'p.nombre',
                'p.apellido_paterno',
                'p.apellido_materno'
            )
            ->orderByRaw(
                "CASE
                    WHEN sp.tipo_participacion = 'padrino' THEN 1
                    WHEN sp.tipo_participacion = 'madrina' THEN 2
                    ELSE 3
                END"
            )
            ->get()
            ->map(function ($padrino) {
                return [
                    'nombre' => $this->nombreCompleto($padrino),
                    'participacion' => $padrino->tipo_participacion,
                ];
            });
    }

    /*
    |--------------------------------------------------------------------------
    | IDENTIFICAR CERTIFICADOS DE PRUEBA
    |--------------------------------------------------------------------------
    */

    private function esCertificadoPrueba(object $bautismo): bool
    {
        $folio = Str::upper(
            trim((string) $bautismo->folio_acta)
        );

        return Str::startsWith(
            $folio,
            'PRUEBA-'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DESCARGAR CERTIFICADO DE BAUTISMO
    |--------------------------------------------------------------------------
    */

    public function descargar(int $idBautismo)
    {
        // 1. Verificar autenticación.

        abort_unless(
            Auth::check(),
            401,
            'Debes iniciar sesión para descargar el certificado.'
        );

        // 2. Consultar el bautismo.

        $bautismo = $this->obtenerBautismo(
            $idBautismo
        );

        abort_unless(
            $bautismo !== null,
            404,
            'El bautismo solicitado no existe.'
        );

        // 3. Comprobar los permisos del usuario.

        $this->autorizarAcceso($bautismo);

        // 4. Verificar que la ceremonia esté realizada.

        abort_unless(
            Str::lower(
                trim((string) $bautismo->estado_cita)
            ) === 'realizada',
            422,
            'No se puede generar el certificado porque la ceremonia todavía no está registrada como realizada.'
        );

        // 5. Obtener los padres y tutores.

        $tutores = $this->obtenerTutores(
            (int) $bautismo->id_solicitud
        );

        // 6. Obtener los padrinos.

        $padrinos = $this->obtenerPadrinos(
            (int) $bautismo->id_solicitud
        );

        // 7. Construir el nombre del bautizado.

        $nombreBautizado = $this->nombreCompleto(
            $bautismo
        );

        // 8. Verificar si es un certificado de prueba.

        $esPrueba = $this->esCertificadoPrueba(
            $bautismo
        );

        // 9. Preparar los datos para la vista Blade.

        $datos = [
            'bautismo' => $bautismo,
            'nombreBautizado' => $nombreBautizado,
            'tutores' => $tutores,
            'padrinos' => $padrinos,
            'esPrueba' => $esPrueba,
            'diocesis' => 'Diócesis de Toluca, A.R.',
            'parroquia' => 'La Asunción de María, Pipioltepec',
        ];

        // 10. Generar el PDF.
        //
        // Carta vertical: 215.9 mm x 279.4 mm.
        // El certificado ocupará la mitad superior,
        // según el diseño de certificados.bautismo.

        $pdf = Pdf::loadView(
            'certificados.bautismo',
            $datos
        );

        $pdf->setPaper(
            'letter',
            'portrait'
        );

        // 11. Configurar DomPDF.

        $pdf->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => false,
            'isPhpEnabled' => false,
        ]);

        // 12. Preparar el nombre del archivo.

        $nombreArchivo = 'certificado-bautismo-'
            . $bautismo->id_bautismo
            . '.pdf';

        // 13. Descargar el certificado.

        return $pdf->download(
            $nombreArchivo
        );
    }
}
