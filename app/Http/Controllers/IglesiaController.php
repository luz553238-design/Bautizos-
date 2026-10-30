<?php

namespace App\Http\Controllers;

use App\Models\Iglesia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class IglesiaController extends Controller
{
    private function autorizarAdministracion(): void
    {
        abort_unless(Auth::check(), 401);

        $roles = DB::table('usuario_rol as ur')
            ->join('roles as r', 'r.id_rol', '=', 'ur.id_rol')
            ->where(
                'ur.id_usuario',
                Auth::user()->getAuthIdentifier()
            )
            ->pluck('r.nombre')
            ->all();

        abort_unless(
            count(array_intersect($roles, [
                'superadministrador',
                'administrador'
            ])) > 0,
            403,
            'No tienes permiso para administrar iglesias.'
        );
    }

    public function index(Request $request)
    {
        $this->autorizarAdministracion();

        $buscar = trim((string) $request->query('buscar', ''));

        $iglesias = Iglesia::with('direccion')
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('nombre', 'like', "%{$buscar}%")
                        ->orWhere('tipo', 'like', "%{$buscar}%")
                        ->orWhereHas('direccion', function ($direccion) use ($buscar) {
                            $direccion
                                ->where('municipio', 'like', "%{$buscar}%")
                                ->orWhere('colonia', 'like', "%{$buscar}%")
                                ->orWhere('estado', 'like', "%{$buscar}%");
                        });
                });
            })
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return view('iglesias.index', compact(
            'iglesias',
            'buscar'
        ));
    }

    public function create()
    {
        $this->autorizarAdministracion();

        return view('iglesias.form', [
            'iglesia' => new Iglesia(),
            'esEdicion' => false,
        ]);
    }

    public function store(Request $request)
    {
        $this->autorizarAdministracion();

        $datos = $this->validar($request);

        DB::transaction(function () use ($datos) {
            $iglesia = Iglesia::create([
                'nombre' => $datos['nombre'],
                'tipo' => $datos['tipo'],
                'telefono' => $datos['telefono'] ?? null,
                'correo' => $datos['correo'] ?? null,
                'activo' => $datos['activo'],
            ]);

            $iglesia->direccion()->create(
                $this->datosDireccion($datos)
            );
        });

        return redirect()
            ->route('iglesias.index')
            ->with(
                'success',
                'Iglesia y dirección registradas correctamente.'
            );
    }

    public function edit(Iglesia $iglesia)
    {
        $this->autorizarAdministracion();

        $iglesia->load('direccion');

        return view('iglesias.form', [
            'iglesia' => $iglesia,
            'esEdicion' => true,
        ]);
    }

    public function update(Request $request, Iglesia $iglesia)
    {
        $this->autorizarAdministracion();

        $datos = $this->validar($request);

        DB::transaction(function () use ($iglesia, $datos) {
            $iglesia->update([
                'nombre' => $datos['nombre'],
                'tipo' => $datos['tipo'],
                'telefono' => $datos['telefono'] ?? null,
                'correo' => $datos['correo'] ?? null,
                'activo' => $datos['activo'],
            ]);

            $iglesia->direccion()->updateOrCreate(
                ['id_iglesia' => $iglesia->id_iglesia],
                $this->datosDireccion($datos)
            );
        });

        return redirect()
            ->route('iglesias.index')
            ->with(
                'success',
                'Datos de la iglesia actualizados correctamente.'
            );
    }

    public function destroy(Iglesia $iglesia)
    {
        $this->autorizarAdministracion();

        $iglesia->delete();

        return redirect()
            ->route('iglesias.index')
            ->with(
                'success',
                'Iglesia eliminada correctamente.'
            );
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:180'],
            'tipo' => [
                'required',
                Rule::in([
                    'Parroquia',
                    'Capilla',
                    'Santuario',
                    'Basílica',
                    'Catedral',
                    'Otra',
                ])
            ],
            'telefono' => ['nullable', 'string', 'max:20'],
            'correo' => ['nullable', 'email', 'max:150'],
            'activo' => ['required', 'boolean'],

            'calle' => ['required', 'string', 'max:180'],
            'numero_exterior' => ['required', 'string', 'max:20'],
            'numero_interior' => ['nullable', 'string', 'max:20'],
            'colonia' => ['required', 'string', 'max:150'],
            'codigo_postal' => [
                'required',
                'digits:5',
            ],
            'municipio' => ['required', 'string', 'max:120'],
            'estado' => ['required', 'string', 'max:120'],
            'referencias' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function datosDireccion(array $datos): array
    {
        return [
            'calle' => $datos['calle'],
            'numero_exterior' => $datos['numero_exterior'],
            'numero_interior' => $datos['numero_interior'] ?? null,
            'colonia' => $datos['colonia'],
            'codigo_postal' => $datos['codigo_postal'],
            'municipio' => $datos['municipio'],
            'estado' => $datos['estado'],
            'referencias' => $datos['referencias'] ?? null,
        ];
    }
}
