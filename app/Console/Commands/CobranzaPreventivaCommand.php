<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Models\NotificacionHistorial;
use App\Models\Condominio;
use Carbon\Carbon;

class CobranzaPreventivaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cobranza:preventiva {--condominio_id= : Filtrar ejecución por ID de un condominio}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecuta el ciclo de cobranza preventiva inteligente (avisos a 3 días, día de vencimiento y mora).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $condominioId = $this->option('condominio_id');
        $this->info('Iniciando ciclo automático de cobranza preventiva...');

        $query = Invoice::with(['apartamento.propietarios.user', 'condominio'])
            ->whereIn('estado', ['pendiente', 'parcial']);

        if ($condominioId) {
            $query->where('condominio_id', $condominioId);
        }

        $invoices = $query->get();
        $hoy = Carbon::today();

        $stats = [
            'preventivos_3dias' => 0,
            'vencen_hoy' => 0,
            'en_mora' => 0,
            'total_despachados' => 0,
        ];

        foreach ($invoices as $inv) {
            $emision = $inv->created_at ? Carbon::parse($inv->created_at) : Carbon::now();
            $fechaVencimiento = $inv->fecha_vencimiento ? Carbon::parse($inv->fecha_vencimiento) : $emision->copy()->addDays(5);
            $diasParaVencer = (int) $hoy->diffInDays($fechaVencimiento, false); // positivo = faltan días, negativo = vencido

            $aptoNumero = $inv->apartamento?->numero ?? 'N/A';
            $condoNombre = $inv->condominio?->nombre ?? 'Condominio';
            $montoUsd = number_format((float)$inv->monto_total, 2, ',', '.');
            $tasa = (float)($inv->tasa_efectiva ?: ($inv->condominio?->tasa_cambio ?: 36.50));
            $montoBs = number_format((float)$inv->monto_total * $tasa, 2, ',', '.');

            $plantilla = null;
            $mensaje = null;
            $categoria = null;

            if ($diasParaVencer === 3) {
                $plantilla = 'cobranza_preventiva_3dias';
                $categoria = 'preventivos_3dias';
                $mensaje = "🔔 AVISO PREVENTIVO ({$condoNombre}): Estimado residente del Apto {$aptoNumero}, le recordamos cordialmente que su recibo por \${$montoUsd} USD (Bs. {$montoBs}) vencerá en 3 días (" . $fechaVencimiento->format('d/m/Y') . "). Agradecemos reportar su comprobante a tiempo.";
            } elseif ($diasParaVencer === 0) {
                $plantilla = 'cobranza_vence_hoy';
                $categoria = 'vencen_hoy';
                $mensaje = "⚠️ AVISO DE VENCIMIENTO HOY ({$condoNombre}): Estimado residente del Apto {$aptoNumero}, hoy es la fecha límite para el pago de su cuota de condominio (\${$montoUsd} USD / Bs. {$montoBs}). Por favor reporte su pago para conservar su solvencia.";
            } elseif ($diasParaVencer < 0 && $diasParaVencer >= -15) {
                $plantilla = 'cobranza_en_mora';
                $categoria = 'en_mora';
                $diasAtraso = abs($diasParaVencer);
                $mensaje = "🚨 RECORDATORIO DE MOROSIDAD ({$condoNombre}): Estimado residente del Apto {$aptoNumero}, su recibo de condominio presenta {$diasAtraso} días de atraso (\${$montoUsd} USD / Bs. {$montoBs}). Evite recargos administrativos regularizando su cuenta a la brevedad.";
            }

            if (!$plantilla || !$mensaje) {
                continue;
            }

            $propietarios = $inv->apartamento?->propietarios ?? collect();
            foreach ($propietarios as $prop) {
                $user = $prop->user;
                $telefono = $user?->telefono ?: $prop->telefono;
                $email = $user?->email ?: $prop->email;

                if (!$telefono && !$email) {
                    continue;
                }

                // Evitar despachos duplicados en la misma fecha
                $yaNotificado = NotificacionHistorial::where('user_id', $user?->id)
                    ->where('plantilla', $plantilla)
                    ->whereDate('created_at', $hoy)
                    ->exists();

                if ($yaNotificado) {
                    continue;
                }

                NotificacionHistorial::create([
                    'condominio_id' => $inv->condominio_id,
                    'user_id' => $user?->id,
                    'tipo' => $telefono ? 'whatsapp' : 'email',
                    'plantilla' => $plantilla,
                    'destinatario' => $telefono ?: $email,
                    'mensaje' => $mensaje,
                    'estado' => 'enviado',
                ]);

                $stats[$categoria]++;
                $stats['total_despachados']++;
            }
        }

        $this->info("✅ Cobranza preventiva completada exitosamente.");
        $this->line("  • Avisos a 3 días: {$stats['preventivos_3dias']}");
        $this->line("  • Avisos vence hoy: {$stats['vencen_hoy']}");
        $this->line("  • Avisos de morosidad: {$stats['en_mora']}");
        $this->info("  • Total despachos generados: {$stats['total_despachados']}");

        return Command::SUCCESS;
    }
}
