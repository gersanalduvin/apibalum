<?php

namespace App\Services;

use App\Models\Recibo;
use App\Models\ReciboDetalle;
use App\Utils\SimpleXlsxGenerator;
use Barryvdh\Snappy\Facades\SnappyPdf as Pdf;
use Illuminate\Support\Facades\DB;

class ReporteCierreCajaService
{
    public function obtenerDetalles($tipo = 'todos', $fechaInicio = null, $fechaFin = null)
    {
        $query = ReciboDetalle::query()
            ->with(['rubro.rubro.planPago.periodoLectivo', 'recibo.usuario']) // Cargar relaciones necesarias para el resumen
            ->join('recibos', 'recibos_detalle.recibo_id', '=', 'recibos.id')
            ->whereNull('recibos.deleted_at')
            ->select('recibos_detalle.*', 'recibos.numero_recibo', 'recibos.tipo', 'recibos.fecha', 'recibos.estado', 'recibos.user_id as recibo_user_id');

        if ($tipo && $tipo !== 'todos') {
            $query->where('recibos.tipo', $tipo);
        }
        $query->when($fechaInicio, function ($q) use ($fechaInicio) {
            return $q->whereDate('recibos.fecha', '>=', $fechaInicio);
        })
            ->when($fechaFin, function ($q) use ($fechaFin) {
                return $q->whereDate('recibos.fecha', '<=', $fechaFin);
            })
            ->orderBy('recibos.tipo', 'asc')
            ->orderBy('recibos.fecha', 'asc')
            ->orderBy('recibos.numero_recibo', 'asc');

        return $query->get()->map(function ($detalle) {
            // Ensure these properties are directly available on the detalle object for consistency with previous logic
            // and for methods like generarExcelDetalles that expect them.
            $detalle->fecha = $detalle->recibo?->fecha;
            $detalle->numero_recibo = $detalle->recibo?->numero_recibo;
            $detalle->tipo = $detalle->recibo?->tipo;
            $detalle->nombre_usuario = $detalle->recibo?->usuario?->name ?? $detalle->recibo?->nombre_usuario ?? 'N/A';
            $detalle->estado = $detalle->recibo?->estado;
            $detalle->subtotal = $detalle->total;
            $detalle->total_recibo = $detalle->recibo?->total;
            return $detalle;
        });
    }

    public function obtenerDetallesPorConcepto($tipo, $fechaInicio, $fechaFin, $soloAranceles = false)
    {
        return ReciboDetalle::query()
            ->join('recibos', 'recibos.id', '=', 'recibos_detalle.recibo_id')
            ->where('recibos.estado', '!=', 'anulado')
            ->whereNull('recibos.deleted_at')
            ->when($tipo && $tipo !== 'todos', function ($q) use ($tipo) {
                $q->where('recibos.tipo', $tipo);
            })
            ->when($fechaInicio, function ($q) use ($fechaInicio) {
                $q->whereDate('recibos.fecha', '>=', $fechaInicio);
            })
            ->when($fechaFin, function ($q) use ($fechaFin) {
                $q->whereDate('recibos.fecha', '<=', $fechaFin);
            })
            ->when($soloAranceles, function ($q) {
                return $q->where(function ($sq) {
                    $sq->whereNotNull('recibos_detalle.rubro_id')
                        ->orWhereNotNull('recibos_detalle.aranceles_id');
                });
            })
            ->select('recibos_detalle.concepto', 'recibos_detalle.monto', DB::raw('SUM(recibos_detalle.cantidad) as cantidad'), DB::raw('SUM(recibos_detalle.total) as total'))
            ->groupBy('recibos_detalle.concepto', 'recibos_detalle.monto')
            ->orderBy('total', 'desc')
            ->get();
    }

    public function generarPdfDetalles($meta, $detalles)
    {
        $totalGeneral = $detalles->where('estado', '!=', 'anulado')->sum('total');
        $groupedDetalles = $detalles->groupBy(function ($item) {
            return $item->numero_recibo . '-' . $item->tipo;
        });

        $nombreInstitucion = config('app.nombre_institucion', 'MINISTERIO DE EDUCACIÓN');

        $resumenCategorizado = null;
        $tipoReporte = $meta['tipo'] ?? '';
        if (strtolower($tipoReporte) === 'externo') {
            $resumenCategorizado = $this->calcularResumenCategorizado($detalles);
        }

        $pdf = Pdf::loadView('reportes.caja.cierre_detalles', compact('meta', 'groupedDetalles', 'totalGeneral', 'nombreInstitucion', 'resumenCategorizado'));

        return $pdf->download('cierre_caja_detalles.pdf');
    }

