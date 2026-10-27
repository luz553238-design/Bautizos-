<?php
namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;
    private function account(): Usuario {
        $id=DB::table('personas')->insertGetId(['nombre'=>'Ana','apellido_paterno'=>'Pérez','fecha_nacimiento'=>'2000-01-01','sexo'=>'femenino']);
        return Usuario::create(['id_persona'=>$id,'nombre_usuario'=>'ana','contrasena'=>Hash::make('ClaveSegura123'),'activo'=>1]);
    }
    public function test_guest_cannot_access_any_crud_or_menu(): void {
        $this->get('/menu')->assertRedirect(route('login'));
        foreach (\App\Http\Controllers\CrudController::schema() as $module=>$meta) {
            $this->get('/gestion/'.$module)->assertRedirect(route('login'));
            $this->post('/gestion/'.$module,[])->assertRedirect(route('login'));
            $this->put('/gestion/'.$module.'/abc',[])->assertRedirect(route('login'));
            $this->delete('/gestion/'.$module.'/abc')->assertRedirect(route('login'));
        }
    }
    public function test_login_menu_and_logout(): void {
        $user=$this->account();
        $this->get('/login')->assertOk()->assertSee('Iniciar sesión');
        $this->post('/login',['nombre_usuario'=>'ana','password'=>'incorrecta'])->assertSessionHasErrors('nombre_usuario');
        $this->assertGuest();
        $this->post('/login',['nombre_usuario'=>'ana','password'=>'ClaveSegura123'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->ultimo_acceso);
        $this->get('/menu')->assertOk()->assertSee('Menú principal')->assertSee('Cerrar sesión');
        $this->post('/logout')->assertRedirect(route('login')); $this->assertGuest();
        $this->get('/menu')->assertRedirect(route('login'));
    }
    public function test_inactive_user_is_denied(): void {
        $user=$this->account(); $user->update(['activo'=>false]);
        $this->post('/login',['nombre_usuario'=>'ana','password'=>'ClaveSegura123'])->assertSessionHasErrors('nombre_usuario');
        $this->assertGuest();
        $this->actingAs($user)->get('/menu')->assertRedirect(route('login'));
    }
    public function test_first_account_can_only_be_created_once(): void {
        $data=['nombre'=>'Ana','apellido_paterno'=>'Pérez','fecha_nacimiento'=>'2000-01-01','sexo'=>'femenino','nombre_usuario'=>'ana','password'=>'ClaveSegura123','password_confirmation'=>'ClaveSegura123'];
        $this->get('/login')->assertSee('Iniciar sesión')->assertSee('Crear cuenta');
        $this->get('/crear-cuenta')->assertOk()->assertSee('Crear cuenta');
        $this->post('/primer-usuario',$data)->assertSessionHasNoErrors()->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertTrue(Hash::check($data['password'],Usuario::first()->contrasena));
        $this->post('/logout');
        $this->post('/primer-usuario',$data)->assertForbidden();
    }
    public function test_login_attempts_are_limited(): void {
        $this->account();
        for($i=0;$i<6;$i++) $response=$this->post('/login',['nombre_usuario'=>'ana','password'=>'mala']);
        $response->assertSessionHasErrors('nombre_usuario');
        $this->assertStringContainsString('Demasiados intentos',session('errors')->first('nombre_usuario'));
    }

    public function test_additional_registration_requires_activation(): void {
        $this->account();
        $this->get('/crear-cuenta')->assertOk()->assertSee('activar tu cuenta');
        $this->post('/crear-cuenta',['nombre'=>'Eva','apellido_paterno'=>'López','fecha_nacimiento'=>'2000-01-01','sexo'=>'femenino','nombre_usuario'=>'eva','password'=>'ClaveSegura123','password_confirmation'=>'ClaveSegura123'])->assertSessionHasNoErrors()->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertDatabaseHas('usuarios',['nombre_usuario'=>'eva','activo'=>0]);
        $this->post('/login',['nombre_usuario'=>'eva','password'=>'ClaveSegura123'])->assertSessionHasErrors('nombre_usuario');
    }
}
