<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Apartamento;
use App\Models\ApartamentoAlicuota;
use App\Models\Condominio;
use App\Models\CondominioAlicuota;
use App\Models\Propietario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApartamentoController extends BaseController
{
    public function index(Request $request)
    {
        $condominioId = $request->condominio_id ?? $this->obtenerCondominioActual();

        $query = Apartamento::with(['propietarios', 'condominio.parent', 'alicuotasAsignadas.condominioAlicuota']);

        if ($condominioId) {
            $query->where('condominio_id', $condominioId);
        }

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'LIKE', "%{$search}%")
                  ->orWhere('piso', 'LIKE', "%{$search}%")
                  ->orWhere('descripcion', 'LIKE', "%{$search}%")
                  ->orWhereHas('propietarios', function ($pq) use ($search) {
                      $pq->where('nombre_completo', 'LIKE', "%{$search}%")
                         ->orWhere('cedula', 'LIKE', "%{$search}%");
                  });
            });
        }

        if ($request->has('ocupado') && $request->ocupado !== '') {
            $query->where('ocupado', filter_var($request->ocupado, FILTER_VALIDATE_BOOLEAN));
        }

        $apartamentos = $query->orderBy('numero', 'asc')->paginate(50);
        return $this->respuestaExitosa($apartamentos);
    }

    public function store(Request $request)
    {
        $validationRules = [
            'numero' => 'required|string|max:50',
            'piso' => 'required|string|max:20',
            'alicuota' => 'nullable|numeric|min:0|max:100',
            'grupo_alicuota' => 'nullable|string|max:10',
            'metros_cuadrados' => 'required|numeric|min:1',
            'habitaciones' => 'nullable|integer|min:0',
            'banos' => 'nullable|integer|min:0',
            'ocupado' => 'boolean',
            'descripcion' => 'nullable|string',
            'condominio_id' => 'nullable|exists:condominios,id',
            'alicuotas' => 'nullable|array',
        ];

        for ($i = 2; $i <= 12; $i++) {
            $validationRules["alicuota_{$i}"] = 'nullable|numeric|min:0|max:100';
        }

        $request->validate($validationRules);

        $condominioId = $request->condominio_id ?? $this->obtenerCondominioActual();

        $aptoData = [
            'condominio_id' => $condominioId,
            'numero' => $request->numero,
            'piso' => $request->piso,
            'alicuota' => $request->alicuota ?? 3.03460000,
            'grupo_alicuota' => $request->grupo_alicuota ?? '1',
            'metros_cuadrados' => $request->metros_cuadrados,
            'habitaciones' => $request->habitaciones ?? 2,
            'banos' => $request->banos ?? 1,
            'ocupado' => $request->ocupado ?? true,
            'descripcion' => $request->descripcion,
        ];

        for ($i = 2; $i <= 12; $i++) {
            $aptoData["alicuota_{$i}"] = $request->input("alicuota_{$i}", 0.00000000);
        }

        DB::beginTransaction();
        try {
            $apartamento = Apartamento::create($aptoData);
            $this->sincronizarAlicuotas($apartamento, $request);
            DB::commit();

            return $this->respuestaExitosa(
                $apartamento->load(['propietarios', 'condominio.parent', 'alicuotasAsignadas.condominioAlicuota']),
                'Apartamento registrado exitosamente.',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respuestaError('Error al registrar apartamento: ' . $e->getMessage(), 500);
        }
    }

    public function show(Apartamento $apartamento)
    {
        return $this->respuestaExitosa($apartamento->load(['propietarios.user', 'invoices', 'condominio.parent', 'alicuotasAsignadas.condominioAlicuota']));
    }

    public function update(Request $request, Apartamento $apartamento)
    {
        $validationRules = [
            'numero' => 'required|string|max:50',
            'piso' => 'required|string|max:20',
            'alicuota' => 'nullable|numeric|min:0|max:100',
            'grupo_alicuota' => 'nullable|string|max:10',
            'metros_cuadrados' => 'required|numeric|min:1',
            'habitaciones' => 'nullable|integer|min:0',
            'banos' => 'nullable|integer|min:0',
            'ocupado' => 'boolean',
            'descripcion' => 'nullable|string',
            'condominio_id' => 'nullable|exists:condominios,id',
            'alicuotas' => 'nullable|array',
        ];

        for ($i = 2; $i <= 12; $i++) {
            $validationRules["alicuota_{$i}"] = 'nullable|numeric|min:0|max:100';
        }

        $request->validate($validationRules);

        DB::beginTransaction();
        try {
            $apartamento->update($request->all());
            $this->sincronizarAlicuotas($apartamento, $request);
            DB::commit();

            return $this->respuestaExitosa(
                $apartamento->load(['propietarios', 'condominio.parent', 'alicuotasAsignadas.condominioAlicuota']),
                'Apartamento actualizado exitosamente.'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respuestaError('Error al actualizar apartamento: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Apartamento $apartamento)
    {
        $apartamento->delete(); // Soft delete
        return $this->respuestaExitosa(null, 'Apartamento eliminado lógicamente.');
    }

    /**
     * Sincronizar alícuotas relacionales en tabla apartamento_alicuotas
     */
    private function sincronizarAlicuotas(Apartamento $apartamento, Request $request)
    {
        $condominioId = $apartamento->condominio_id;
        if (!$condominioId) return;

        // 1. Si vienen como lista estructurada 'alicuotas'
        if ($request->has('alicuotas') && is_array($request->alicuotas)) {
            foreach ($request->alicuotas as $item) {
                $aliId = $item['condominio_alicuota_id'] ?? null;
                $pct = (float)($item['porcentaje'] ?? 0);

                if ($aliId) {
                    ApartamentoAlicuota::updateOrCreate(
                        [
                            'apartamento_id' => $apartamento->id,
                            'condominio_alicuota_id' => $aliId,
                        ],
                        [
                            'porcentaje' => $pct,
                        ]
                    );

                    // Si es la alícuota 1, mantener actualizada la columna tradicional
                    $def = CondominioAlicuota::find($aliId);
                    if ($def && $def->numero === 1) {
                        $apartamento->updateQuietly(['alicuota' => $pct]);
                    }
                }
            }
            return;
        }

        // 2. Si vienen como inputs alicuota, alicuota_2, ..., alicuota_12
        $ali1Def = CondominioAlicuota::firstOrCreate(
            ['condominio_id' => $condominioId, 'numero' => 1],
            ['nombre' => 'Gastos Generales', 'activo' => true]
        );

        $pct1 = (float)($request->input('alicuota') ?? ($apartamento->alicuota ?: 3.03460000));
        ApartamentoAlicuota::updateOrCreate(
            ['apartamento_id' => $apartamento->id, 'condominio_alicuota_id' => $ali1Def->id],
            ['porcentaje' => $pct1]
        );

        for ($i = 2; $i <= 12; $i++) {
            $key = "alicuota_{$i}";
            if ($request->has($key)) {
                $pctVal = (float)$request->input($key);
                $aliDef = CondominioAlicuota::firstOrCreate(
                    ['condominio_id' => $condominioId, 'numero' => $i],
                    ['nombre' => "Alícuota {$i}", 'activo' => true]
                );

                ApartamentoAlicuota::updateOrCreate(
                    ['apartamento_id' => $apartamento->id, 'condominio_alicuota_id' => $aliDef->id],
                    ['porcentaje' => $pctVal]
                );
            }
        }
    }

    /**
     * Descargar plantilla base de Excel / CSV para importación de unidades con nombres de alícuotas del condominio
     */
    public function descargarPlantilla(Request $request)
    {
        $condominioId = $request->condominio_id ?? $this->obtenerCondominioActual();
        $condominio = $condominioId ? Condominio::with('alicuotas')->find($condominioId) : null;

        $alicuotasDef = $condominio && $condominio->alicuotas->isNotEmpty()
            ? $condominio->alicuotas
            : collect([
                (object)['numero' => 1, 'nombre' => 'Gastos Generales'],
                (object)['numero' => 2, 'nombre' => 'Torre / Sector'],
                (object)['numero' => 3, 'nombre' => 'Estacionamiento'],
            ]);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla_apartamentos_alicuotas.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($alicuotasDef) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Encabezados con nombres reales
            $csvHeaders = ['numero', 'piso'];
            foreach ($alicuotasDef as $def) {
                $cleanName = preg_replace('/[^A-Za-z0-9_]/', '_', strtolower($def->nombre));
                $csvHeaders[] = "alicuota_{$def->numero}_{$cleanName}";
            }
            $csvHeaders = array_merge($csvHeaders, [
                'metros_cuadrados',
                'grupo_alicuota',
                'habitaciones',
                'banos',
                'ocupado',
                'propietario_nombre',
                'propietario_cedula',
                'propietario_email',
                'propietario_telefono',
            ]);

            fputcsv($handle, $csvHeaders, ';');

            // Fila de ejemplo 1
            $row1 = ['1-A', '1'];
            foreach ($alicuotasDef as $def) {
                $row1[] = ($def->numero === 1) ? '3.12500000' : (($def->numero === 3) ? '0.50000000' : '0.00000000');
            }
            $row1 = array_merge($row1, ['85.50', '1', '3', '2', 'SI', 'Carlos Pérez', 'V-12345678', 'carlos.perez@ejemplo.com', '0414-1234567']);
            fputcsv($handle, $row1, ';');

            // Fila de ejemplo 2
            $row2 = ['1-B', '1'];
            foreach ($alicuotasDef as $def) {
                $row2[] = ($def->numero === 1) ? '3.12500000' : '0.00000000';
            }
            $row2 = array_merge($row2, ['85.50', '1', '3', '2', 'SI', 'María González', 'V-18765432', 'maria.gonzalez@ejemplo.com', '0412-7654321']);
            fputcsv($handle, $row2, ';');

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Procesar e importar archivo Excel / CSV de forma masiva
     */
    public function importarExcel(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->esMaster()) {
            return $this->respuestaError('Solo el Super Administrador tiene autorización para realizar importaciones masivas de inmuebles.', 403);
        }

        $request->validate([
            'archivo' => 'required|file|max:10240',
            'condominio_id' => 'nullable|exists:condominios,id',
        ]);

        $condominioId = $request->condominio_id ?? $this->obtenerCondominioActual();
        if (!$condominioId) {
            return $this->respuestaError('Debes seleccionar un condominio o torre activa para realizar la importación.', 422);
        }

        $file = $request->file('archivo');
        $filePath = $file->getRealPath();

        $rows = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            // Verificar si tiene BOM y avanzar
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            // Auto-detectar delimitador (; o ,)
            $firstLine = fgets($handle);
            rewind($handle);
            if ($bom === "\xEF\xBB\xBF") {
                fread($handle, 3);
            }
            $delimiter = (substr_count($firstLine, ';') >= substr_count($firstLine, ',')) ? ';' : ',';

            $header = null;
            while (($data = fgetcsv($handle, 4000, $delimiter)) !== false) {
                if (!$header) {
                    $header = array_map(function ($h) {
                        return strtolower(trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h)));
                    }, $data);
                    continue;
                }
                if (count($data) >= 2 && !empty(trim($data[0] ?? ''))) {
                    $row = [];
                    foreach ($header as $index => $colName) {
                        $row[$colName] = isset($data[$index]) ? trim($data[$index]) : null;
                    }
                    $rows[] = $row;
                }
            }
            fclose($handle);
        }

        if (empty($rows)) {
            return $this->respuestaError('El archivo subido no contiene filas de datos válidas para procesar.', 422);
        }

        $creados = 0;
        $actualizados = 0;

        DB::beginTransaction();
        try {
            // Obtener o crear catálogo de alícuotas del condominio
            $alicuotasCatalogo = CondominioAlicuota::where('condominio_id', $condominioId)->get();
            $ali1Def = $alicuotasCatalogo->firstWhere('numero', 1) ?? CondominioAlicuota::create([
                'condominio_id' => $condominioId,
                'numero' => 1,
                'nombre' => 'Gastos Generales',
                'activo' => true,
            ]);

            foreach ($rows as $row) {
                $numero = $row['numero'] ?? null;
                if (!$numero) continue;

                $piso = $row['piso'] ?? '1';

                // Detectar porcentaje para alicuota 1
                $alicuota1Raw = 0;
                foreach ($row as $colKey => $colVal) {
                    if (str_starts_with($colKey, 'alicuota_1') || $colKey === 'alicuota') {
                        $alicuota1Raw = str_replace(',', '.', (string)$colVal);
                        break;
                    }
                }
                $alicuota1 = is_numeric($alicuota1Raw) ? (float)$alicuota1Raw : 0;

                $metrosRaw = isset($row['metros_cuadrados']) ? str_replace(',', '.', (string)$row['metros_cuadrados']) : 70;
                $metros = is_numeric($metrosRaw) ? (float)$metrosRaw : 70.0;

                $grupo = $row['grupo_alicuota'] ?? '1';
                $habitaciones = isset($row['habitaciones']) && is_numeric($row['habitaciones']) ? (int)$row['habitaciones'] : 2;
                $banos = isset($row['banos']) && is_numeric($row['banos']) ? (int)$row['banos'] : 1;
                $ocupadoStr = strtolower(trim($row['ocupado'] ?? '1'));
                $ocupado = in_array($ocupadoStr, ['1', 'si', 'sí', 'true', 'ocupado', 'yes']);

                $apto = Apartamento::where('condominio_id', $condominioId)
                    ->where('numero', $numero)
                    ->first();

                $aptoPayload = [
                    'piso' => $piso,
                    'alicuota' => $alicuota1 > 0 ? $alicuota1 : ($apto ? $apto->alicuota : 3.03460000),
                    'metros_cuadrados' => $metros,
                    'grupo_alicuota' => $grupo,
                    'habitaciones' => $habitaciones,
                    'banos' => $banos,
                    'ocupado' => $ocupado,
                ];

                // Extraer alícuotas 2 a 12 si vienen en las columnas
                for ($i = 2; $i <= 12; $i++) {
                    $foundVal = null;
                    foreach ($row as $colKey => $colVal) {
                        if (str_starts_with($colKey, "alicuota_{$i}")) {
                            $raw = str_replace(',', '.', (string)$colVal);
                            $foundVal = is_numeric($raw) ? (float)$raw : 0.0;
                            break;
                        }
                    }
                    if ($foundVal !== null) {
                        $aptoPayload["alicuota_{$i}"] = $foundVal;
                    }
                }

                if ($apto) {
                    $apto->update($aptoPayload);
                    $actualizados++;
                } else {
                    $aptoPayload['condominio_id'] = $condominioId;
                    $aptoPayload['numero'] = $numero;
                    $apto = Apartamento::create($aptoPayload);
                    $creados++;
                }

                // Sincronizar en tabla relacional apartamento_alicuotas
                ApartamentoAlicuota::updateOrCreate(
                    ['apartamento_id' => $apto->id, 'condominio_alicuota_id' => $ali1Def->id],
                    ['porcentaje' => (float)$apto->alicuota]
                );

                for ($i = 2; $i <= 12; $i++) {
                    if (isset($aptoPayload["alicuota_{$i}"])) {
                        $pctExtra = (float)$aptoPayload["alicuota_{$i}"];
                        $aliDef = CondominioAlicuota::firstOrCreate(
                            ['condominio_id' => $condominioId, 'numero' => $i],
                            ['nombre' => "Alícuota {$i}", 'activo' => true]
                        );

                        ApartamentoAlicuota::updateOrCreate(
                            ['apartamento_id' => $apto->id, 'condominio_alicuota_id' => $aliDef->id],
                            ['porcentaje' => $pctExtra]
                        );
                    }
                }

                // Procesar propietario si viene en la plantilla
                $propNombre = $row['propietario_nombre'] ?? null;
                $propCedula = $row['propietario_cedula'] ?? null;
                if ($propNombre || $propCedula) {
                    $propEmail = $row['propietario_email'] ?? null;
                    $propTlf = $row['propietario_telefono'] ?? null;

                    Propietario::updateOrCreate(
                        [
                            'apartamento_id' => $apto->id,
                            'condominio_id' => $condominioId,
                        ],
                        [
                            'nombre_completo' => $propNombre ?: 'Propietario ' . $numero,
                            'cedula' => $propCedula ?: 'V-0',
                            'email' => $propEmail,
                            'telefono' => $propTlf,
                            'es_propietario_principal' => true,
                            'fecha_inicio' => now()->toDateString(),
                            'activo' => true,
                        ]
                    );
                }
            }

            DB::commit();

            $totalAlicuota = Apartamento::where('condominio_id', $condominioId)->sum('alicuota');

            return $this->respuestaExitosa([
                'creados' => $creados,
                'actualizados' => $actualizados,
                'total_procesados' => $creados + $actualizados,
                'suma_alicuotas' => round($totalAlicuota, 4),
            ], "Importación completada con éxito: {$creados} unidades creadas y {$actualizados} actualizadas.");

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respuestaError('Error durante el procesamiento del archivo: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Distribuir el 100% de las alícuotas equitativamente entre todos los apartamentos
     */
    public function distribuirEquitativo(Request $request)
    {
        $condominioId = $request->condominio_id ?? $this->obtenerCondominioActual();
        if (!$condominioId) {
            return $this->respuestaError('Debes seleccionar un condominio o torre activa.', 422);
        }

        $aptos = Apartamento::where('condominio_id', $condominioId)->orderBy('id')->get();
        $totalAptos = $aptos->count();

        if ($totalAptos === 0) {
            return $this->respuestaError('No hay apartamentos registrados para distribuir alícuotas.', 422);
        }

        $alicuotaExacta = round(100 / $totalAptos, 8);
        $sumaAcumulada = $alicuotaExacta * ($totalAptos - 1);
        $alicuotaUltimo = round(100 - $sumaAcumulada, 8);

        DB::beginTransaction();
        try {
            $ali1Def = CondominioAlicuota::firstOrCreate(
                ['condominio_id' => $condominioId, 'numero' => 1],
                ['nombre' => 'Gastos Generales', 'activo' => true]
            );

            foreach ($aptos as $idx => $apto) {
                $valor = ($idx === $totalAptos - 1) ? $alicuotaUltimo : $alicuotaExacta;
                $apto->update(['alicuota' => $valor]);

                ApartamentoAlicuota::updateOrCreate(
                    ['apartamento_id' => $apto->id, 'condominio_alicuota_id' => $ali1Def->id],
                    ['porcentaje' => $valor]
                );
            }
            DB::commit();

            return $this->respuestaExitosa(null, "Alícuotas distribuidas equitativamente al 100% entre los {$totalAptos} apartamentos.");
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respuestaError('Error al distribuir alícuotas: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Calcular alícuotas en base a los metros cuadrados (m²) de construcción
     */
    public function calcularPorMetraje(Request $request)
    {
        $condominioId = $request->condominio_id ?? $this->obtenerCondominioActual();
        if (!$condominioId) {
            return $this->respuestaError('Debes seleccionar un condominio o torre activa.', 422);
        }

        $aptos = Apartamento::where('condominio_id', $condominioId)->orderBy('id')->get();
        $totalMetros = (float)$aptos->sum('metros_cuadrados');

        if ($totalMetros <= 0) {
            return $this->respuestaError('No es posible calcular alícuotas por metraje porque la suma de metros cuadrados de los apartamentos es 0 m².', 422);
        }

        $totalAptos = $aptos->count();
        $sumaAcumulada = 0;

        DB::beginTransaction();
        try {
            $ali1Def = CondominioAlicuota::firstOrCreate(
                ['condominio_id' => $condominioId, 'numero' => 1],
                ['nombre' => 'Gastos Generales', 'activo' => true]
            );

            foreach ($aptos as $idx => $apto) {
                if ($idx === $totalAptos - 1) {
                    $alicuotaCalculada = round(100 - $sumaAcumulada, 8);
                } else {
                    $alicuotaCalculada = round(($apto->metros_cuadrados / $totalMetros) * 100, 8);
                    $sumaAcumulada += $alicuotaCalculada;
                }

                $apto->update(['alicuota' => $alicuotaCalculada]);

                ApartamentoAlicuota::updateOrCreate(
                    ['apartamento_id' => $apto->id, 'condominio_alicuota_id' => $ali1Def->id],
                    ['porcentaje' => $alicuotaCalculada]
                );
            }
            DB::commit();

            return $this->respuestaExitosa(null, "Alícuotas recalculadas con éxito en base al metraje total ({$totalMetros} m²).");
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respuestaError('Error al calcular alícuotas por metraje: ' . $e->getMessage(), 500);
        }
    }
}
