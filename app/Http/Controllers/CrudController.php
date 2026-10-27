<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class CrudController extends Controller
{
    public static function schema(): array { return json_decode(file_get_contents(database_path('schema.json')), true); }
    public static function label(string $name): string { return ucfirst(str_replace('_', ' ', $name)); }
    public static function token($row, array $meta): string {
        return rtrim(strtr(base64_encode(json_encode(array_intersect_key((array) $row, array_flip($meta['keys'])))), '+/', '-_'), '=');
    }
    public static function display($row): string {
        $r = (array) $row;
        if (isset($r['apellido_paterno'])) return trim($r['nombre'].' '.$r['apellido_paterno'].' '.($r['apellido_materno'] ?? ''));
        foreach (['nombre', 'folio', 'nombre_usuario', 'folio_acta', 'numero_identificacion'] as $key) if (isset($r[$key])) return $r[$key];
        if (isset($r['id_persona'])) {
            $person = DB::table('personas')->where('id_persona', $r['id_persona'])->first();
            if ($person) return self::display($person);
        }
        if (isset($r['fecha'])) return $r['fecha'].' '.($r['hora_inicio'] ?? '');
        return implode(' · ', array_map(fn($k, $v) => self::label($k).': '.$v, array_keys(array_slice($r, 0, 2)), array_values(array_slice($r, 0, 2))));
    }
    private function context(string $module): array {
        $schema = self::schema(); abort_unless(isset($schema[$module]), 404);
        return ['module' => $module, 'meta' => $schema[$module], 'modules' => $schema];
    }
    private function query(string $module, string $token) {
        $meta = $this->context($module)['meta'];
        $keys = json_decode(base64_decode(strtr($token, '-_', '+/'), true) ?: '', true);
        abort_unless(is_array($keys) && count($keys) === count($meta['keys']) && !array_diff($meta['keys'], array_keys($keys)), 404);
        $query = DB::table($module);
        foreach ($keys as $key => $value) { abort_unless(is_scalar($value), 404); $query->where($key, $value); }
        return $query;
    }
    public function dashboard() {
        $modules = self::schema(); $counts = [];
        foreach ($modules as $name => $meta) $counts[$name] = DB::table($name)->count();
        return view('crud.dashboard', compact('modules', 'counts'));
    }
    public function index(Request $request, string $module) {
        $context = $this->context($module); $query = DB::table($module);
        $search = trim((string) $request->query('q', ''));
        if ($search !== '') $query->where(function ($q) use ($context, $search) {
            foreach ($context['meta']['columns'] as $column => $field) if (in_array($field['type'], ['varchar', 'text', 'enum']) && $column !== 'contrasena') $q->orWhere($column, 'like', '%'.$search.'%');
        });
        $rows = $query->orderBy($context['meta']['keys'][0], 'desc')->paginate(12)->withQueryString();
        return view('crud.index', $context + compact('rows', 'search'));
    }
    public function create(string $module) { return $this->form($module); }
    public function edit(string $module, string $record) { return $this->form($module, $record); }
    private function form(string $module, ?string $record = null) {
        $context = $this->context($module); $row = $record ? $this->query($module, $record)->first() : null;
        if ($record) abort_unless($row, 404);
        $options = [];
        foreach ($context['meta']['foreign'] as $field => $fk) {
            $options[$field] = DB::table($fk['table'])->get()->mapWithKeys(fn($r) => [$r->{$fk['key']} => self::display($r).' (#'.$r->{$fk['key']}.')'])->all();
        }
        return view('crud.form', $context + compact('row', 'record', 'options'));
    }
    public function show(string $module, string $record) {
        $context = $this->context($module); $row = $this->query($module, $record)->first(); abort_unless($row, 404);
        return view('crud.show', $context + compact('row', 'record'));
    }
    private function data(Request $request, string $module, ?object $row): array {
        $meta = $this->context($module)['meta']; $rules = [];
        foreach ($meta['columns'] as $name => $field) {
            if ($field['auto'] || $field['generated']) continue;
            $rules[$name] = [$field['required'] && !($row && $name === 'contrasena') ? 'required' : 'nullable'];
            $rules[$name][] = match($field['type']) { 'int', 'bigint' => 'integer', 'boolean' => 'boolean', 'date', 'datetime' => 'date', 'time' => 'date_format:H:i', default => 'string' };
            if ($field['length']) $rules[$name][] = 'max:'.$field['length'];
            if ($field['enum']) $rules[$name][] = Rule::in($field['enum']);
            if (isset($meta['foreign'][$name])) $rules[$name][] = Rule::exists($meta['foreign'][$name]['table'], $meta['foreign'][$name]['key']);
            if ($name === 'correo') $rules[$name][] = 'email';
            if ($name === 'curp') $rules[$name][] = 'size:18';
            if ($name === 'contrasena') $rules[$name][] = 'min:8';
            if (in_array($field['type'], ['int','bigint'])) $rules[$name][] = 'min:0';
        }
        if ($module === 'horarios_bautizo') { $rules['capacidad'][] = 'min:1'; $rules['hora_fin'][] = 'after:hora_inicio'; }
        $data = $request->validate($rules, [], array_combine(array_keys($rules), array_map([self::class, 'label'], array_keys($rules))));
        $groups = $meta['unique']; if (count($meta['keys']) > 1) $groups[] = $meta['keys'];
        foreach ($groups as $columns) {
            $q = DB::table($module); $skip = false;
            foreach ($columns as $col) { if (($data[$col] ?? null) === null) { $skip = true; break; } $q->where($col, $data[$col]); }
            if ($skip) continue;
            if ($row) $q->where(function($q) use ($meta, $row) { foreach ($meta['keys'] as $key) $q->orWhere($key, '!=', $row->$key); });
            if ($q->exists()) throw ValidationException::withMessages([$columns[0] => 'Ya existe un registro con esta combinación de datos.']);
        }
        if (!empty($data['contrasena'])) $data['contrasena'] = Hash::make($data['contrasena']); else unset($data['contrasena']);
        if ($row && isset($meta['columns']['fecha_actualizacion'])) $data['fecha_actualizacion'] = now();
        return $data;
    }
    public function store(Request $request, string $module) {
        $data = $this->data($request, $module, null);
        try { DB::table($module)->insert($data); } catch (QueryException $e) { return $this->failure($e); }
        return redirect()->route('crud.index', $module)->with('success', 'Registro creado correctamente.');
    }
    public function update(Request $request, string $module, string $record) {
        $row = $this->query($module, $record)->first(); abort_unless($row, 404); $data = $this->data($request, $module, $row);
        try { $this->query($module, $record)->update($data); } catch (QueryException $e) { return $this->failure($e); }
        return redirect()->route('crud.index', $module)->with('success', 'Registro actualizado correctamente.');
    }
    public function destroy(string $module, string $record) {
        abort_unless($this->query($module, $record)->exists(), 404);
        try { $this->query($module, $record)->delete(); } catch (QueryException $e) { return $this->failure($e); }
        return redirect()->route('crud.index', $module)->with('success', 'Registro eliminado correctamente.');
    }
    private function failure(QueryException $e) {
        if (!str_starts_with((string)$e->getCode(), '23')) throw $e;
        return back()->withInput()->withErrors(['registro' => 'No se pudo guardar o eliminar: hay datos duplicados o registros relacionados. Revisa sus relaciones antes de continuar.']);
    }
}
