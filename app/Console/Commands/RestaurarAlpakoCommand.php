<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use ZipArchive;
use App\Models\Condominio;
use App\Models\CondominioCuentaBancaria;
use App\Models\CondominioConcepto;
use App\Models\CondominioAlicuota;
use App\Models\Apartamento;
use App\Models\Propietario;
use App\Models\User;
use App\Models\Role;
use App\Models\Invoice;
use App\Models\Payment;

class RestaurarAlpakoCommand extends Command
{
    protected $signature = 'condominio:restaurar-alpako';
    protected $description = 'Restaura el condominio Residencias Alpako (36 apartamentos, 66 usuarios, 73 facturas) desde el backup de seguridad oficial.';

    public function handle(): int
    {
        $this->output->title('📦 RESTAURACIÓN DE CONDOMINIO RESIDENCIAS ALPAKO');

        $zipFile = storage_path('app/backups/backup_azpro_2026-09-14_113610.zip');
        if (!file_exists($zipFile)) {
            $this->error("No se encontró el archivo de backup en: {$zipFile}");
            return 1;
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== TRUE) {
            $this->error("No se pudo abrir el archivo ZIP de backup.");
            return 1;
        }

        $content = $zip->getFromName('backup_azpro_2026-09-14_113610.json');
        $data = json_decode($content, true);
        $zip->close();

        $alpakoCondoData = array_values(array_filter($data['tablas']['condominios'] ?? [], fn($c) => ($c['rif'] ?? '') === 'J-300576531'))[0] ?? null;
        if (!$alpakoCondoData) {
            $this->error("Condominio Alpako no encontrado en el archivo JSON del backup.");
            return 1;
        }

        $oldCondoId = $alpakoCondoData['id'];

        DB::beginTransaction();
        try {
            // Eliminar versión previa de Alpako si ya existe (incluyendo soft deletes)
            $existingCondo = Condominio::withTrashed()->where('rif', 'J-300576531')->first();
            if ($existingCondo) {
                $eId = $existingCondo->id;
                DB::table('payments')->where('condominio_id', $eId)->delete();
                DB::table('invoices')->where('condominio_id', $eId)->delete();
                DB::table('propietarios')->where('condominio_id', $eId)->delete();
                DB::table('apartamentos')->where('condominio_id', $eId)->delete();
                DB::table('users')->where('condominio_id', $eId)->delete();
                DB::table('condominio_cuentas_bancarias')->where('condominio_id', $eId)->delete();
                DB::table('condominio_conceptos')->where('condominio_id', $eId)->delete();
                DB::table('condominio_alicuotas')->where('condominio_id', $eId)->delete();
                DB::table('condominio_user')->where('condominio_id', $eId)->delete();
                $existingCondo->forceDelete();
            }

            // Insertar Condominio Alpako
            $newCondo = Condominio::create([
                'nombre' => $alpakoCondoData['nombre'],
                'tipo_entidad' => $alpakoCondoData['tipo_entidad'] ?? 'edificio_independiente',
                'rif' => $alpakoCondoData['rif'],
                'direccion' => $alpakoCondoData['direccion'],
                'telefono' => $alpakoCondoData['telefono'],
                'email' => $alpakoCondoData['email'],
                'numero_apartamentos' => $alpakoCondoData['numero_apartamentos'] ?? 36,
                'cuota_mantenimiento_base' => $alpakoCondoData['cuota_mantenimiento_base'] ?? 38339.45,
                'porcentaje_mora' => $alpakoCondoData['porcentaje_mora'] ?? 5.00,
                'dias_gracia' => $alpakoCondoData['dias_gracia'] ?? 5,
                'moneda_base' => $alpakoCondoData['moneda_base'] ?? 'VES',
                'tasa_cambio' => $alpakoCondoData['tasa_cambio'] ?? 785.07,
                'banco_nombre' => $alpakoCondoData['banco_nombre'] ?? 'BNC',
                'cuenta_bancaria_bs' => $alpakoCondoData['cuenta_bancaria_bs'] ?? '0191 0514 8221 0001 8351',
                'cuenta_bancaria_usd' => $alpakoCondoData['cuenta_bancaria_usd'] ?? '0191 0012 0223 1202 5152',
                'pago_movil_banco' => $alpakoCondoData['pago_movil_banco'] ?? 'BNC',
                'pago_movil_cedula' => $alpakoCondoData['pago_movil_cedula'] ?? 'J-300576531',
                'pago_movil_telefono' => $alpakoCondoData['pago_movil_telefono'] ?? '0412-377-95-75',
                'fondo_reserva_porcentaje' => $alpakoCondoData['fondo_reserva_porcentaje'] ?? 10.00,
                'fondo_reserva_acumulado' => $alpakoCondoData['fondo_reserva_acumulado'] ?? 2997.06,
                'notas_recibo' => $alpakoCondoData['notas_recibo'] ?? '',
                'plan_suscripcion' => 'premium',
                'fecha_vencimiento_suscripcion' => Carbon::now()->addYear(),
                'estado_suscripcion' => 'activo',
                'activo' => true,
            ]);
            $newCondoId = $newCondo->id;

            // Cuentas Bancarias
            $cuentaBncBs = CondominioCuentaBancaria::create([
                'condominio_id' => $newCondoId,
                'banco_nombre' => 'Banco Nacional de Crédito (BNC)',
                'tipo_cuenta' => 'corriente',
                'moneda' => 'VES',
                'numero_cuenta' => '01910514822100018351',
                'titular_nombre' => 'CONDOMINIO RESIDENCIAS ALPAKO',
                'titular_identificacion' => 'J-300576531',
                'telefono_pago_movil' => '0412-377-95-75',
                'es_pago_movil' => true,
                'instrucciones' => 'Transferencias BNC o Pago Móvil a J-300576531.',
                'activo' => true,
            ]);

            CondominioCuentaBancaria::create([
                'condominio_id' => $newCondoId,
                'banco_nombre' => 'Banco Nacional de Crédito (BNC)',
                'tipo_cuenta' => 'custodia',
                'moneda' => 'USD',
                'numero_cuenta' => '01910012022312025152',
                'titular_nombre' => 'CONDOMINIO RESIDENCIAS ALPAKO',
                'titular_identificacion' => 'J-300576531',
                'telefono_pago_movil' => null,
                'es_pago_movil' => false,
                'instrucciones' => 'Depósitos de divisas en efectivo BNC.',
                'activo' => true,
            ]);

            // Alícuotas
            $mapAlicuotas = [];
            $alpakoAlis = array_filter($data['tablas']['condominio_alicuotas'] ?? [], fn($a) => ($a['condominio_id'] ?? null) == $oldCondoId);
            foreach ($alpakoAlis as $oldAli) {
                $newAli = CondominioAlicuota::create([
                    'condominio_id' => $newCondoId,
                    'numero' => $oldAli['numero'],
                    'nombre' => $oldAli['nombre'],
                    'descripcion' => $oldAli['descripcion'] ?? '',
                    'activo' => (bool) ($oldAli['activo'] ?? true),
                ]);
                $mapAlicuotas[$oldAli['id']] = $newAli->id;
            }

            // Apartamentos
            $mapAptos = [];
            $alpakoAptos = array_filter($data['tablas']['apartamentos'] ?? [], fn($a) => ($a['condominio_id'] ?? null) == $oldCondoId);
            foreach ($alpakoAptos as $oldApto) {
                $newApto = Apartamento::create([
                    'condominio_id' => $newCondoId,
                    'numero' => $oldApto['numero'],
                    'piso' => (string) ($oldApto['piso'] ?? '1'),
                    'metros_cuadrados' => $oldApto['metros_cuadrados'] ?? 95.50,
                    'alicuota' => $oldApto['alicuota'] ?? 3.03460000,
                    'grupo_alicuota' => $oldApto['grupo_alicuota'] ?? '1',
                    'alicuota_2' => $oldApto['alicuota_2'] ?? 0,
                    'alicuota_3' => $oldApto['alicuota_3'] ?? 0,
                    'habitaciones' => $oldApto['habitaciones'] ?? 3,
                    'banos' => $oldApto['banos'] ?? 2,
                    'ocupado' => (bool) ($oldApto['ocupado'] ?? true),
                    'descripcion' => $oldApto['descripcion'] ?? "Apartamento {$oldApto['numero']}",
                ]);
                $mapAptos[$oldApto['id']] = $newApto->id;
            }

            // Usuarios
            $roleAdmin = Role::where('slug', 'admin-condominio')->first();
            $rolePropietario = Role::where('slug', 'propietario')->first();

            $mapUsers = [];
            $alpakoUsers = array_filter($data['tablas']['users'] ?? [], fn($u) => ($u['condominio_id'] ?? null) == $oldCondoId);
            foreach ($alpakoUsers as $oldUser) {
                $newAptoId = !empty($oldUser['apartamento_id']) ? ($mapAptos[$oldUser['apartamento_id']] ?? null) : null;
                $rId = (str_contains(strtolower($oldUser['rol'] ?? ''), 'admin')) ? $roleAdmin->id : $rolePropietario->id;

                $userExistente = User::where('email', $oldUser['email'])->first();
                if ($userExistente) {
                    $userExistente->update([
                        'condominio_id' => $newCondoId,
                        'apartamento_id' => $newAptoId,
                    ]);
                    $mapUsers[$oldUser['id']] = $userExistente->id;
                } else {
                    $newUser = User::create([
                        'name' => $oldUser['name'],
                        'email' => $oldUser['email'],
                        'password' => $oldUser['password'] ?: Hash::make('123456'),
                        'rol' => $oldUser['rol'] ?? 'Propietario/Residente',
                        'role_id' => $rId,
                        'condominio_id' => $newCondoId,
                        'apartamento_id' => $newAptoId,
                        'telefono' => $oldUser['telefono'] ?? null,
                        'cedula' => $oldUser['cedula'] ?? null,
                        'activo' => (bool) ($oldUser['activo'] ?? true),
                    ]);
                    $mapUsers[$oldUser['id']] = $newUser->id;
                }

                if (str_contains(strtolower($oldUser['rol'] ?? ''), 'admin')) {
                    $newCondo->administradores()->syncWithoutDetaching([$mapUsers[$oldUser['id']] => ['es_principal' => true]]);
                }
            }

            // Propietarios
            $alpakoProps = array_filter($data['tablas']['propietarios'] ?? [], fn($p) => ($p['condominio_id'] ?? null) == $oldCondoId);
            foreach ($alpakoProps as $oldProp) {
                $newAptoId = $mapAptos[$oldProp['apartamento_id']] ?? null;
                $newUserId = $mapUsers[$oldProp['user_id']] ?? null;
                if ($newAptoId && $newUserId) {
                    Propietario::create([
                        'apartamento_id' => $newAptoId,
                        'user_id' => $newUserId,
                        'condominio_id' => $newCondoId,
                        'nombre_completo' => $oldProp['nombre_completo'],
                        'cedula' => $oldProp['cedula'] ?? '',
                        'telefono' => $oldProp['telefono'] ?? '',
                        'email' => $oldProp['email'] ?? '',
                        'es_propietario_principal' => (bool) ($oldProp['es_propietario_principal'] ?? true),
                        'fecha_inicio' => $oldProp['fecha_inicio'] ?? '2015-01-01',
                        'activo' => (bool) ($oldProp['activo'] ?? true),
                    ]);
                }
            }

            // Conceptos de Gasto
            $conceptosSeeder = [
                ['ali' => '1', 'concepto' => '01 PRESTACIONES SOCIALES', 'monto' => 10.00],
                ['ali' => '1', 'concepto' => '02 SUELDOS Y SALARIOS TRABAJADORAS RESIDENCIALES', 'monto' => 0.50],
                ['ali' => '1', 'concepto' => '03 LEY POLÍTICA HABITACIONAL', 'monto' => 0.11],
                ['ali' => '1', 'concepto' => '04 IVSS', 'monto' => 0.10],
                ['ali' => '1', 'concepto' => '05 BONO LEY ALIMENTACION', 'monto' => 40.00],
                ['ali' => '1', 'concepto' => '06 BONO COMPLEMENTARIO', 'monto' => 55.00],
                ['ali' => '1', 'concepto' => '09 BONO COMPLEMENTARIO INTEGRAL INDEXADO', 'monto' => 145.00],
                ['ali' => '1', 'concepto' => 'ADQUISICIÓN Sistema de Cámaras, tuberías, fuente de poder, cableado 5 de 8', 'monto' => 165.32],
                ['ali' => '1', 'concepto' => 'CORPOELEC Jul-2026', 'monto' => 42.82],
                ['ali' => '1', 'concepto' => 'GASTOS ADMINISTRATIVOS SISTEMA AUTOMATIZADO ISAC junio y julio', 'monto' => 50.56],
                ['ali' => '1', 'concepto' => 'GASTOS ADMINISTRATIVOS Honorarios', 'monto' => 170.00],
                ['ali' => '1', 'concepto' => 'GASTOS BANCARIOS', 'monto' => 8.00],
                ['ali' => '1', 'concepto' => 'HIDROCAPITAL', 'monto' => 73.58],
                ['ali' => '1', 'concepto' => 'MANO DE OBRA Albañileria quitar frizo viejo y colocar el nuevo pared del lado del estacionamiento 1B, 6A, 11B, 2B. 4A 1 de 3', 'monto' => 116.67],
                ['ali' => '1', 'concepto' => 'MANTENIMIENTO DE JARDINES Y PODA DE ARBOLES', 'monto' => 140.00],
                ['ali' => '1', 'concepto' => 'MATERIALES Y ARTÍCULOS DE LIMPIEZA y Bolsas negras 1 de 2', 'monto' => 73.53],
                ['ali' => '1', 'concepto' => 'SERVICIO COPIA DEL PLANO ESTRUCTUAL DEL EDIFICIO', 'monto' => 11.00],
                ['ali' => '1', 'concepto' => 'SERVICIO RECOLECCION DE DESECHO Y ESCOMBROS', 'monto' => 20.00],
                ['ali' => '1', 'concepto' => 'SERVICIO DE TELÉFONO Jul-2026', 'monto' => 9.54],
                ['ali' => '1', 'concepto' => 'Z. COMISION X CRED. INMDTO', 'monto' => 0.00],
                ['ali' => '1', 'concepto' => 'Z. PUESTA DE AGUA', 'monto' => 0.00],
                ['ali' => '3', 'concepto' => 'ADQUISICIÓN E INSTALACION de un Contactor de 110 voltios para el circuito de alta velocidad ascensor par 1 de 3', 'monto' => 166.67],
                ['ali' => '3', 'concepto' => 'MANTENIMIENTO DE ASCENSORES Jul-2026', 'monto' => 80.00],
            ];
            foreach ($conceptosSeeder as $cs) {
                CondominioConcepto::create([
                    'condominio_id' => $newCondoId,
                    'ali' => $cs['ali'],
                    'concepto' => $cs['concepto'],
                    'monto_base' => $cs['monto'],
                    'tipo' => 'fijo',
                    'categoria' => 'mantenimiento',
                    'vigente_desde' => '2026-07-01',
                    'activo' => true,
                ]);
            }

            // Facturas
            $mapInvoices = [];
            $alpakoInvs = array_filter($data['tablas']['invoices'] ?? [], fn($i) => ($i['condominio_id'] ?? null) == $oldCondoId);
            foreach ($alpakoInvs as $oldInv) {
                $newAptoId = $mapAptos[$oldInv['apartamento_id']] ?? null;
                if ($newAptoId) {
                    $detallesGastos = is_string($oldInv['detalles_gastos']) ? json_decode($oldInv['detalles_gastos'], true) : $oldInv['detalles_gastos'];
                    $fondos = is_string($oldInv['fondos']) ? json_decode($oldInv['fondos'], true) : $oldInv['fondos'];

                    $numFactura = $oldInv['numero_factura'];
                    $existenteFact = Invoice::withoutGlobalScopes()->where('numero_factura', $numFactura)->first();
                    if ($existenteFact) {
                        $numFactura .= "-ALP";
                    }

                    $newInv = Invoice::create([
                        'apartamento_id' => $newAptoId,
                        'condominio_id' => $newCondoId,
                        'numero_factura' => $numFactura,
                        'numero_recibo_general' => $oldInv['numero_recibo_general'] ?? null,
                        'fecha_emision' => $oldInv['fecha_emision'] ?? '2026-07-31',
                        'fecha_vencimiento' => $oldInv['fecha_vencimiento'] ?? '2026-08-05',
                        'monto_total' => $oldInv['monto_total'],
                        'monto_total_usd' => $oldInv['monto_total_usd'] ?? 51.35,
                        'monto_alicuota_usd' => $oldInv['monto_alicuota_usd'] ?? 47.92,
                        'tasa_cambio' => $oldInv['tasa_cambio'] ?? 746.63,
                        'monto_pagado' => $oldInv['monto_pagado'] ?? 0,
                        'estado' => $oldInv['estado'] ?? 'pendiente',
                        'estado_certificacion' => $oldInv['estado_certificacion'] ?? 'certificado',
                        'descripcion' => $oldInv['descripcion'] ?? 'Aviso de cobro Alpako',
                        'periodo' => $oldInv['periodo'] ?? 'Julio del 2026',
                        'detalles_gastos' => $detallesGastos,
                        'fondos' => $fondos,
                        'tipo_recibo' => $oldInv['tipo_recibo'] ?? 'ordinario',
                        'titulo_proyecto' => $oldInv['titulo_proyecto'] ?? null,
                        'modalidad_calculo' => $oldInv['modalidad_calculo'] ?? 'alicuota',
                    ]);
                    $mapInvoices[$oldInv['id']] = $newInv->id;
                }
            }

            // Pagos
            $alpakoPays = array_filter($data['tablas']['payments'] ?? [], fn($p) => ($p['condominio_id'] ?? null) == $oldCondoId);
            foreach ($alpakoPays as $oldPay) {
                $newInvId = $mapInvoices[$oldPay['invoice_id']] ?? null;
                if ($newInvId) {
                    $newRegPor = $mapUsers[$oldPay['registrado_por']] ?? User::where('email', 'master@condominio.com')->value('id');
                    $p = Payment::create([
                        'invoice_id' => $newInvId,
                        'cuenta_bancaria_id' => $cuentaBncBs->id,
                        'condominio_id' => $newCondoId,
                        'monto' => $oldPay['monto'],
                        'tasa_cambio' => $oldPay['tasa_cambio'] ?? 746.63,
                        'fecha_pago' => $oldPay['fecha_pago'] ?? '2026-07-31',
                        'metodo_pago' => $oldPay['metodo_pago'] ?? 'pago_movil',
                        'referencia' => $oldPay['referencia'] ?? 'REF-ALPAKO',
                        'banco' => $oldPay['banco'] ?? 'BNC',
                        'estado' => $oldPay['estado'] ?? 'aprobado',
                        'observaciones' => $oldPay['observaciones'] ?? 'Pago restaurado Alpako',
                        'registrado_por' => $newRegPor,
                    ]);
                    $p->invoices()->attach($newInvId, ['monto_aplicado' => $oldPay['monto']]);
                }
            }

            DB::commit();

            $this->output->success('🎉 CONDOMINIO RESIDENCIAS ALPAKO RESTAURADO EXITOSAMENTE.');
            $this->table(
                ['Módulo', 'Cantidad Restaurada'],
                [
                    ['Condominio', "{$newCondo->nombre} (ID: {$newCondoId})"],
                    ['Apartamentos', count($mapAptos) . ' apartamentos'],
                    ['Usuarios', count($mapUsers) . ' usuarios copropietarios y admin'],
                    ['Propietarios', count($alpakoProps) . ' registros de titularidad'],
                    ['Conceptos de Gasto', count($conceptosSeeder) . ' conceptos desglosados'],
                    ['Facturas / Recibos', count($mapInvoices) . ' recibos de cobro'],
                    ['Cuentas Bancarias', '2 (BNC Cuenta Corriente Bs + BNC Divisas USD)'],
                ]
            );

            return 0;

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Error durante la restauración: ' . $e->getMessage());
            return 1;
        }
    }
}
