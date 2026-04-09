<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ConfigCatalogoCuentas;
use Illuminate\Support\Str;

class ConfigCatalogoCuentasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cuenta raíz: Activo
        $activo = ConfigCatalogoCuentas::firstOrCreate(
            ['codigo' => '1'],
            [
                'uuid' => (string) Str::uuid(),
                'nombre' => 'Activo',
                'tipo' => 'activo',
                'nivel' => 1,
                'padre_id' => null,
                'es_grupo' => true,
                'permite_movimiento' => false,
                'naturaleza' => 'deudora',
                'descripcion' => 'Cuentas de activo',
                'estado' => 'activo',
                'moneda_usd' => false,
                'is_synced' => true,
                'version' => 1
            ]
        );

        // Subgrupo: Activo Corriente
        $activoCorriente = ConfigCatalogoCuentas::firstOrCreate(
            ['codigo' => '1.1'],
            [
                'uuid' => (string) Str::uuid(),
                'nombre' => 'Activo Corriente',
                'tipo' => 'activo',
                'nivel' => 2,
                'padre_id' => $activo->id,
                'es_grupo' => true,
                'permite_movimiento' => false,
                'naturaleza' => 'deudora',
                'descripcion' => 'Cuentas de activo corriente',
                'estado' => 'activo',
                'moneda_usd' => false,
                'is_synced' => true,
                'version' => 1
            ]
        );

        // Cuenta de movimiento: Caja General
        ConfigCatalogoCuentas::firstOrCreate(
            ['codigo' => '1.1.01'],
            [
                'uuid' => (string) Str::uuid(),
                'nombre' => 'Caja General',
                'tipo' => 'activo',
                'nivel' => 3,
                'padre_id' => $activoCorriente->id,
                'es_grupo' => false,
                'permite_movimiento' => true,
                'naturaleza' => 'deudora',
                'descripcion' => 'Cuenta para manejo de efectivo en caja',
                'estado' => 'activo',
                'moneda_usd' => false,
                'is_synced' => true,
                'version' => 1
            ]
        );

        // Cuenta de movimiento: Bancos
        ConfigCatalogoCuentas::firstOrCreate(
            ['codigo' => '1.1.02'],
            [
                'uuid' => (string) Str::uuid(),
                'nombre' => 'Bancos',
                'tipo' => 'activo',
                'nivel' => 3,
                'padre_id' => $activoCorriente->id,
                'es_grupo' => false,
                'permite_movimiento' => true,
                'naturaleza' => 'deudora',
                'descripcion' => 'Cuentas bancarias',
                'estado' => 'activo',
                'moneda_usd' => false,
                'is_synced' => true,
                'version' => 1
            ]
        );
    }
}