<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        foreach (json_decode(file_get_contents(database_path('schema.json')), true) as $name => $meta) {
            Schema::create($name, function (Blueprint $table) use ($meta) {
                foreach ($meta['columns'] as $name => $field) {
                    if ($field['auto']) { $table->increments($name); continue; }
                    $column = match ($field['type']) {
                        'varchar' => $table->string($name, $field['length']),
                        'enum' => $table->enum($name, $field['enum']),
                        'int' => $table->unsignedInteger($name),
                        'bigint' => $table->unsignedBigInteger($name),
                        'boolean' => $table->boolean($name),
                        'date' => $table->date($name),
                        'datetime' => $table->dateTime($name),
                        'time' => $table->time($name),
                        'timestamp' => $table->timestamp($name)->useCurrent(),
                        default => $table->text($name),
                    };
                    if (!$field['required']) $column->nullable();
                    if ($field['default'] !== null) $column->default($field['default']);
                    if ($name === 'fecha_actualizacion') $column->useCurrentOnUpdate();
                }
                if (count($meta['keys']) > 1) $table->primary($meta['keys']);
                foreach ($meta['unique'] as $columns) $table->unique($columns);
                foreach ($meta['foreign'] as $column => $fk) {
                    $table->foreign($column)->references($fk['key'])->on($fk['table'])->onUpdate('cascade')->onDelete($fk['delete']);
                }
            });
        }
    }
    public function down(): void {
        foreach (array_reverse(array_keys(json_decode(file_get_contents(database_path('schema.json')), true))) as $name) Schema::dropIfExists($name);
    }
};
