<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use ZipArchive;

class GenerarBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'condominio:backup {--retention-days=14 : Días de retención de respaldos anteriores}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera una copia de seguridad (backup) estructurada de la base de datos del sistema.';

    /**
     * Tablas prioritarias para el respaldo del sistema.
     */
    protected array $tablas = [
        'users',
        'condominios',
        'condominio_alicuotas',
        'apartamentos',
        'propietarios',
        'apartamento_propietario',
        'cuentas_bancarias',
        'gastos',
        'invoices',
        'invoice_items',
        'payments',
        'creditos',
        'transacciones_cuenta',
        'polls',
        'poll_options',
        'votes',
        'audit_trails',
        'select_options',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Iniciando generación de copia de seguridad del sistema...');

        $backupDir = storage_path('app/backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = Carbon::now()->format('Y-m-d_His');
        $baseName = "backup_azpro_{$timestamp}";
        $jsonFilePath = "{$backupDir}/{$baseName}.json";
        $zipFilePath = "{$backupDir}/{$baseName}.zip";

        $backupData = [
            'meta' => [
                'sistema' => 'AZPRO Condominio Inteligente',
                'version' => '2.5.0',
                'fecha_generacion' => Carbon::now()->toIso8601String(),
                'db_driver' => DB::getDriverName(),
                'tablas_incluidas' => [],
                'resumen_registros' => [],
            ],
            'tablas' => [],
        ];

        foreach ($this->tablas as $tabla) {
            try {
                if (DB::getSchemaBuilder()->hasTable($tabla)) {
                    $rows = DB::table($tabla)->get()->toArray();
                    $count = count($rows);
                    $backupData['tablas'][$tabla] = $rows;
                    $backupData['meta']['tablas_incluidas'][] = $tabla;
                    $backupData['meta']['resumen_registros'][$tabla] = $count;
                    $this->line("  ✓ Tabla [{$tabla}]: {$count} registros exportados");
                }
            } catch (\Throwable $e) {
                $this->warn("  ⚠ Error al exportar tabla [{$tabla}]: " . $e->getMessage());
            }
        }

        // Guardar JSON estructurado
        $jsonContent = json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        File::put($jsonFilePath, $jsonContent);

        $finalFile = $jsonFilePath;

        // Intentar empaquetar en ZIP si la extensión ZipArchive está disponible
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                $zip->addFile($jsonFilePath, "{$baseName}.json");
                $zip->close();
                // Eliminar el JSON intermedio para ahorrar espacio en disco
                File::delete($jsonFilePath);
                $finalFile = $zipFilePath;
                $this->info("  📦 Archivo empaquetado en formato ZIP comprimido");
            }
        }

        $sizeKb = round(filesize($finalFile) / 1024, 2);
        $this->info("✅ Copia de seguridad completada con éxito: {$finalFile} ({$sizeKb} KB)");

        // Limpieza de respaldos antiguos según días de retención
        $retentionDays = (int) $this->option('retention-days');
        $this->limpiarRespaldosAntiguos($backupDir, $retentionDays);

        return Command::SUCCESS;
    }

    /**
     * Elimina respaldos con una antigüedad mayor al umbral indicado.
     */
    protected function limpiarRespaldosAntiguos(string $dir, int $dias): void
    {
        $limite = Carbon::now()->subDays($dias)->getTimestamp();
        $archivos = File::files($dir);

        $eliminados = 0;
        foreach ($archivos as $archivo) {
            if ($archivo->getMTime() < $limite) {
                File::delete($archivo->getRealPath());
                $eliminados++;
            }
        }

        if ($eliminados > 0) {
            $this->line("  🧹 Se eliminaron {$eliminados} respaldos anteriores a {$dias} días.");
        }
    }
}
