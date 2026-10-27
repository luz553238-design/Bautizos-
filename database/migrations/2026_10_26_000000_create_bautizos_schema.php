
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schemaPath = database_path('schema.json');

        if (!file_exists($schemaPath)) {
            throw new RuntimeException(
                'No se encontro el archivo database/schema.json.'
            );
        }

        $schema = json_decode(
            file_get_contents($schemaPath),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        foreach ($schema as $tableName => $meta) {

            // Si la tabla ya existe, no intentamos crearla otra vez.
            if (Schema::hasTable($tableName)) {
                continue;
            }

            Schema::create($tableName, function (Blueprint $table) use ($meta) {

                foreach ($meta['columns'] as $columnName => $field) {

                    if ($field['auto']) {
                        $table->increments($columnName);
                        continue;
                    }

                    $column = match ($field['type']) {
                        'varchar' => $table->string(
                            $columnName,
                            $field['length']
                        ),

                        'enum' => $table->enum(
                            $columnName,
                            $field['enum']
                        ),

                        'int' => $table->unsignedInteger($columnName),

                        'bigint' => $table->unsignedBigInteger($columnName),

                        'boolean' => $table->boolean($columnName),

                        'date' => $table->date($columnName),

                        'datetime' => $table->dateTime($columnName),

                        'time' => $table->time($columnName),

                        'timestamp' => $table->timestamp($columnName)
                            ->useCurrent(),

                        default => $table->text($columnName),
                    };

                    if (!$field['required']) {
                        $column->nullable();
                    }

                    if ($field['default'] !== null) {
                        $column->default($field['default']);
                    }

                    if ($columnName === 'fecha_actualizacion') {
                        $column->useCurrentOnUpdate();
                    }
                }

                if (count($meta['keys']) > 1) {
                    $table->primary($meta['keys']);
                }

                foreach ($meta['unique'] as $columns) {
                    $table->unique($columns);
                }

                foreach ($meta['foreign'] as $columnName => $fk) {
                    $table->foreign($columnName)
                        ->references($fk['key'])
                        ->on($fk['table'])
                        ->onUpdate('cascade')
                        ->onDelete($fk['delete']);
                }
            });
        }
    }

    public function down(): void
    {
        // No eliminamos tablas automáticamente para proteger
        // las tablas y los datos que ya existían en la base de datos.
    }
};
