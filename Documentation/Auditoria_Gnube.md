# 📘 Auditoría de Cambios en Laravel

Este documento describe cómo implementar un sistema de **auditoría completa** en Laravel para registrar los cambios realizados sobre los modelos del sistema.

---

## 🧩 1. Estructura de la tabla `audits`

Crea la migración con los siguientes campos:

```bash
php artisan make:migration create_audits_table
```

Ejemplo de migración:

```php
Schema::create('audits', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('user_id')->nullable();
    $table->string('model_type');
    $table->unsignedBigInteger('model_id');
    $table->string('event'); // created, updated, deleted
    $table->json('old_values')->nullable();
    $table->json('new_values')->nullable();
    $table->string('ip')->nullable();
    $table->string('user_agent')->nullable();
    $table->timestamps();
});
```

---

## 🧠 2. Modelo `Audit`

Crea el modelo correspondiente:

```bash
php artisan make:model Audit
```

Código sugerido:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    protected $fillable = [
        'user_id', 'model_type', 'model_id', 'event',
        'old_values', 'new_values', 'ip', 'user_agent'
    ];
}
```

---

## 🧩 3. Trait `Auditable`

Este trait permite registrar automáticamente los cambios (creación, actualización, eliminación).

Guarda este archivo en `app/Traits/Auditable.php`:

```php
namespace App\Traits;

use App\Models\Audit;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    public static function bootAuditable()
    {
        static::updated(function ($model) {
            $user = Auth::user();
            $changes = $model->getDirty();
            $old = [];
            $new = [];

            foreach ($changes as $field => $value) {
                $old[$field] = $model->getOriginal($field);
                $new[$field] = $value;
            }

            Audit::create([
                'user_id' => $user?->id,
                'model_type' => get_class($model),
                'model_id' => $model->id,
                'event' => 'updated',
                'old_values' => json_encode($old),
                'new_values' => json_encode($new),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        static::created(function ($model) {
            $user = Auth::user();
            Audit::create([
                'user_id' => $user?->id,
                'model_type' => get_class($model),
                'model_id' => $model->id,
                'event' => 'created',
                'new_values' => json_encode($model->getAttributes()),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        static::deleted(function ($model) {
            $user = Auth::user();
            Audit::create([
                'user_id' => $user?->id,
                'model_type' => get_class($model),
                'model_id' => $model->id,
                'event' => 'deleted',
                'old_values' => json_encode($model->getOriginal()),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }
}
```

---

## ⚙️ 4. Integrar en los modelos

En cualquier modelo donde quieras auditar cambios (por ejemplo, `Arancel`), simplemente agrega el `trait`:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Arancel extends Model
{
    use Auditable;
}
```

---

## 🧾 5. Ejemplo de registro generado

| user_id | model_type | model_id | event | old_values | new_values |
|----------|-------------|-----------|--------|--------------|-------------|
| 5 | App\Models\Arancel | 1 | updated | {"monto":300} | {"monto":400} |

Solo se guardan los campos realmente modificados.

---

## 💡 6. Extensión recomendada (opcional)

Si deseas guardar **un registro por cada campo modificado**, agrega a la tabla:

```php
$table->string('table_name')->nullable();
$table->string('column_name')->nullable();
```

Y en el `trait`, crea un `Audit::create()` dentro del `foreach($changes)` para registrar campo por campo.

Esto permite reportes más detallados como:

> “El campo `monto` del Arancel #12 fue cambiado de `300` a `400` por el usuario Admin a las 10:45am.”

---

📅 Documento generado el 17/10/2025 para **Gnube**  
👨‍💻 Autor: Gersan Alduvin
