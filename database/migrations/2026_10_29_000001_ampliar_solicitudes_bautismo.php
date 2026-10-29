
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1. PLÁTICAS PREBAUTISMALES
        |--------------------------------------------------------------------------
        |
        | La tabla ya fue creada manualmente.
        | Se conserva su información y no se establece capacidad.
        |
        */

        if (!Schema::hasTable('platicas_prebautismales')) {
            Schema::create('platicas_prebautismales', function (Blueprint $table) {
                $table->bigIncrements('id_platica');
                $table->date('fecha');
                $table->time('hora_inicio');
                $table->time('hora_fin');
                $table->string('lugar', 150)->nullable();
                $table->boolean('disponible')->default(true);
                $table->timestamps();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | 2. INSCRIPCIONES A PLÁTICAS
        |--------------------------------------------------------------------------
        |
        | Una solicitud selecciona una plática.
        | No existe límite de participantes.
        |
        */

        if (!Schema::hasTable('inscripciones_platicas')) {
            Schema::create('inscripciones_platicas', function (Blueprint $table) {
                $table->bigIncrements('id_inscripcion');

                $table->unsignedInteger('id_solicitud');
                $table->unsignedBigInteger('id_platica');

                $table->enum('estado', [
                    'inscrita',
                    'asistio',
                    'no_asistio',
                    'cancelada',
                ])->default('inscrita');

                $table->timestamps();

                $table->unique(
                    'id_solicitud',
                    'uq_inscripcion_solicitud'
                );

                $table->index(
                    'id_platica',
                    'idx_inscripcion_platica'
                );

                $table->foreign(
                    'id_solicitud',
                    'fk_inscripcion_solicitud'
                )
                    ->references('id_solicitud')
                    ->on('solicitudes_bautismo')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $table->foreign(
                    'id_platica',
                    'fk_inscripcion_platica'
                )
                    ->references('id_platica')
                    ->on('platicas_prebautismales')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | 3. PROPUESTAS DE CAMBIO DE CITA
        |--------------------------------------------------------------------------
        |
        | El sacerdote propone una fecha y horario.
        | El cliente acepta o rechaza la propuesta.
        |
        */

        if (!Schema::hasTable('propuestas_cambio_cita')) {
            Schema::create('propuestas_cambio_cita', function (Blueprint $table) {
                $table->bigIncrements('id_propuesta');

                $table->unsignedInteger('id_solicitud');
                $table->unsignedInteger('id_horario_propuesto');
                $table->unsignedInteger('propuesto_por');

                $table->enum('estado', [
                    'pendiente',
                    'aceptada',
                    'rechazada',
                ])->default('pendiente');

                $table->text('motivo')->nullable();
                $table->timestamp('respondida_en')->nullable();

                $table->timestamps();

                $table->index(
                    'id_solicitud',
                    'idx_propuesta_solicitud'
                );

                $table->index(
                    'id_horario_propuesto',
                    'idx_propuesta_horario'
                );

                $table->index(
                    'propuesto_por',
                    'idx_propuesta_usuario'
                );

                $table->foreign(
                    'id_solicitud',
                    'fk_propuesta_solicitud'
                )
                    ->references('id_solicitud')
                    ->on('solicitudes_bautismo')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $table->foreign(
                    'id_horario_propuesto',
                    'fk_propuesta_horario'
                )
                    ->references('id_horario')
                    ->on('horarios_bautizo')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();

                $table->foreign(
                    'propuesto_por',
                    'fk_propuesta_usuario'
                )
                    ->references('id_usuario')
                    ->on('usuarios')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | 4. AUTORIZACIÓN DE SOLICITUDES
        |--------------------------------------------------------------------------
        */

        Schema::table('solicitudes_bautismo', function (Blueprint $table) {
            if (!Schema::hasColumn('solicitudes_bautismo', 'autorizado_por')) {
                $table->unsignedInteger('autorizado_por')->nullable();

                $table->foreign(
                    'autorizado_por',
                    'fk_solicitud_autorizado_por'
                )
                    ->references('id_usuario')
                    ->on('usuarios')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('solicitudes_bautismo', 'fecha_autorizacion')) {
                $table->timestamp('fecha_autorizacion')->nullable();
            }

            if (!Schema::hasColumn('solicitudes_bautismo', 'observaciones_autorizacion')) {
                $table->text('observaciones_autorizacion')->nullable();
            }
        });

        /*
        |--------------------------------------------------------------------------
        | 5. SACERDOTE OPCIONAL EN CITAS
        |--------------------------------------------------------------------------
        |
        | La cita se registra inicialmente sin sacerdote.
        | Conservamos el tipo y signo originales de la columna.
        |
        */

        $columna = DB::selectOne(
            "SELECT COLUMN_TYPE, IS_NULLABLE
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'citas'
               AND COLUMN_NAME = 'id_sacerdote'"
        );

        if ($columna && $columna->IS_NULLABLE === 'NO') {
            $tipo = strtolower($columna->COLUMN_TYPE);

            // Solo se admiten tipos enteros válidos obtenidos
            // directamente de la definición de la base de datos.
            if (!preg_match(
                '/^(tinyint|smallint|mediumint|int|integer|bigint)(\(\d+\))?( unsigned)?$/',
                $tipo
            )) {
                throw new \RuntimeException(
                    'Tipo inesperado en citas.id_sacerdote: ' . $tipo
                );
            }

            DB::statement(
                "ALTER TABLE citas MODIFY COLUMN id_sacerdote {$tipo} NULL"
            );
        }
    }

    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | REVERSIÓN
        |--------------------------------------------------------------------------
        |
        | No eliminamos platicas_prebautismales porque
        | contiene registros creados antes de esta migración.
        |
        | Tampoco obligamos a id_sacerdote a ser NOT NULL,
        | porque podrían existir citas sin sacerdote asignado.
        |
        */

        if (Schema::hasTable('solicitudes_bautismo')) {
            if (Schema::hasColumn('solicitudes_bautismo', 'autorizado_por')) {
                Schema::table('solicitudes_bautismo', function (Blueprint $table) {
                    $table->dropForeign('fk_solicitud_autorizado_por');
                });
            }

            $columnas = [];

            foreach ([
                'autorizado_por',
                'fecha_autorizacion',
                'observaciones_autorizacion',
            ] as $columna) {
                if (Schema::hasColumn('solicitudes_bautismo', $columna)) {
                    $columnas[] = $columna;
                }
            }

            if (!empty($columnas)) {
                Schema::table('solicitudes_bautismo', function (Blueprint $table) use ($columnas) {
                    $table->dropColumn($columnas);
                });
            }
        }

        Schema::dropIfExists('propuestas_cambio_cita');
        Schema::dropIfExists('inscripciones_platicas');
    }
};
