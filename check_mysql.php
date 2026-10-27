<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db=Illuminate\Support\Facades\DB::connection();
echo 'Conexion: '.$db->getDriverName().PHP_EOL;
echo 'Base: '.$db->getDatabaseName().PHP_EOL;
foreach(App\Http\Controllers\CrudController::schema() as $table=>$meta) {
    foreach(array_keys($meta['columns']) as $column) {
        if(!Illuminate\Support\Facades\Schema::hasColumn($table,$column)) throw new RuntimeException('Falta '.$table.'.'.$column);
    }
    echo $table.': '.$db->table($table)->count().' registros'.PHP_EOL;
}
echo 'Sesiones: '.config('session.driver').PHP_EOL;
echo 'Cache: '.config('cache.default').PHP_EOL;
