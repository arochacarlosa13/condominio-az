<?php

namespace App\Services;

use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ConciliacionBancariaService
{
    /**
     * Parsea un archivo de extracto bancario (CSV o TXT delimitado) y realiza auto-matching contra pagos pendientes.
     *
     * @param string $filePath Ruta absoluta del archivo subido
     * @param int $condominioId
     * @param float $tasaCondominio
     * @return array
     */
    public function procesarExtracto(string $filePath, int $condominioId, float $tasaCondominio = 36.50): array
    {
        $contenido = file_get_contents($filePath);
        if ($contenido === false) {
            throw new \Exception('No se pudo leer el archivo de extracto bancario.');
        }

        // Detectar codificación (UTF-8 o ISO-8859-1 común en bancos venezolanos)
        if (!mb_check_encoding($contenido, 'UTF-8')) {
            $contenido = mb_convert_encoding($contenido, 'UTF-8', 'ISO-8859-1');
        }

        $lineas = preg_split('/\r\n|\r|\n/', trim($contenido));
        if (empty($lineas)) {
            throw new \Exception('El archivo de extracto está vacío.');
        }

        // Detectar delimitador: ;, ,, \t o |
        $delimitador = $this->detectarDelimitador($lineas[0]);

        // Parsear líneas a filas
        $filas = [];
        foreach ($lineas as $idx => $linea) {
            $linea = trim($linea);
            if ($linea === '') continue;
            $cols = str_getcsv($linea, $delimitador);
            $filas[] = array_map('trim', $cols);
        }

        if (empty($filas)) {
            throw new \Exception('No se encontraron filas con formato válido en el archivo.');
        }

        // Mapear encabezados
        $headerIndex = $this->encontrarEncabezados($filas);
        $indices = $headerIndex['indices'];
        $datosFilas = array_slice($filas, $headerIndex['data_start']);

        // Obtener pagos pendientes y en revisión de este condominio
        $pagosPendientes = Payment::with(['invoice.apartamento', 'invoices.apartamento'])
            ->where('condominio_id', $condominioId)
            ->whereIn('estado', ['pendiente', 'en_revision'])
            ->get();

        $movimientosBancarios = [];
        $idsPagosEmparejados = [];

        foreach ($datosFilas as $fila) {
            if (count($fila) < 2) continue;

            $fechaStr = $indices['fecha'] !== null && isset($fila[$indices['fecha']]) ? $fila[$indices['fecha']] : null;
            $refStr = $indices['referencia'] !== null && isset($fila[$indices['referencia']]) ? $fila[$indices['referencia']] : '';
            $descStr = $indices['descripcion'] !== null && isset($fila[$indices['descripcion']]) ? $fila[$indices['descripcion']] : '';
            $montoStr = $indices['monto'] !== null && isset($fila[$indices['monto']]) ? $fila[$indices['monto']] : '0';

            // Extraer referencia limpia de la fila o descripción si el banco junta la referencia en el concepto
            $referenciaLimpia = $this->limpiarReferencia($refStr);
            if (empty($referenciaLimpia)) {
                $referenciaLimpia = $this->extraerReferenciaDeTexto($descStr);
            }

            $monto = $this->normalizarMonto($montoStr);
            // Ignorar cargos / débitos (montos negativos o de 0)
            if ($monto <= 0) {
                continue;
            }

            $movimiento = [
                'fecha' => $this->normalizarFecha($fechaStr),
                'referencia_banco' => $referenciaLimpia ?: $refStr,
                'descripcion' => $descStr,
                'monto_bs' => round($monto, 2),
                'monto_usd' => $tasaCondominio > 0 ? round($monto / $tasaCondominio, 2) : 0,
                'match_status' => 'sin_coincidencia', // 'exacto', 'parcial', 'sin_coincidencia'
                'pago_id' => null,
                'pago_detalles' => null,
                'motivo_diferencia' => null,
            ];

            // Buscar matching en pagos pendientes
            $matchEncontrado = $this->buscarCoincidencia($movimiento, $pagosPendientes, $idsPagosEmparejados, $tasaCondominio);
            if ($matchEncontrado) {
                $movimiento['match_status'] = $matchEncontrado['status'];
                $movimiento['pago_id'] = $matchEncontrado['pago']->id;
                $movimiento['pago_detalles'] = [
                    'id' => $matchEncontrado['pago']->id,
                    'apartamento' => $matchEncontrado['apartamento_numero'],
                    'referencia_reportada' => $matchEncontrado['pago']->referencia,
                    'monto_reportado_bs' => (float)$matchEncontrado['pago']->monto,
                    'monto_reportado_usd' => (float)($matchEncontrado['pago']->monto_divisa ?: round($matchEncontrado['pago']->monto / $tasaCondominio, 2)),
                    'fecha_reportada' => $matchEncontrado['pago']->fecha_pago ? Carbon::parse($matchEncontrado['pago']->fecha_pago)->format('Y-m-d') : null,
                    'metodo_pago' => $matchEncontrado['pago']->metodo_pago,
                ];
                $movimiento['motivo_diferencia'] = $matchEncontrado['motivo'];
                $idsPagosEmparejados[] = $matchEncontrado['pago']->id;
            }

            $movimientosBancarios[] = $movimiento;
        }

        // Pagos pendientes en el sistema que NO aparecieron en el extracto
        $pagosNoEncontrados = $pagosPendientes->reject(function ($p) use ($idsPagosEmparejados) {
            return in_array($p->id, $idsPagosEmparejados);
        })->map(function ($p) use ($tasaCondominio) {
            $apto = $p->invoice?->apartamento ?: $p->invoices?->first()?->apartamento;
            return [
                'id' => $p->id,
                'apartamento' => $apto?->numero ?? 'N/A',
                'referencia' => $p->referencia,
                'monto_bs' => (float)$p->monto,
                'monto_usd' => (float)($p->monto_divisa ?: round($p->monto / $tasaCondominio, 2)),
                'fecha_pago' => $p->fecha_pago ? Carbon::parse($p->fecha_pago)->format('Y-m-d') : null,
                'metodo_pago' => $p->metodo_pago,
                'banco' => $p->banco,
            ];
        })->values();

        $totalConciliables = count(array_filter($movimientosBancarios, fn($m) => $m['match_status'] === 'exacto'));
        $totalParciales = count(array_filter($movimientosBancarios, fn($m) => $m['match_status'] === 'parcial'));
        $totalSinMatch = count(array_filter($movimientosBancarios, fn($m) => $m['match_status'] === 'sin_coincidencia'));

        return [
            'total_movimientos_leidos' => count($movimientosBancarios),
            'coincidencias_exactas' => $totalConciliables,
            'coincidencias_parciales' => $totalParciales,
            'movimientos_sin_pago' => $totalSinMatch,
            'pagos_sistema_no_conciliados' => $pagosNoEncontrados->count(),
            'movimientos' => $movimientosBancarios,
            'pagos_pendientes_no_encontrados' => $pagosNoEncontrados,
        ];
    }

    /**
     * Busca coincidencias entre un movimiento bancario y la lista de pagos pendientes.
     */
    protected function buscarCoincidencia(array $mov, $pagos, array $yaEmparejados, float $tasa): ?array
    {
        $refBanco = $this->limpiarReferencia($mov['referencia_banco']);
        $montoBanco = $mov['monto_bs'];

        // 1. Coincidencia Exacta: Referencia idéntica o que coincidan al menos los últimos 4-6 dígitos y monto exacto (+- 0.05 Bs)
        foreach ($pagos as $pago) {
            if (in_array($pago->id, $yaEmparejados)) continue;

            $refPago = $this->limpiarReferencia($pago->referencia ?? '');
            $montoPagoBs = (float)$pago->monto;

            $refCoincide = $this->compararReferencias($refBanco, $refPago);
            $difMonto = abs($montoBanco - $montoPagoBs);

            if ($refCoincide && $difMonto <= 0.05) {
                $apto = $pago->invoice?->apartamento ?: $pago->invoices?->first()?->apartamento;
                return [
                    'pago' => $pago,
                    'status' => 'exacto',
                    'apartamento_numero' => $apto?->numero ?? 'N/A',
                    'motivo' => 'Referencia y monto verificados con exactitud 100%.',
                ];
            }
        }

        // 2. Coincidencia Parcial: Referencia coincide pero monto difiere por céntimos o comisiones
        foreach ($pagos as $pago) {
            if (in_array($pago->id, $yaEmparejados)) continue;

            $refPago = $this->limpiarReferencia($pago->referencia ?? '');
            $montoPagoBs = (float)$pago->monto;
            $refCoincide = $this->compararReferencias($refBanco, $refPago);

            if ($refCoincide) {
                $apto = $pago->invoice?->apartamento ?: $pago->invoices?->first()?->apartamento;
                $dif = round($montoBanco - $montoPagoBs, 2);
                $signo = $dif > 0 ? "+{$dif}" : "{$dif}";
                return [
                    'pago' => $pago,
                    'status' => 'parcial',
                    'apartamento_numero' => $apto?->numero ?? 'N/A',
                    'motivo' => "Referencia coincide pero monto en banco difiere por Bs. {$signo}.",
                ];
            }
        }

        // 3. Coincidencia Parcial por Monto y Fecha (referencia omitida o diferente pero mismo monto exacto)
        if ($montoBanco > 0) {
            foreach ($pagos as $pago) {
                if (in_array($pago->id, $yaEmparejados)) continue;

                $montoPagoBs = (float)$pago->monto;
                if (abs($montoBanco - $montoPagoBs) <= 0.01) {
                    $apto = $pago->invoice?->apartamento ?: $pago->invoices?->first()?->apartamento;
                    return [
                        'pago' => $pago,
                        'status' => 'parcial',
                        'apartamento_numero' => $apto?->numero ?? 'N/A',
                        'motivo' => 'Monto exacto idéntico con referencia declarada distinta.',
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Compara dos números de referencia bancaria considerando prefijos y sufijos de dígitos.
     */
    protected function compararReferencias(string $ref1, string $ref2): bool
    {
        if (empty($ref1) || empty($ref2)) return false;
        if ($ref1 === $ref2) return true;

        // Si una contiene a la otra
        if (str_contains($ref1, $ref2) || str_contains($ref2, $ref1)) return true;

        // Comparar los últimos 4 a 8 dígitos comunes
        $digitos1 = preg_replace('/\D/', '', $ref1);
        $digitos2 = preg_replace('/\D/', '', $ref2);

        if (strlen($digitos1) >= 4 && strlen($digitos2) >= 4) {
            $minLen = min(strlen($digitos1), strlen($digitos2), 6);
            $sub1 = substr($digitos1, -$minLen);
            $sub2 = substr($digitos2, -$minLen);
            if ($sub1 === $sub2) return true;
        }

        return false;
    }

    protected function limpiarReferencia(?string $ref): string
    {
        if (!$ref) return '';
        return strtoupper(trim(preg_replace('/[^a-zA-Z0-9]/', '', $ref)));
    }

    protected function extraerReferenciaDeTexto(string $texto): string
    {
        if (preg_match('/\b\d{4,12}\b/', $texto, $matches)) {
            return $matches[0];
        }
        return '';
    }

    protected function normalizarMonto(string $montoStr): float
    {
        $limpio = trim($montoStr);
        $limpio = str_replace(['Bs', '$', 'USD', ' '], '', $limpio);

        // Formato venezolano: 1.234,56 -> 1234.56
        if (str_contains($limpio, ',') && str_contains($limpio, '.')) {
            if (strrpos($limpio, ',') > strrpos($limpio, '.')) {
                $limpio = str_replace('.', '', $limpio);
                $limpio = str_replace(',', '.', $limpio);
            } else {
                $limpio = str_replace(',', '', $limpio);
            }
        } elseif (str_contains($limpio, ',')) {
            $limpio = str_replace(',', '.', $limpio);
        }

        return (float) preg_replace('/[^0-9.-]/', '', $limpio);
    }

    protected function normalizarFecha(?string $fechaStr): string
    {
        if (!$fechaStr) return Carbon::now()->format('Y-m-d');

        try {
            $fechaStr = trim($fechaStr);
            // Formatos usuales dd/mm/YYYY, YYYY-mm-dd, dd-mm-YYYY
            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $fechaStr, $m)) {
                return Carbon::createFromDate($m[3], $m[2], $m[1])->format('Y-m-d');
            }
            return Carbon::parse($fechaStr)->format('Y-m-d');
        } catch (\Throwable $e) {
            return Carbon::now()->format('Y-m-d');
        }
    }

    protected function detectarDelimitador(string $primeraLinea): string
    {
        $delimitadores = [';', ',', "\t", '|'];
        $maxCount = 0;
        $seleccionado = ';';

        foreach ($delimitadores as $d) {
            $count = substr_count($primeraLinea, $d);
            if ($count > $maxCount) {
                $maxCount = $count;
                $seleccionado = $d;
            }
        }

        return $seleccionado;
    }

    protected function encontrarEncabezados(array $filas): array
    {
        $indices = [
            'fecha' => 0,
            'referencia' => 1,
            'descripcion' => 2,
            'monto' => 3,
        ];

        foreach ($filas as $rowIdx => $fila) {
            $filaLower = array_map('strtolower', $fila);
            $encontroHeader = false;

            foreach ($filaLower as $colIdx => $col) {
                if (str_contains($col, 'fecha')) {
                    $indices['fecha'] = $colIdx;
                    $encontroHeader = true;
                } elseif (str_contains($col, 'ref') || str_contains($col, 'oper') || str_contains($col, 'secuencia') || str_contains($col, 'documento')) {
                    $indices['referencia'] = $colIdx;
                    $encontroHeader = true;
                } elseif (str_contains($col, 'desc') || str_contains($col, 'concepto') || str_contains($col, 'detalle')) {
                    $indices['descripcion'] = $colIdx;
                    $encontroHeader = true;
                } elseif (str_contains($col, 'monto') || str_contains($col, 'crédito') || str_contains($col, 'credito') || str_contains($col, 'importe') || str_contains($col, 'haber')) {
                    $indices['monto'] = $colIdx;
                    $encontroHeader = true;
                }
            }

            if ($encontroHeader) {
                return [
                    'indices' => $indices,
                    'data_start' => $rowIdx + 1,
                ];
            }
        }

        return [
            'indices' => $indices,
            'data_start' => 0,
        ];
    }
}
