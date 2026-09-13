<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BcvRateService
{
    /**
     * Obtener la tasa oficial del BCV intentando primero APIs oficiales
     * y luego scraping directo al portal web del BCV con alta disponibilidad.
     *
     * @return array
     */
    public function obtenerTasaBcv(): array
    {
        // 1. Intentar API DolarApi Oficial (Alta velocidad y disponibilidad)
        try {
            $response = Http::withoutVerifying()
                ->timeout(8)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'application/json',
                ])
                ->get('https://ve.dolarapi.com/v1/dolares/oficial');

            if ($response->successful()) {
                $data = $response->json();
                $promedio = (float)($data['promedio'] ?? $data['monto'] ?? 0);
                if ($promedio > 0) {
                    $fecha = Carbon::now()->format('d/m/Y');
                    if (!empty($data['fechaActualizacion'])) {
                        try {
                            $fecha = Carbon::parse($data['fechaActualizacion'])->format('d/m/Y');
                        } catch (\Throwable $e) {}
                    }

                    return [
                        'success' => true,
                        'tasa' => round($promedio, 4),
                        'fecha_valor' => $fecha,
                        'fuente' => 'Banco Central de Venezuela (vía DolarApi Oficial)',
                        'metodo' => 'api_oficial',
                        'fecha_consulta' => Carbon::now()->toDateTimeString(),
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning("BcvRateService: Falló consulta a DolarApi: {$e->getMessage()}");
        }

        // 2. Intentar Scraping Directo al Portal Oficial del BCV (bcv.org.ve)
        try {
            $response = Http::withoutVerifying()
                ->timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'es-ES,es;q=0.9,en;q=0.8',
                ])
                ->get('https://www.bcv.org.ve/');

            if ($response->successful()) {
                $html = $response->body();

                // Buscar el bloque #dolar y su <strong> con el valor numérico
                if (preg_match('/<div\s+id=[\'"]dolar[\'"][^>]*>.*?<strong>\s*([0-9.,]+)\s*<\/strong>/is', $html, $matches)) {
                    $raw = trim($matches[1]);
                    $tasaStr = str_replace(',', '.', str_replace('.', '', $raw));
                    $tasa = (float)$tasaStr;

                    if ($tasa > 0) {
                        $fechaValor = Carbon::now()->format('d/m/Y');
                        if (preg_match('/<span\s+class=[\'"]date-display-single[\'"][^>]*content=[\'"]([^\'"]+)[\'"]/i', $html, $dateM)) {
                            try {
                                $fechaValor = Carbon::parse($dateM[1])->format('d/m/Y');
                            } catch (\Throwable $e) {
                                $fechaValor = $dateM[1];
                            }
                        }

                        return [
                            'success' => true,
                            'tasa' => round($tasa, 4),
                            'fecha_valor' => $fechaValor,
                            'fuente' => 'Portal Oficial Banco Central de Venezuela (bcv.org.ve)',
                            'metodo' => 'scraping_bcv',
                            'fecha_consulta' => Carbon::now()->toDateTimeString(),
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning("BcvRateService: Falló scraping directo al portal BCV: {$e->getMessage()}");
        }

        // 3. Fallback Internacional (Open Exchange Rate API)
        try {
            $response = Http::withoutVerifying()
                ->timeout(8)
                ->get('https://open.er-api.com/v6/latest/USD');

            if ($response->successful()) {
                $data = $response->json();
                $ves = (float)($data['rates']['VES'] ?? 0);
                if ($ves > 0) {
                    return [
                        'success' => true,
                        'tasa' => round($ves, 4),
                        'fecha_valor' => Carbon::now()->format('d/m/Y'),
                        'fuente' => 'Banco Central de Venezuela (vía ExchangeRate API)',
                        'metodo' => 'api_backup',
                        'fecha_consulta' => Carbon::now()->toDateTimeString(),
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning("BcvRateService: Falló consulta a ExchangeRate API: {$e->getMessage()}");
        }

        return [
            'success' => false,
            'message' => 'No fue posible consultar la tasa del BCV automáticamente en este momento. Puede ingresarla manualmente.',
        ];
    }
}
