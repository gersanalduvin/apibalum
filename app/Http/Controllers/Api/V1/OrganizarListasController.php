<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\OrganizarListasRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\Snappy\Facades\SnappyPdf;

class OrganizarListasController extends Controller
{
    public function catalogos(): \Illuminate\Http\JsonResponse
    {
        $periodos = DB::table('conf_periodo_lectivos')->select('id','nombre')->orderBy('nombre')->get();
        $grados = DB::table('config_grado')->select('id','nombre','orden')->orderBy('orden')->orderBy('nombre')->get();
        $turnos = DB::table('config_turnos')->select('id','nombre','orden')->orderBy('orden')->orderBy('nombre')->get();
        return $this->successResponse(['periodos_lectivos'=>$periodos,'grados'=>$grados,'turnos'=>$turnos],'Catálogos obtenidos exitosamente');
    }

    public function alumnos(OrganizarListasRequest $request): \Illuminate\Http\JsonResponse
    {
        $p = $request->input('periodo_lectivo_id');
        $g = $request->input('grado_id');
        $t = $request->input('turno_id');

        $query = DB::table('users_grupos')
            ->join('users','users_grupos.user_id','=','users.id')
            ->leftJoin('config_grupos','users_grupos.grupo_id','=','config_grupos.id')
            ->leftJoin('config_grado','config_grupos.grado_id','=','config_grado.id')
            ->leftJoin('config_seccion','config_grupos.seccion_id','=','config_seccion.id')
            ->leftJoin('config_turnos','config_grupos.turno_id','=','config_turnos.id')
            ->select(
                'users.id as user_id',
                DB::raw("CONCAT(COALESCE(users.primer_nombre,''),' ',COALESCE(users.segundo_nombre,''),' ',COALESCE(users.primer_apellido,''),' ',COALESCE(users.segundo_apellido,'')) as nombre_completo"),
                'users.sexo',
                'users_grupos.grupo_id',
                DB::raw("CONCAT(COALESCE(config_grado.nombre,''),' - ',COALESCE(config_seccion.nombre,''),' (',COALESCE(config_turnos.nombre,''),')') as grupo"),
            );

        $query->whereNull('users.deleted_at');
        $query->whereNull('users_grupos.deleted_at');
        $query->where('users_grupos.estado','activo');

        if ($p) $query->where('users_grupos.periodo_lectivo_id',$p);
        if ($g) $query->where('users_grupos.grado_id',$g);
        if ($t) $query->where('users_grupos.turno_id',$t);

        $alumnos = $query->orderBy('users.primer_apellido', 'asc')
            ->orderBy('users.segundo_apellido', 'asc')
            ->orderBy('users.primer_nombre', 'asc')
            ->orderBy('users.segundo_nombre', 'asc')
            ->get();

        return $this->successResponse($alumnos,'Alumnos obtenidos exitosamente');
    }

    public function alumnosPdf(OrganizarListasRequest $request)
    {
        $data = $this->alumnos($request)->getData(true)['data'] ?? [];
        $m = 0; $f = 0; $total = 0;
        foreach ($data as $row) {
            $sx = strtoupper(trim($row['sexo'] ?? ''));
            if ($sx === 'M') { $m++; } elseif ($sx === 'F') { $f++; }
            $total++;
        }
        $periodo = DB::table('conf_periodo_lectivos')->find($request->input('periodo_lectivo_id'));
        $grado = DB::table('config_grado')->find($request->input('grado_id'));
        $turno = DB::table('config_turnos')->find($request->input('turno_id'));

        $html = view('reportes.organizar.lista-alumnos', [
            'alumnos' => $data,
            'periodo' => $periodo,
            'grado' => $grado,
            'turno' => $turno,
            'sexo_masculino' => $m,
            'sexo_femenino' => $f,
            'total_alumnos' => $total,
        ])->render();

        $titulo = 'LISTA DE ALUMNOS';
        $subtitulo1 = 'Período: '.($periodo->nombre ?? '').' | Grado: '.($grado->nombre ?? '').' | Turno: '.($turno->nombre ?? '');
        $subtitulo2 = '';
        $nombreInstitucion = config('app.nombre_institucion', 'MINISTERIO DE EDUCACIÓN');
        $headerHtml = view()->make('pdf.header', compact('titulo','subtitulo1','subtitulo2','nombreInstitucion'))->render();

        $pdf = SnappyPdf::loadHTML($html)
            ->setPaper('letter')
            ->setOrientation('portrait')
            ->setOption('margin-top', 35)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 20)
            ->setOption('margin-left', 10)
            ->setOption('encoding', 'utf-8')
            ->setOption('enable-local-file-access', true)
            ->setOption('header-html', $headerHtml)
            ->setOption('header-spacing', 5)
            ->setOption('footer-left', 'Fecha y hora: [date] [time]')
            ->setOption('footer-right', 'Página [page] de [toPage]')
            ->setOption('footer-spacing', 5)
            ->setOption('load-error-handling', 'ignore');