    public function generarPdfConceptos($meta, $conceptos)
    {
        $nombreInstitucion = config('app.nombre_institucion', 'MINISTERIO DE EDUCACIÓN');

        // Calcular resumen categorizado para conceptos.
        // Necesitamos los detalles RAW para esto, los conceptos ya están agrupados.
        // Asumimos que podemos obtenerlos usando los filtros de meta.
        $fInicio = $meta['fecha_inicio'] ?? null;
        $fFin = $meta['fecha_fin'] ?? null;
        $tipoFiltro = $meta['tipo'] === 'Todos' ? 'todos' : strtolower($meta['tipo'] ?? 'todos');

        $detallesRaw = $this->obtenerDetalles($tipoFiltro, $fInicio, $fFin);

        $resumenCategorizado = null;
        if (strtolower($meta['tipo'] ?? '') === 'externo') {
            $resumenCategorizado = $this->calcularResumenCategorizado($detallesRaw);
        }

        $pdf = Pdf::loadView('reportes.caja.cierre_conceptos', compact('meta', 'conceptos', 'nombreInstitucion', 'resumenCategorizado'));
        return $pdf->download('cierre_caja_conceptos.pdf');
    }

    public function generarExcelDetalles($meta, $detalles)
    {
        $rows = [];
        foreach ($detalles as $d) {
            $isAnulado = strtolower($d->estado) === 'anulado';
            $rowData = [
                $d->fecha ? $d->fecha->format('d/m/Y') : '',
                $d->numero_recibo,
                $d->nombre_usuario,
                $d->concepto,
                $d->cantidad, // Raw number
                (float)$d->monto, // Raw number
                (float)$d->descuento, // Raw number
                (float)$d->subtotal, // Raw number
                ucfirst($d->estado),
                (float)$d->total_recibo // Raw number
            ];

            if ($isAnulado) {
                // Apply style 4 (Red + Bordered)
                $rows[] = ['data' => $rowData, 'style' => 4];
            } else {
                $rows[] = $rowData;
            }
        }

        // Summary row
        $totalRecibos = $detalles->unique('numero_recibo')->filter(function ($d) {
            return strtolower($d->estado) !== 'anulado';
        })->sum('total_recibo');

        $rows[] = ['', '', '', '', '', '', '', '', 'TOTAL', (float)$totalRecibos];

        $metaRows = [
            ['TIPO', ucfirst($meta['tipo'] ?? 'Todos')],
            ['DESDE', $meta['fecha_inicio'] ?? 'N/A'],
            ['HASTA', $meta['fecha_fin'] ?? 'N/A'],
            ['GENERADO', now()->format('d/m/Y H:i A')],
            []
        ];

        $headings = ['Fecha', 'N° Recibo', 'Usuario/Cliente', 'Concepto', 'Cantidad', 'Precio', 'Descuento', 'Subtotal', 'Estado', 'Total Recibo'];

        // Calculate Merges for "Total Recibo" (Column J, index 9 => Letter J)
        // Rows start at: count($metaRows) + 1 (header) + 1 (row 1 is 1-based index)
        // Excel row index for first data row: count($metaRows) + 2

        $startRowOffset = count($metaRows) + 2;
        $merges = [];
        $currentRecibo = null;
        $startRow = 0;

        foreach ($rows as $index => $row) {
            // Skip summary row (last one)
            if ($index === count($rows) - 1) continue;

            $rowData = $row;
            if (isset($row['data'])) {
                $rowData = $row['data'];
            }

            $recibo = $rowData[1]; // Index 1 is 'Numero Recibo'

            if ($index === 0) {
                $currentRecibo = $recibo;
                $startRow = $startRowOffset + $index;
                $consecutiveCount = 1;
                continue;
            }

            if ($recibo === $currentRecibo) {
                $consecutiveCount++;
            } else {
                if ($consecutiveCount > 1) {
                    $endRow = $startRowOffset + $index - 1;
                    $merges[] = "J{$startRow}:J{$endRow}";
                }
                $currentRecibo = $recibo;
                $startRow = $startRowOffset + $index;
                $consecutiveCount = 1;
            }
        }
        // Handle last group
        if ($consecutiveCount > 1) {
            // The last data row index is count($rows) - 2
            $endRow = $startRowOffset + (count($rows) - 2);
            $merges[] = "J{$startRow}:J{$endRow}";
        }

        // Generar resumen categorizado SOLO si es Externo
        // $metaRows[0] es ['TIPO', 'Externo'] por ejemplo.
        // Pero $meta['tipo'] también está disponible en la llamada original? No, aquí solo tenemos $metaRows.
        // Necesitamos chequear $metaRows para el tipo.

        $esExterno = false;
        if (isset($meta['tipo']) && strtolower($meta['tipo']) === 'externo') {
            $esExterno = true;
        }

        if ($esExterno) {
            $resumenCategorizado = $this->calcularResumenCategorizado($detalles);

            // Agregar espacio
            $rows[] = ['', '', '', '', '', '', '', '', '', ''];
            $rows[] = ['', '', '', '', '', '', '', '', '', ''];

            // Agregar tabla de resumen categorizado
            $rows[] = ['Categoría', '', '', '', '', '', '', '', '', 'Monto'];
            $rows[] = ['Mensualidades anticipadas', '', '', '', '', '', '', '', '', number_format($resumenCategorizado['anticipado'], 2)];
            $rows[] = ['Mensualidades del mes en curso', '', '', '', '', '', '', '', '', number_format($resumenCategorizado['en_curso'], 2)];
            $rows[] = ['Mensualidades atrasadas', '', '', '', '', '', '', '', '', number_format($resumenCategorizado['atrasado'], 2)];
            $rows[] = ['Total cierre de caja', '', '', '', '', '', '', '', '', number_format($resumenCategorizado['total_cierre'], 2)];

            // Styles for summary table
            $summaryHeaderIndex = count($rows) - 5;
            $rows[$summaryHeaderIndex] = ['data' => $rows[$summaryHeaderIndex], 'style' => 1];

            $summaryTotalIndex = count($rows) - 1;
            $rows[$summaryTotalIndex] = ['data' => $rows[$summaryTotalIndex], 'style' => 1];
        }


        $binary = SimpleXlsxGenerator::generateWithMeta($metaRows, $headings, $rows, $merges);

        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="cierre_caja_detalles.xlsx"'
        ]);
    }

    public function generarExcelConceptos($meta, $conceptos)
    {
        $rows = [];
        foreach ($conceptos as $c) {
            $rows[] = [
                $c->concepto,
                number_format($c->cantidad, 0),
                number_format($c->monto, 2),
                number_format($c->total, 2)
            ];
        }

        // Summary row
        $rows[] = ['TOTAL GENERAL', number_format($conceptos->sum('cantidad'), 0), '—', number_format($conceptos->sum('total'), 2)];

        $metaRows = [
            ['TIPO', ucfirst($meta['tipo'] ?? 'Todos')],
            ['DESDE', $meta['fecha_inicio'] ?? 'N/A'],
            ['HASTA', $meta['fecha_fin'] ?? 'N/A'],
            ['GENERADO', now()->format('d/m/Y H:i A')],
            []
        ];

        $headings = ['Concepto', 'Cantidad', 'Precio', 'Total'];

        // Recalcular detalles para el resumen categorizado (necesitamos los objetos ReciboDetalle originales, no los agrupados)
        $tipo = $meta['tipo'] === 'Todos' ? null : strtolower($meta['tipo']);

        // Solo si es Externo
        if ($tipo === 'externo') {
            // Parsear fechas de meta (d/m/Y o Y-m-d?) - Controller pone $fechaInicio tal cual.
            $fInicio = $meta['fecha_inicio'] ?? null;
            $fFin = $meta['fecha_fin'] ?? null;
            $tipoFiltro = 'externo'; // Ya validamos que es externo

            $detallesRaw = $this->obtenerDetalles($tipoFiltro, $fInicio, $fFin);
            $resumenCategorizado = $this->calcularResumenCategorizado($detallesRaw);

            // Agregar espacio
            $rows[] = ['', '', ''];
            $rows[] = ['', '', ''];

            // Agregar tabla de resumen categorizado
            $rows[] = ['Categoría', '', 'Monto'];
            $rows[] = ['Mensualidades anticipadas', '', number_format($resumenCategorizado['anticipado'], 2)];
            $rows[] = ['Mensualidades del mes en curso', '', number_format($resumenCategorizado['en_curso'], 2)];
            $rows[] = ['Mensualidades atrasadas', '', number_format($resumenCategorizado['atrasado'], 2)];
            $rows[] = ['Total cierre de caja', '', number_format($resumenCategorizado['total_cierre'], 2)];

            // Apply basic styling manually
            $summaryHeaderIndex = count($rows) - 5;
            $rows[$summaryHeaderIndex] = ['data' => $rows[$summaryHeaderIndex], 'style' => 1];

            $summaryTotalIndex = count($rows) - 1;
            $rows[$summaryTotalIndex] = ['data' => $rows[$summaryTotalIndex], 'style' => 1];
        }

        $binary = SimpleXlsxGenerator::generateWithMeta($metaRows, $headings, $rows);

        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="cierre_caja_conceptos.xlsx"'
        ]);
    }

    private function calcularResumenCategorizado($detalles)
    {
        $anticipado = 0;
        $en_curso = 0;
        $atrasado = 0;
        $total_cierre = 0;

        foreach ($detalles as $detalle) {
            try {
                // Sumar al total general (excluyendo anulados)
                if (strtolower($detalle->estado) === 'anulado') continue;

                $total_cierre += $detalle->total;

                // Cargar datos para analisis de fecha
                $configPlanPagoDetalle = null;

                // 1. Intentar via relación (si rubro_id es realmente UsersAranceles.id)
                $ua = $detalle->rubro;
                // Validar que el arancel pertenezca al usuario del recibo para evitar falsos positivos por IDs bajos
                if ($ua && $detalle->recibo && $ua->user_id == $detalle->recibo->user_id) {
                    $configPlanPagoDetalle = $ua->rubro;
                }

                // 2. Fallback: Si no hay relación válida, intentar cargar como ConfigPlanPagoDetalle directamente
                if (!$configPlanPagoDetalle && $detalle->rubro_id) {
                    $configPlanPagoDetalle = \App\Models\ConfigPlanPagoDetalle::find($detalle->rubro_id);
                }

                $mesPagoIndex = 0;
                $anioPago = null;

                if ($configPlanPagoDetalle) {
                    // Obtener indice del mes de pago [1-12]
                    $mesPagoIndex = $configPlanPagoDetalle->orden_mes;
                    if ($mesPagoIndex === 0 && $configPlanPagoDetalle->asociar_mes) {
                        $mesesMap = [
                            'enero' => 1,
                            'febrero' => 2,
                            'marzo' => 3,
                            'abril' => 4,
                            'mayo' => 5,
                            'junio' => 6,
                            'julio' => 7,
                            'agosto' => 8,
                            'septiembre' => 9,
                            'octubre' => 10,
                            'noviembre' => 11,
                            'diciembre' => 12
                        ];
                        $mesNombre = strtolower(trim($configPlanPagoDetalle->asociar_mes));
                        if (isset($mesesMap[$mesNombre])) {
                            $mesPagoIndex = $mesesMap[$mesNombre];
                        }
                    }

                    // Obtener año del plan
                    $periodoLectivoNombre = data_get($configPlanPagoDetalle, 'planPago.periodoLectivo.nombre');
                    if ($periodoLectivoNombre && preg_match('/(\d{4})/', $periodoLectivoNombre, $matches)) {
                        $anioPago = (int)$matches[1];
                    }
                }

                // 3. SEGUNDO FALLBACK: Parsear directamente del concepto
                if ($mesPagoIndex === 0 || !$anioPago) {
                    $concepto = strtoupper($detalle->concepto);
                    $mesesTexto = [
                        'ENERO' => 1,
                        'FEBRERO' => 2,
                        'MARZO' => 3,
                        'ABRIL' => 4,
                        'MAYO' => 5,
                        'JUNIO' => 6,
                        'JULIO' => 7,
                        'AGOSTO' => 8,
                        'SEPTIEMBRE' => 9,
                        'OCTUBRE' => 10,
                        'NOVIEMBRE' => 11,
                        'DICIEMBRE' => 12
                    ];

                    foreach ($mesesTexto as $mNom => $mInd) {
                        if (str_contains($concepto, $mNom)) {
                            $mesPagoIndex = $mInd;
                            break;
                        }
                    }

                    if (preg_match('/(\d{4})/', $concepto, $matches)) {
                        $anioPago = (int)$matches[1];
                    }
                }

                if ($mesPagoIndex > 0) {
                    // Fecha del recibo
                    $fechaRecibo = $detalle->recibo?->fecha ?? $detalle->fecha;

                    if (!($fechaRecibo instanceof \Carbon\Carbon)) {
                        try {
                            $fechaRecibo = \Carbon\Carbon::parse($fechaRecibo);
                        } catch (\Exception $e) {
                            $fechaRecibo = now();
                        }
                    }

                    $mesReciboIndex = $fechaRecibo->month;
                    $anioRecibo = $fechaRecibo->year;

                    if (!$anioPago) {
                        $anioPago = $anioRecibo;
                    }

                    // Logica de Clasificación
                    if ($anioPago > $anioRecibo) {
                        $anticipado += $detalle->total;
                    } elseif ($anioPago < $anioRecibo) {
                        $atrasado += $detalle->total;
                    } else {
                        if ($mesPagoIndex > $mesReciboIndex) {
                            $anticipado += $detalle->total;
                        } elseif ($mesPagoIndex == $mesReciboIndex) {
                            $en_curso += $detalle->total;
                        } else {
                            $atrasado += $detalle->total;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Error categorized
            }
        }

        return [
            'anticipado' => $anticipado,
            'en_curso' => $en_curso,
            'atrasado' => $atrasado,
            'total_cierre' => $total_cierre
        ];
    }
}
