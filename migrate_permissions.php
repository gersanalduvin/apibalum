use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

$perm = Permission::firstOrCreate([
'name' => 'generar.consolidado_notas',
'guard_name' => 'api',
'category' => 'reportes',
'module' => 'consolidado_notas',
'action' => 'generar',
'display_name' => 'Reportes - Consolidado de notas - Generar'
]);

$roles = Role::whereHas('permissions', function($q) {
$q->where('name', 'generar.boletin');
})->get();

foreach($roles as $role) {
if (!$role->hasPermissionTo('generar.consolidado_notas')) {
$role->givePermissionTo('generar.consolidado_notas');
echo "Granted to " . $role->name . "\n";
}
}
echo "Migration completed\n";