        $nombreArchivo = 'lista_alumnos_'.now()->format('Y-m-d_H-i-s').'.pdf';
        return $pdf->download($nombreArchivo);
    }

    public function alumnosExcel(OrganizarListasRequest $request)
    {
        $data = $this->alumnos($request)->getData(true)['data'] ?? [];
        $headings = ['#','nombre_completo','sexo','grupo'];
        $rows = [];
        $m = 0; $f = 0; $total = 0;
        foreach ($data as $index => $row) {
            $sx = strtoupper(trim($row['sexo'] ?? ''));
            if ($sx === 'M') { $m++; $sxLabel = 'Masculino'; }
            elseif ($sx === 'F') { $f++; $sxLabel = 'Femenino'; }
            else { $sxLabel = ''; }
            $rows[] = [
                $index + 1,
                $row['nombre_completo'],
                $sxLabel,
                $row['grupo'] ?? '',
            ];
            $total++;
        }
        $rows[] = ['','','',''];
        $rows[] = ['Resumen por sexo','','',''];
        $rows[] = ['Masculino',$m,'',''];
        $rows[] = ['Femenino',$f,'',''];
        $rows[] = ['Total',$total,'',''];
        $binary = \App\Utils\SimpleXlsxGenerator::generate($headings, $rows);
        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="lista_alumnos.xlsx"'
        ]);
    }

    public function grupos(OrganizarListasRequest $request): \Illuminate\Http\JsonResponse
    {
        $p = $request->input('periodo_lectivo_id');
        $g = $request->input('grado_id');
        $t = $request->input('turno_id');
        $query = DB::table('config_grupos')
            ->join('config_grado','config_grupos.grado_id','=','config_grado.id')
            ->join('config_seccion','config_grupos.seccion_id','=','config_seccion.id')
            ->join('config_turnos','config_grupos.turno_id','=','config_turnos.id')
            ->select('config_grupos.id','config_grupos.periodo_lectivo_id','config_grado.nombre as grado','config_seccion.nombre as seccion','config_turnos.nombre as turno');
        if ($p) $query->where('config_grupos.periodo_lectivo_id',$p);
        if ($g) $query->where('config_grupos.grado_id',$g);
        if ($t) $query->where('config_grupos.turno_id',$t);
        $grupos = $query->orderBy('config_turnos.orden')->orderBy('config_grado.orden')->orderBy('config_seccion.orden')->get();
        return $this->successResponse($grupos,'Grupos obtenidos exitosamente');
    }

    public function asignarGrupo(OrganizarListasRequest $request): \Illuminate\Http\JsonResponse
    {
        $asignaciones = $request->input('asignaciones');
        if (is_array($asignaciones) && count($asignaciones) > 0) {
            $affected = 0;
            foreach ($asignaciones as $item) {
                $userId = (int) ($item['user_id'] ?? 0);
                $grupoId = (int) ($item['grupo_id'] ?? 0);
                if (!$userId || !$grupoId) {
                    continue;
                }
                $grupo = DB::table('config_grupos')->where('id', $grupoId)->first();
                if (!$grupo) {
                    continue;
                }
                $updated = DB::table('users_grupos')
                    ->where('user_id', $userId)
                    ->where('periodo_lectivo_id', $grupo->periodo_lectivo_id)
                    ->update([
                        'grupo_id' => $grupoId,
                        'grado_id' => $grupo->grado_id,
                        'turno_id' => $grupo->turno_id,
                    ]);
                $affected += $updated;
            }
            return $this->successResponse(['registros_actualizados' => $affected], 'Asignaciones procesadas exitosamente');
        }

        $alumnos = $request->input('alumnos', []);
        $grupoId = (int) $request->input('grupo_id');
        $grupo = DB::table('config_grupos')->where('id', $grupoId)->first();
        if (!$grupo) {
            return $this->errorResponse('Grupo no encontrado', [], 404);
        }
        $affected = 0;
        foreach ($alumnos as $userId) {
            $updated = DB::table('users_grupos')
                ->where('user_id', $userId)
                ->where('periodo_lectivo_id', $grupo->periodo_lectivo_id)
                ->update([
                    'grupo_id' => $grupoId,
                    'grado_id' => $grupo->grado_id,
                    'turno_id' => $grupo->turno_id,
                ]);
            $affected += $updated;
        }
        return $this->successResponse(['registros_actualizados' => $affected], 'Alumnos asignados al grupo exitosamente');
    }
}
