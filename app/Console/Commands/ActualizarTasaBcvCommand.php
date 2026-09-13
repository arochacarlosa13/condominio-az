<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BcvRateService;
use App\Models\SelectOption;
use App\Models\Condominio;
use App\Models\AuditLog;
use Carbon\Carbon;

class ActualizarTasaBcvCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bcv:actualizar {--sync-all : Sincronizar automáticamente todas las torres y condominios activos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consulta la tasa oficial del Banco Central de Venezuela y actualiza la tasa central del sistema.';

    /**
     * Execute the console command.
     */
    public function handle(BcvRateService $bcvService)
    {
        $this->info('Consultando tasa oficial del Banco Central de Venezuela...');

        $resultado = $bcvService->obtenerTasaBcv();

        if (!$resultado['success']) {
            $this->error('Error: ' . ($resultado['message'] ?? 'No se pudo obtener la tasa del BCV'));
            return Command::FAILURE;
        }

        $tasaNueva = (float)$resultado['tasa'];
        $fechaValor = $resultado['fecha_valor'] ?? Carbon::now()->format('d/m/Y');
        $fuente = $resultado['fuente'] ?? 'BCV Oficial';

        $tasaAnterior = (float) (SelectOption::where('tipo', 'configuracion_general')
            ->where('valor', 'tasa_bcv_oficial')
            ->value('etiqueta') ?? 36.50);

        // Actualizar tasa central activa
        SelectOption::updateOrCreate(
            ['tipo' => 'configuracion_general', 'valor' => 'tasa_bcv_oficial'],
            ['etiqueta' => (string) $tasaNueva, 'activo' => true]
        );

        // Guardar en el histórico cronológico
        SelectOption::create([
            'tipo' => 'historico_tasa_bcv',
            'valor' => Carbon::now()->toDateTimeString(),
            'etiqueta' => (string) $tasaNueva,
            'activo' => true,
        ]);

        $syncAll = $this->option('sync-all');
        $actualizados = 0;

        if ($syncAll) {
            $actualizados = Condominio::query()->update(['tasa_cambio' => $tasaNueva]);
        }

        // Registrar en bitácora de auditoría
        AuditLog::create([
            'user_id' => null, // Sistema / Cron
            'accion' => 'actualizacion_automatica_bcv_cron',
            'modulo' => 'Cron Tasa BCV',
            'detalle' => "Actualización automática de tasa BCV a Bs. {$tasaNueva} (Anterior: Bs. {$tasaAnterior}). Fuente: {$fuente}. Fecha Valor: {$fechaValor}. Torres actualizadas: {$actualizados}.",
            'ip_address' => '127.0.0.1',
            'created_at' => Carbon::now(),
        ]);

        $this->info("✅ Tasa BCV actualizada exitosamente a Bs. {$tasaNueva}");
        $this->info("📅 Fecha Valor: {$fechaValor}");
        $this->info("🌐 Fuente: {$fuente}");
        if ($syncAll) {
            $this->info("🏢 Sincronizadas {$actualizados} torres/condominios activos.");
        }

        return Command::SUCCESS;
    }
}
