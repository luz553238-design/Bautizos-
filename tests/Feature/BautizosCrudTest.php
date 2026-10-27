<?php
namespace Tests\Feature;

use App\Http\Controllers\CrudController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BautizosCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        $this->actingAs(new \App\Models\Usuario(['id_usuario'=>999,'nombre_usuario'=>'Pruebas','activo'=>true]));
    }

    public function test_every_module_supports_create_read_update_and_delete(): void
    {
        $this->get('/menu')->assertOk(); $created = [];
        foreach (CrudController::schema() as $module => $meta) {
            $this->get(route('crud.index',$module))->assertOk();
            $this->get(route('crud.create',$module))->assertOk();
            $data = [];
            foreach ($meta['columns'] as $field => $def) {
                if ($def['auto'] || $def['generated']) continue;
                if (isset($meta['foreign'][$field])) { $fk=$meta['foreign'][$field]; $data[$field]=DB::table($fk['table'])->value($fk['key']); continue; }
                $data[$field] = match ($def['type']) {
                    'boolean' => 1, 'int','bigint' => 10, 'date' => '2026-10-26',
                    'datetime' => '2026-10-26T10:00', 'time' => $field==='hora_fin' ? '11:00' : '10:00',
                    'enum' => $def['enum'][0], default => 'Prueba',
                };
                if ($field==='correo') $data[$field]='prueba@example.com';
                if ($field==='curp') $data[$field]='AAAA000101HMCBBB01';
                if ($field==='contrasena') $data[$field]='ClaveSegura123';
            }
            $this->post(route('crud.store',$module),$data)->assertSessionHasNoErrors()->assertRedirect(route('crud.index',$module));
            $row=DB::table($module)->first(); $this->assertNotNull($row);
            if ($module==='usuarios') $this->assertTrue(Hash::check('ClaveSegura123',$row->contrasena));
            $token=CrudController::token($row,$meta); $created[]=[$module,$token];
            $this->get(route('crud.index',$module))->assertOk();
            $this->get(route('crud.show',[$module,$token]))->assertOk();
            $this->get(route('crud.edit',[$module,$token]))->assertOk();
            $this->put(route('crud.update',[$module,$token]),$data)->assertSessionHasNoErrors()->assertRedirect();
        }
        foreach (array_reverse($created) as [$module,$token]) {
            $this->delete(route('crud.destroy',[$module,$token]))->assertSessionHasNoErrors()->assertRedirect();
            $this->assertSame(0,DB::table($module)->count());
        }
    }

    public function test_invalid_hours_duplicates_and_restricted_deletions_are_rejected(): void
    {
        $this->post('/gestion/horarios_bautizo',['fecha'=>'2026-10-26','hora_inicio'=>'11:00','hora_fin'=>'10:00','capacidad'=>0,'disponible'=>1])->assertSessionHasErrors(['hora_fin','capacidad']);
        $this->post('/gestion/roles',['nombre'=>'Administrador'])->assertSessionHasNoErrors();
        $this->post('/gestion/roles',['nombre'=>'Administrador'])->assertSessionHasErrors('nombre');
        $person=DB::table('personas')->insertGetId(['nombre'=>'Ana','apellido_paterno'=>'Pérez','fecha_nacimiento'=>'2000-01-01','sexo'=>'femenino']);
        DB::table('usuarios')->insert(['id_persona'=>$person,'nombre_usuario'=>'ana','contrasena'=>Hash::make('ClaveSegura123')]);
        $meta=CrudController::schema()['personas']; $row=DB::table('personas')->first();
        $this->delete(route('crud.destroy',['personas',CrudController::token($row,$meta)]))->assertSessionHasErrors('registro');
        $this->assertDatabaseHas('personas',['id_persona'=>$person]);
        $this->get('/gestion/no_existe')->assertNotFound();
        $this->get('/gestion/personas/clave_invalida')->assertNotFound();
    }
}
