<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ConfigGrupo;
use App\Models\User;
use App\Models\UsersFamilia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Barryvdh\Snappy\Facades\SnappyPdf;

class ReporteCredencialesController extends Controller
{
    /**
     * Generar PDF de credenciales de familia por grupo
     */
    public function generarPorGrupo(int $grupoId)
    {
        try {
            $grupo = ConfigGrupo::with(['grado', 'seccion'])->findOrFail($grupoId);
            
            // Obtener todos los alumnos del grupo
            $alumnosIds = $grupo->usersGrupos()->pluck('user_id');
            
            // Obtener las familias vinculadas a estos alumnos
            $familiasIds = UsersFamilia::whereIn('estudiante_id', $alumnosIds)->pluck('familia_id')->unique();
            
            $familias = User::whereIn('id', $familiasIds)->get();
            
            // Por cada familia, obtener sus alumnos en este grupo para mostrarlos si es necesario
            foreach ($familias as $familia) {
                $familia->alumnos_nombres = UsersFamilia::where('familia_id', $familia->id)
                    ->whereIn('estudiante_id', $alumnosIds)
                    ->with('estudiante')
                    ->get()
                    ->map(function($vf) {
                        return $vf->estudiante->primer_nombre . ' ' . $vf->estudiante->primer_apellido;
                    })->implode(', ');
            }

            $pdf = SnappyPdf::loadView('pdf.credenciales-familia', [
                'familias' => $familias,
                'grupo' => $grupo,
                'password_default' => '36251469',
                'url_plataforma' => 'balumbotan.gnube.app'
            ])
            ->setPaper('letter')
            ->setOrientation('portrait')
            ->setOption('margin-top', 10)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)
            ->setOption('encoding', 'utf-8');

            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="credenciales_' . Str::slug($grupo->nombre) . '.pdf"',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al generar el reporte: ' . $e->getMessage()
            ], 500);
        }
    }
}
