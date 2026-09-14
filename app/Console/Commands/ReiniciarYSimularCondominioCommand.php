<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Menu;
use App\Models\SelectOption;
use App\Models\Plan;
use App\Models\Condominio;
use App\Models\CondominioCuentaBancaria;
use App\Models\CondominioConcepto;
use App\Models\CondominioAlicuota;
use App\Models\Apartamento;
use App\Models\ApartamentoAlicuota;
use App\Models\Propietario;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\CreditNote;
use App\Models\Expense;
use App\Models\CommonArea;
use App\Models\Reservation;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Vote;
use App\Models\Comunicado;
use App\Models\Incidencia;
use App\Models\NotificacionHistorial;
use App\Services\BcvRateService;

class ReiniciarYSimularCondominioCommand extends Command
{
    protected $signature = 'condominio:reiniciar-simulacion 
                            {--force : Ejecutar sin confirmación interactiva}
                            {--tasa= : Forzar una tasa BCV específica si se desea}';

    protected $description = 'Limpia la base de datos de datos previos de condominios y ejecuta una corrida completa de simulación con un condominio nuevo y todos los casos operativos y financieros.';

    public function handle(BcvRateService $bcvService): int
    {
        $this->output->title('🏢 REINICIO LIMPIO Y SIMULACIÓN COMPLETA DE CONDOMINIO (AZPRO)');

        if (!$this->option('force')) {
            if (!$this->confirm('⚠️ Esta acción eliminará todos los condominios, apartamentos, facturas y pagos anteriores para dejar el sistema como nuevo. ¿Desea continuar?', false)) {
                $this->warn('Operación cancelada por el usuario.');
                return 0;
            }
        }

        // =========================================================================
        // 1. OBTENCIÓN DE LA TASA OFICIAL DEL BCV (EN VIVO O PARÁMETRO)
        // =========================================================================
        $tasaForzada = $this->option('tasa');
        $tasaBcv = 832.4883; // Tasa base oficial de referencia del día
        $fuenteBcv = 'Tasa oficial BCV del día';

        if ($tasaForzada && is_numeric($tasaForzada)) {
            $tasaBcv = (float) $tasaForzada;
            $fuenteBcv = 'Parámetro forzado por usuario';
            $this->info("🔧 Tasa BCV fijada por parámetro: Bs. " . number_format($tasaBcv, 4, ',', '.'));
        } else {
            $this->comment('📡 Consultando tasa oficial del Banco Central de Venezuela en vivo...');
            $resBcv = $bcvService->obtenerTasaBcv();
            if (!empty($resBcv['success']) && !empty($resBcv['tasa'])) {
                $tasaBcv = (float) $resBcv['tasa'];
                $fuenteBcv = $resBcv['fuente'] ?? 'BCV Oficial';
                $this->info("✅ Tasa BCV obtenida en tiempo real: Bs. " . number_format($tasaBcv, 4, ',', '.') . " | Fuente: {$fuenteBcv}");
            } else {
                $this->warn("⚠️ No se pudo conectar a la API del BCV en este instante. Usando tasa bancaria del día: Bs. " . number_format($tasaBcv, 4, ',', '.'));
            }
        }

        // Actualizar opción global de tasa oficial en el catálogo select_options
        SelectOption::updateOrCreate(
            ['tipo' => 'configuracion_general', 'valor' => 'tasa_bcv_oficial'],
            [
                'etiqueta' => (string) $tasaBcv,
                'descripcion' => "Tasa oficial Banco Central de Venezuela al día de hoy ({$fuenteBcv})",
                'activo' => true,
            ]
        );

        // =========================================================================
        // 2. LIMPIEZA TOTAL DE TABLAS DE CONDOMINIO (PRESERVANDO PLATAFORMA SAAS)
        // =========================================================================
        $this->comment('🧹 Limpiando tablas de condominios previas...');

        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('TRUNCATE TABLE 
                votes, 
                poll_options, 
                polls, 
                notificacion_historial, 
                incidencias, 
                comunicados, 
                reservations, 
                common_areas, 
                visitantes, 
                commissions, 
                special_fees, 
                credit_notes, 
                invoice_payment, 
                payments, 
                invoices, 
                expenses, 
                condominio_conceptos, 
                condominio_cuentas_bancarias, 
                apartamento_alicuotas, 
                condominio_alicuotas, 
                propietarios, 
                apartamentos, 
                condominio_user, 
                saas_payments, 
                saas_invoices, 
                condominios 
                RESTART IDENTITY CASCADE;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            $tablas = [
                'votes', 'poll_options', 'polls', 'notificacion_historial', 'incidencias',
                'comunicados', 'reservations', 'common_areas', 'visitantes', 'commissions',
                'special_fees', 'credit_notes', 'invoice_payment', 'payments', 'invoices',
                'expenses', 'condominio_conceptos', 'condominio_cuentas_bancarias',
                'apartamento_alicuotas', 'condominio_alicuotas', 'propietarios', 'apartamentos',
                'condominio_user', 'saas_payments', 'saas_invoices', 'condominios'
            ];
            foreach ($tablas as $tb) {
                DB::table($tb)->delete();
            }
            DB::statement('PRAGMA foreign_keys = ON;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
            $tablas = [
                'votes', 'poll_options', 'polls', 'notificacion_historial', 'incidencias',
                'comunicados', 'reservations', 'common_areas', 'visitantes', 'commissions',
                'special_fees', 'credit_notes', 'invoice_payment', 'payments', 'invoices',
                'expenses', 'condominio_conceptos', 'condominio_cuentas_bancarias',
                'apartamento_alicuotas', 'condominio_alicuotas', 'propietarios', 'apartamentos',
                'condominio_user', 'saas_payments', 'saas_invoices', 'condominios'
            ];
            foreach ($tablas as $tb) {
                DB::table($tb)->truncate();
            }
            DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
        }

        // Eliminar usuarios excepto el Super Admin maestro
        DB::table('users')
            ->where('email', '!=', 'master@condominio.com')
            ->where('rol', '!=', 'Super Admin')
            ->delete();

        // Asegurar que el usuario Super Admin exista y esté 100% operativo
        $roleMaster = Role::firstOrCreate(['slug' => 'super-admin'], [
            'nombre' => 'Super Admin',
            'descripcion' => 'Dueño del sistema con acceso total.',
            'status' => true,
        ]);

        $roleAdmin = Role::firstOrCreate(['slug' => 'admin-condominio'], [
            'nombre' => 'Admin de Condominio',
            'descripcion' => 'Administrador de uno o varios condominios.',
            'status' => true,
        ]);

        $rolePropietario = Role::firstOrCreate(['slug' => 'propietario'], [
            'nombre' => 'Propietario/Residente',
            'descripcion' => 'Residente y copropietario de apartamento.',
            'status' => true,
        ]);

        User::updateOrCreate(['email' => 'master@condominio.com'], [
            'name' => 'Super Admin',
            'password' => Hash::make('123456'),
            'rol' => 'Super Admin',
            'role_id' => $roleMaster->id,
            'condominio_id' => null,
            'apartamento_id' => null,
            'telefono' => '+58 414 123 4567',
            'cedula' => 'V-12345678',
            'activo' => true,
            'dos_factores_activo' => false,
        ]);

        $this->info('✨ Base de datos purgada. Sistema listo como nuevo.');

        // =========================================================================
        // 3. CREACIÓN DEL NUEVO CONDOMINIO: RESIDENCIAS PARQUE CRISTAL
        // =========================================================================
        $this->comment('🏗️ Creando nuevo condominio: Residencias Parque Cristal...');

        $plan = Plan::where('nombre', 'like', '%Profesional%')->first() ?: Plan::first();

        $condominio = Condominio::create([
            'nombre' => 'Residencias Parque Cristal',
            'tipo_entidad' => 'edificio_independiente',
            'rif' => 'J-40129384-5',
            'direccion' => 'Av. Francisco de Miranda con Los Palos Grandes, Edif. Parque Cristal, Chacao, Caracas',
            'telefono' => '0212-285-4120',
            'email' => 'admon@parquecristal.com',
            'numero_apartamentos' => 10,
            'cuota_mantenimiento_base' => round(950.00 * $tasaBcv, 2),
            'porcentaje_mora' => 5.00,
            'dias_gracia' => 5,
            'moneda_base' => 'VES',
            'tasa_cambio' => $tasaBcv,
            'mantener_tasa_emision_5_dias' => true,
            'dias_congelar_tasa' => 5,
            'fondo_reserva_porcentaje' => 10.00,
            'fondo_reserva_acumulado' => round(3500.00 * $tasaBcv, 2),
            'banco_nombre' => 'Banesco / BNC',
            'cuenta_bancaria_bs' => '0134-0012-34-1234567890',
            'cuenta_bancaria_usd' => '0191-0023-45-9876543210',
            'pago_movil_banco' => '0134',
            'pago_movil_cedula' => 'J401293845',
            'pago_movil_telefono' => '04141234567',
            'notas_recibo' => "Vencimiento a los 5 días continuos de su emisión. Pagos en Bolívares a la tasa oficial BCV del día conforme al Art. 128 Ley BCV. Para transferencias en Bs: Cta Cte Banesco 0134-0012-34-1234567890 a nombre de Residencias Parque Cristal RIF J-40129384-5. Depósitos en divisas USD en BNC Cta Custodia 0191-0023-45-9876543210. Notifique su comprobante por el sistema AZPRO.",
            'plan_id' => $plan?->id,
            'plan_suscripcion' => 'premium',
            'fecha_vencimiento_suscripcion' => Carbon::now()->addYear(),
            'estado_suscripcion' => 'activo',
            'activo' => true,
        ]);

        // Cuentas Bancarias del Condominio
        $cuentaBs = CondominioCuentaBancaria::create([
            'condominio_id' => $condominio->id,
            'banco_nombre' => 'Banesco Banco Universal',
            'tipo_cuenta' => 'corriente',
            'moneda' => 'VES',
            'numero_cuenta' => '01340012341234567890',
            'titular_nombre' => 'Residencias Parque Cristal',
            'titular_identificacion' => 'J-40129384-5',
            'telefono_pago_movil' => '04141234567',
            'es_pago_movil' => true,
            'instrucciones' => 'Transferencias inmediatas o Pago Móvil Banesco (0134) al RIF J-40129384-5.',
            'activo' => true,
        ]);

        $cuentaUsd = CondominioCuentaBancaria::create([
            'condominio_id' => $condominio->id,
            'banco_nombre' => 'Banco Nacional de Crédito (BNC)',
            'tipo_cuenta' => 'custodia',
            'moneda' => 'USD',
            'numero_cuenta' => '01910023459876543210',
            'titular_nombre' => 'Residencias Parque Cristal',
            'titular_identificacion' => 'J-40129384-5',
            'telefono_pago_movil' => null,
            'es_pago_movil' => false,
            'instrucciones' => 'Depósito de divisas en efectivo taquilla BNC o transferencia cuenta verde BNC.',
            'activo' => true,
        ]);

        // Administradora de Condominio
        $adminCondo = User::create([
            'name' => 'Lic. Valentina Morales (Administradora)',
            'email' => 'admin@condominio.com',
            'password' => Hash::make('123456'),
            'rol' => 'Admin de Condominio',
            'role_id' => $roleAdmin->id,
            'condominio_id' => $condominio->id,
            'telefono' => '0414-285-4120',
            'cedula' => 'V-17845120',
            'activo' => true,
            'dos_factores_activo' => false,
        ]);

        $condominio->administradores()->attach($adminCondo->id, ['es_principal' => true]);

        // Alícuota General
        $alicuotaDef = CondominioAlicuota::create([
            'condominio_id' => $condominio->id,
            'numero' => 1,
            'nombre' => 'Gastos Comunes Generales',
            'descripcion' => 'Alícuota principal según documento de condominio',
            'activo' => true,
        ]);

        // Conceptos preconfigurados
        $conceptosBase = [
            ['concepto' => 'Mantenimiento de Ascensores (Otis)', 'monto' => 280.00, 'categoria' => 'mantenimiento'],
            ['concepto' => 'Servicio de Vigilancia Privada 24/7', 'monto' => 320.00, 'categoria' => 'sueldos'],
            ['concepto' => 'Servicio Eléctrico Áreas Comunes (Corpoelec)', 'monto' => 140.00, 'categoria' => 'servicios'],
            ['concepto' => 'Químicos, Limpieza y Áreas Verdes', 'monto' => 130.00, 'categoria' => 'mantenimiento'],
            ['concepto' => 'Mantenimiento Preventivo Sistema Hidroneumático', 'monto' => 110.00, 'categoria' => 'mantenimiento'],
        ];
        foreach ($conceptosBase as $cb) {
            CondominioConcepto::create([
                'condominio_id' => $condominio->id,
                'ali' => '1',
                'concepto' => $cb['concepto'],
                'monto_base' => $cb['monto'],
                'tipo' => 'fijo',
                'categoria' => $cb['categoria'],
                'vigente_desde' => '2026-01-01',
                'activo' => true,
            ]);
        }

        // =========================================================================
        // 4. CREACIÓN DE 10 APARTAMENTOS Y COPROPIETARIOS (SUMA ALÍCUOTA: 100.0000%)
        // =========================================================================
        $this->comment('🚪 Creando 10 apartamentos con alícuotas matemáticamente exactas (100.0000%)...');

        $apartamentosData = [
            ['numero' => '1-A', 'piso' => '1', 'alicuota' => 8.5000, 'm2' => 85.00, 'nombre' => 'Carlos Mendoza', 'email' => 'carlos.mendoza@gmail.com', 'cedula' => 'V-14238712', 'telf' => '0414-2345678'],
            ['numero' => '1-B', 'piso' => '1', 'alicuota' => 8.5000, 'm2' => 85.00, 'nombre' => 'Elena Morales', 'email' => 'elena.morales@gmail.com', 'cedula' => 'V-16452109', 'telf' => '0412-3456789'],
            ['numero' => '2-A', 'piso' => '2', 'alicuota' => 9.5000, 'm2' => 95.00, 'nombre' => 'Andrés Romero', 'email' => 'andres.romero@gmail.com', 'cedula' => 'V-12984561', 'telf' => '0416-4567890'],
            ['numero' => '2-B', 'piso' => '2', 'alicuota' => 9.5000, 'm2' => 95.00, 'nombre' => 'Beatriz Salazar', 'email' => 'beatriz.salazar@gmail.com', 'cedula' => 'V-17892341', 'telf' => '0424-5678901'],
            ['numero' => '3-A', 'piso' => '3', 'alicuota' => 10.0000, 'm2' => 100.00, 'nombre' => 'Roberto Gómez', 'email' => 'roberto.gomez@gmail.com', 'cedula' => 'V-11234567', 'telf' => '0414-6789012'],
            ['numero' => '3-B', 'piso' => '3', 'alicuota' => 10.0000, 'm2' => 100.00, 'nombre' => 'Marcos Vargas', 'email' => 'marcos.vargas@gmail.com', 'cedula' => 'V-13567890', 'telf' => '0412-7890123'],
            ['numero' => '4-A', 'piso' => '4', 'alicuota' => 11.0000, 'm2' => 110.00, 'nombre' => 'Daniel Rivas', 'email' => 'daniel.rivas@gmail.com', 'cedula' => 'V-15678901', 'telf' => '0416-8901234'],
            ['numero' => '4-B', 'piso' => '4', 'alicuota' => 11.0000, 'm2' => 110.00, 'nombre' => 'Diana Peña', 'email' => 'diana.pena@gmail.com', 'cedula' => 'V-18901234', 'telf' => '0424-9012345'],
            ['numero' => 'PH-1', 'piso' => '5', 'alicuota' => 11.0000, 'm2' => 135.00, 'nombre' => 'Patricia Colmenares', 'email' => 'patricia.colmenares@gmail.com', 'cedula' => 'V-10111222', 'telf' => '0414-0123456'],
            ['numero' => 'PH-2', 'piso' => '5', 'alicuota' => 11.0000, 'm2' => 135.00, 'nombre' => 'Fernando Castillo', 'email' => 'fernando.castillo@gmail.com', 'cedula' => 'V-9888777', 'telf' => '0412-1234567'],
        ];

        $apartamentosMap = [];

        foreach ($apartamentosData as $ad) {
            $apto = Apartamento::create([
                'condominio_id' => $condominio->id,
                'numero' => $ad['numero'],
                'piso' => $ad['piso'],
                'metros_cuadrados' => $ad['m2'],
                'alicuota' => $ad['alicuota'],
                'grupo_alicuota' => '1',
                'habitaciones' => $ad['piso'] === '5' ? 4 : 3,
                'banos' => $ad['piso'] === '5' ? 3 : 2,
                'ocupado' => true,
                'descripcion' => "Apartamento {$ad['numero']} - Piso {$ad['piso']}",
            ]);

            ApartamentoAlicuota::create([
                'apartamento_id' => $apto->id,
                'condominio_alicuota_id' => $alicuotaDef->id,
                'porcentaje' => $ad['alicuota'],
            ]);

            $ownerUser = User::create([
                'name' => $ad['nombre'],
                'email' => $ad['email'],
                'password' => Hash::make('123456'),
                'rol' => 'Propietario/Residente',
                'role_id' => $rolePropietario->id,
                'condominio_id' => $condominio->id,
                'apartamento_id' => $apto->id,
                'telefono' => $ad['telf'],
                'cedula' => $ad['cedula'],
                'activo' => true,
                'dos_factores_activo' => false,
            ]);

            Propietario::create([
                'apartamento_id' => $apto->id,
                'user_id' => $ownerUser->id,
                'condominio_id' => $condominio->id,
                'nombre_completo' => $ad['nombre'],
                'cedula' => $ad['cedula'],
                'telefono' => $ad['telf'],
                'email' => $ad['email'],
                'es_propietario_principal' => true,
                'fecha_inicio' => '2026-01-01',
                'activo' => true,
            ]);

            $apartamentosMap[$ad['numero']] = [
                'apto' => $apto,
                'user' => $ownerUser,
                'alicuota' => $ad['alicuota'],
            ];
        }

        // =========================================================================
        // 5. REGISTRO DE GASTOS COMUNES MENSUALES (ALIMENTA EL FLUJO DE CAJA EN VIVO)
        // =========================================================================
        $this->comment('📊 Registrando gastos comunes de los últimos 5 meses...');

        $gastosPeriodos = [
            '2026-05' => [
                'fecha' => '2026-05-10', 'tasa' => 790.50,
                'items' => [
                    ['desc' => 'Mantenimiento Preventivo Ascensores Otis Mayo', 'usd' => 280.00, 'cta' => $cuentaBs->id, 'cat' => 'mantenimiento'],
                    ['desc' => 'Servicio Vigilancia Privada 24 Horas Mayo', 'usd' => 320.00, 'cta' => $cuentaBs->id, 'cat' => 'sueldos'],
                    ['desc' => 'Electricidad Áreas Comunes Corpoelec', 'usd' => 130.00, 'cta' => $cuentaBs->id, 'cat' => 'servicios'],
                    ['desc' => 'Químicos y Desinfectantes de Limpieza General', 'usd' => 120.00, 'cta' => $cuentaUsd->id, 'cat' => 'mantenimiento'],
                ]
            ],
            '2026-06' => [
                'fecha' => '2026-06-10', 'tasa' => 805.20,
                'items' => [
                    ['desc' => 'Mantenimiento Preventivo Ascensores Otis Junio', 'usd' => 280.00, 'cta' => $cuentaBs->id, 'cat' => 'mantenimiento'],
                    ['desc' => 'Servicio Vigilancia Privada 24 Horas Junio', 'usd' => 320.00, 'cta' => $cuentaBs->id, 'cat' => 'sueldos'],
                    ['desc' => 'Electricidad Áreas Comunes Corpoelec', 'usd' => 140.00, 'cta' => $cuentaBs->id, 'cat' => 'servicios'],
                    ['desc' => 'Reparación de Bomba de Agua Nro 2 y Rodamientos', 'usd' => 180.00, 'cta' => $cuentaBs->id, 'cat' => 'mantenimiento'],
                ]
            ],
            '2026-07' => [
                'fecha' => '2026-07-10', 'tasa' => 818.40,
                'items' => [
                    ['desc' => 'Mantenimiento Preventivo Ascensores Otis Julio', 'usd' => 280.00, 'cta' => $cuentaBs->id, 'cat' => 'mantenimiento'],
                    ['desc' => 'Servicio Vigilancia Privada 24 Horas Julio', 'usd' => 320.00, 'cta' => $cuentaBs->id, 'cat' => 'sueldos'],
                    ['desc' => 'Electricidad Áreas Comunes Corpoelec', 'usd' => 145.00, 'cta' => $cuentaBs->id, 'cat' => 'servicios'],
                    ['desc' => 'Poda de Árboles y Jardinería Perimetral', 'usd' => 145.00, 'cta' => $cuentaUsd->id, 'cat' => 'mantenimiento'],
                ]
            ],
            '2026-08' => [
                'fecha' => '2026-08-10', 'tasa' => 825.10,
                'items' => [
                    ['desc' => 'Mantenimiento Preventivo Ascensores Otis Agosto', 'usd' => 280.00, 'cta' => $cuentaBs->id, 'cat' => 'mantenimiento'],
                    ['desc' => 'Servicio Vigilancia Privada 24 Horas Agosto', 'usd' => 320.00, 'cta' => $cuentaBs->id, 'cat' => 'sueldos'],
                    ['desc' => 'Electricidad Áreas Comunes Corpoelec', 'usd' => 150.00, 'cta' => $cuentaBs->id, 'cat' => 'servicios'],
                    ['desc' => 'Recarga Anual de Extintores y Sistema Contra Incendio', 'usd' => 200.00, 'cta' => $cuentaBs->id, 'cat' => 'mantenimiento'],
                ]
            ],
            '2026-09' => [
                'fecha' => '2026-09-08', 'tasa' => $tasaBcv,
                'items' => [
                    ['desc' => 'Mantenimiento Preventivo Ascensores Otis Septiembre', 'usd' => 280.00, 'cta' => $cuentaBs->id, 'cat' => 'mantenimiento'],
                    ['desc' => 'Servicio Vigilancia Privada 24 Horas Septiembre', 'usd' => 320.00, 'cta' => $cuentaBs->id, 'cat' => 'sueldos'],
                    ['desc' => 'Electricidad Áreas Comunes Corpoelec', 'usd' => 160.00, 'cta' => $cuentaBs->id, 'cat' => 'servicios'],
                    ['desc' => 'Pintura y Reparación de Iluminación en Pasillos', 'usd' => 220.00, 'cta' => $cuentaUsd->id, 'cat' => 'mantenimiento'],
                ]
            ],
        ];

        foreach ($gastosPeriodos as $periodoKey => $pData) {
            foreach ($pData['items'] as $g) {
                Expense::create([
                    'condominio_id' => $condominio->id,
                    'descripcion' => $g['desc'],
                    'monto_usd' => $g['usd'],
                    'monto_bs' => round($g['usd'] * $pData['tasa'], 2),
                    'tasa_cambio' => $pData['tasa'],
                    'categoria' => $g['cat'],
                    'fecha_gasto' => $pData['fecha'],
                    'estado_pago' => 'pagado',
                    'proveedor' => 'Proveedor de Servicios Residenciales',
                    'referencia_pago' => 'REF-' . rand(100000, 999999),
                    'cuenta_bancaria_id' => $g['cta'],
                    'registrado_por' => $adminCondo->id,
                ]);
            }
        }

        // =========================================================================
        // 6. GENERACIÓN DE RECIBOS MENSUALES (5 ORDINARIOS) Y CUOTAS EXTRAORDINARIAS (2)
        // =========================================================================
        $this->comment('📑 Generando 5 períodos ordinarios + 2 cuotas extraordinarias (70 facturas en total)...');

        $definicionPeriodos = [
            [
                'periodo' => '2026-05',
                'tipo' => 'ordinario',
                'rg' => 'RG2026-00001',
                'fecha_emision' => '2026-05-01',
                'fecha_vencimiento' => '2026-05-06',
                'tasa' => 790.50,
                'total_usd' => 935.00, // 850 gastos + 85 reserva
                'titulo' => null,
                'gastos_detalle' => [
                    ['concepto' => 'Mantenimiento Preventivo Ascensores', 'monto' => 280.00],
                    ['concepto' => 'Servicio Vigilancia Privada 24 Horas', 'monto' => 320.00],
                    ['concepto' => 'Electricidad Áreas Comunes Corpoelec', 'monto' => 130.00],
                    ['concepto' => 'Químicos y Desinfectantes de Limpieza', 'monto' => 120.00],
                ],
            ],
            [
                'periodo' => '2026-06',
                'tipo' => 'ordinario',
                'rg' => 'RG2026-00002',
                'fecha_emision' => '2026-06-01',
                'fecha_vencimiento' => '2026-06-06',
                'tasa' => 805.20,
                'total_usd' => 1012.00, // 920 gastos + 92 reserva
                'titulo' => null,
                'gastos_detalle' => [
                    ['concepto' => 'Mantenimiento Preventivo Ascensores', 'monto' => 280.00],
                    ['concepto' => 'Servicio Vigilancia Privada 24 Horas', 'monto' => 320.00],
                    ['concepto' => 'Electricidad Áreas Comunes Corpoelec', 'monto' => 140.00],
                    ['concepto' => 'Reparación de Bomba de Agua Nro 2', 'monto' => 180.00],
                ],
            ],
            [
                'periodo' => 'EXT-2026-01',
                'tipo' => 'extraordinario',
                'rg' => 'RG2026-00002',
                'fecha_emision' => '2026-06-15',
                'fecha_vencimiento' => '2026-07-15',
                'tasa' => 812.00,
                'total_usd' => 800.00,
                'titulo' => 'Impermeabilización General de Azotea y Techos',
                'gastos_detalle' => [
                    ['concepto' => 'Materiales de Manto Asfáltico 4mm y Primer', 'monto' => 450.00],
                    ['concepto' => 'Mano de Obra Especializada y Retiro de Escombros', 'monto' => 350.00],
                ],
            ],
            [
                'periodo' => '2026-07',
                'tipo' => 'ordinario',
                'rg' => 'RG2026-00003',
                'fecha_emision' => '2026-07-01',
                'fecha_vencimiento' => '2026-07-06',
                'tasa' => 818.40,
                'total_usd' => 979.00, // 890 gastos + 89 reserva
                'titulo' => null,
                'gastos_detalle' => [
                    ['concepto' => 'Mantenimiento Preventivo Ascensores', 'monto' => 280.00],
                    ['concepto' => 'Servicio Vigilancia Privada 24 Horas', 'monto' => 320.00],
                    ['concepto' => 'Electricidad Áreas Comunes Corpoelec', 'monto' => 145.00],
                    ['concepto' => 'Poda de Árboles y Jardinería', 'monto' => 145.00],
                ],
            ],
            [
                'periodo' => '2026-08',
                'tipo' => 'ordinario',
                'rg' => 'RG2026-00004',
                'fecha_emision' => '2026-08-01',
                'fecha_vencimiento' => '2026-08-06',
                'tasa' => 825.10,
                'total_usd' => 1045.00, // 950 gastos + 95 reserva
                'titulo' => null,
                'gastos_detalle' => [
                    ['concepto' => 'Mantenimiento Preventivo Ascensores', 'monto' => 280.00],
                    ['concepto' => 'Servicio Vigilancia Privada 24 Horas', 'monto' => 320.00],
                    ['concepto' => 'Electricidad Áreas Comunes Corpoelec', 'monto' => 150.00],
                    ['concepto' => 'Recarga Extintores y Sistema Contra Incendio', 'monto' => 200.00],
                ],
            ],
            [
                'periodo' => 'EXT-2026-02',
                'tipo' => 'extraordinario',
                'rg' => 'RG2026-00004',
                'fecha_emision' => '2026-08-15',
                'fecha_vencimiento' => '2026-09-15',
                'tasa' => 828.00,
                'total_usd' => 1200.00,
                'titulo' => 'Modernización y Tablero de Control de Ascensor Torre A',
                'gastos_detalle' => [
                    ['concepto' => 'Tarjeta Controladora Microprocesada y Variador Yaskawa', 'monto' => 750.00],
                    ['concepto' => 'Instalación Eléctrica, Cable Viajero y Pruebas de Carga', 'monto' => 450.00],
                ],
            ],
            [
                'periodo' => '2026-09',
                'tipo' => 'ordinario',
                'rg' => 'RG2026-00005',
                'fecha_emision' => '2026-09-01',
                'fecha_vencimiento' => '2026-09-06',
                'tasa' => $tasaBcv,
                'total_usd' => 1078.00, // 980 gastos + 98 reserva
                'titulo' => null,
                'gastos_detalle' => [
                    ['concepto' => 'Mantenimiento Preventivo Ascensores', 'monto' => 280.00],
                    ['concepto' => 'Servicio Vigilancia Privada 24 Horas', 'monto' => 320.00],
                    ['concepto' => 'Electricidad Áreas Comunes Corpoelec', 'monto' => 160.00],
                    ['concepto' => 'Pintura y Reparación de Iluminación en Pasillos', 'monto' => 220.00],
                ],
            ],
        ];

        $correlativoGlobalFactura = 1;
        $invoicesMatrix = []; // [aptoNumero => [periodo => Invoice]]

        foreach ($definicionPeriodos as $idxPeriodo => $defP) {
            $periodoKey = $defP['periodo'];
            $rgNumero = $defP['rg'];
            $numRgInt = ($idxPeriodo + 1);

            $fondosJson = ($defP['tipo'] === 'ordinario') ? [
                [
                    'nombre' => 'Fondo de Reserva (10%)',
                    'porcentaje' => 10.00,
                    'monto_usd' => round($defP['total_usd'] * (10 / 110), 2),
                ]
            ] : [];

            $sumaUsdAsignada = 0;
            $aptoArray = array_values($apartamentosMap);

            foreach ($aptoArray as $apIdx => $apItem) {
                $apNumero = $apItem['apto']->numero;
                $alicuota = $apItem['alicuota'];

                $montoAptoUsd = round($defP['total_usd'] * ($alicuota / 100), 2);

                // Compensación de redondeo al céntimo en el último apartamento
                if ($apIdx === count($aptoArray) - 1) {
                    $diferencia = round($defP['total_usd'] - ($sumaUsdAsignada + $montoAptoUsd), 2);
                    $montoAptoUsd += $diferencia;
                }
                $sumaUsdAsignada += $montoAptoUsd;

                $montoBs = round($montoAptoUsd * $defP['tasa'], 2);
                $numFactura = sprintf("RI2026-%d-%05d", $numRgInt, $correlativoGlobalFactura++);

                $descripcion = ($defP['tipo'] === 'extraordinario') 
                    ? "Cuota Extraordinaria {$defP['titulo']} ({$periodoKey}) - Apto {$apNumero}"
                    : "Recibo de Condominio Mes " . Carbon::parse($defP['fecha_emision'])->translatedFormat('F Y') . " - Apto {$apNumero}";

                $inv = Invoice::create([
                    'apartamento_id' => $apItem['apto']->id,
                    'condominio_id' => $condominio->id,
                    'numero_factura' => $numFactura,
                    'numero_recibo_general' => $rgNumero,
                    'fecha_emision' => $defP['fecha_emision'],
                    'fecha_vencimiento' => $defP['fecha_vencimiento'],
                    'monto_total' => $montoBs,
                    'monto_total_usd' => $montoAptoUsd,
                    'monto_alicuota_usd' => $montoAptoUsd,
                    'tasa_cambio' => $defP['tasa'],
                    'monto_pagado' => 0.00,
                    'estado' => 'pendiente',
                    'estado_certificacion' => 'certificado',
                    'fecha_certificacion' => $defP['fecha_emision'] . ' 08:00:00',
                    'certificado_por_id' => $adminCondo->id,
                    'descripcion' => $descripcion,
                    'detalles_gastos' => $defP['gastos_detalle'],
                    'fondos' => $fondosJson,
                    'periodo' => $periodoKey,
                    'tipo_recibo' => $defP['tipo'],
                    'titulo_proyecto' => $defP['titulo'],
                    'modalidad_calculo' => 'alicuota',
                ]);

                $invoicesMatrix[$apNumero][$periodoKey] = $inv;
            }
        }

        // =========================================================================
        // 7. APLICACIÓN DE PAGOS, CASOS DE PRUEBA Y MOROSIDAD REALISTA
        // =========================================================================
        $this->comment('💳 Simulando matriz exhaustiva de pagos, morosos, abonos, saldos a favor y rechazos...');

        $correlativoRp = 1;

        // Función auxiliar para registrar pago aprobado
        $registrarPagoAprobado = function(Invoice $inv, $metodo = 'transferencia', $cuentaId = null, $moneda = 'VES', $fecha = null, $ref = null) use (&$correlativoRp, $condominio, $adminCondo, $cuentaBs, $cuentaUsd) {
            $cta = $cuentaId ?: ($moneda === 'USD' ? $cuentaUsd->id : $cuentaBs->id);
            $fechaPago = $fecha ?: $inv->fecha_emision->copy()->addDays(2)->format('Y-m-d');
            $rpNum = sprintf("RP2026-%05d", $correlativoRp++);
            $referencia = $ref ?: (string) rand(10000000, 99999999);

            $pago = Payment::create([
                'invoice_id' => $inv->id,
                'cuenta_bancaria_id' => $cta,
                'condominio_id' => $condominio->id,
                'numero_recibo_pago' => $rpNum,
                'monto' => $inv->monto_total,
                'moneda_origen' => $moneda,
                'monto_divisa' => $inv->monto_total_usd,
                'tasa_cambio' => $inv->tasa_cambio,
                'fecha_pago' => $fechaPago,
                'metodo_pago' => $metodo,
                'referencia' => $referencia,
                'banco' => $moneda === 'USD' ? 'BNC' : 'Banesco',
                'banco_origen' => $moneda === 'USD' ? 'BNC' : 'Banesco Banco Universal',
                'telefono_origen' => '0414-1112233',
                'cedula_origen' => 'V-12345678',
                'estado' => 'aprobado',
                'observaciones' => "Pago aprobado y conciliado para {$inv->numero_factura} ({$inv->periodo})",
                'registrado_por' => $adminCondo->id,
            ]);

            $pago->invoices()->attach($inv->id, ['monto_aplicado' => $inv->monto_total]);
            $inv->update([
                'monto_pagado' => $inv->monto_total,
                'estado' => 'pagado',
            ]);

            return $pago;
        };

        // --- CASO 1: APTO 1-A (Carlos Mendoza) -> 100% SOLVENTE AL DÍA ---
        // Pagó todos los 5 meses y las 2 cuotas extraordinarias
        foreach ($invoicesMatrix['1-A'] as $per => $inv) {
            $metodo = ($per === '2026-07' || $per === 'EXT-2026-01') ? 'pago_movil' : 'transferencia';
            $registrarPagoAprobado($inv, $metodo);
        }

        // --- CASO 2: APTO 1-B (Elena Morales) -> SOLVENTE + NOTA DE CRÉDITO (SALDO A FAVOR) ---
        // Pagó Mayo, Junio, EXT-01, Julio, Agosto, EXT-02
        $periodosPaga1B = ['2026-05', '2026-06', 'EXT-2026-01', '2026-07', '2026-08', 'EXT-2026-02'];
        foreach ($periodosPaga1B as $pKey) {
            $registrarPagoAprobado($invoicesMatrix['1-B'][$pKey]);
        }
        // En Septiembre: Pagó con excedente de $35.00 USD (Bs 29,137.09) por transferencia
        $invSep1B = $invoicesMatrix['1-B']['2026-09'];
        $excedenteUsd = 35.00;
        $excedenteBs = round($excedenteUsd * $tasaBcv, 2);
        $totalTransferidoBs = round($invSep1B->monto_total + $excedenteBs, 2);
        $totalTransferidoUsd = round($invSep1B->monto_total_usd + $excedenteUsd, 2);

        $pagoExcedente = Payment::create([
            'invoice_id' => $invSep1B->id,
            'cuenta_bancaria_id' => $cuentaBs->id,
            'condominio_id' => $condominio->id,
            'numero_recibo_pago' => sprintf("RP2026-%05d", $correlativoRp++),
            'monto' => $totalTransferidoBs,
            'moneda_origen' => 'VES',
            'monto_divisa' => $totalTransferidoUsd,
            'tasa_cambio' => $tasaBcv,
            'fecha_pago' => '2026-09-04',
            'metodo_pago' => 'transferencia',
            'referencia' => 'TRF-99887711',
            'banco' => 'Banesco',
            'banco_origen' => 'Banesco Banco Universal',
            'telefono_origen' => '0412-3456789',
            'cedula_origen' => 'V-16452109',
            'estado' => 'aprobado',
            'observaciones' => "Pago con excedente de $35.00 USD para generar saldo a favor en cuenta.",
            'registrado_por' => $adminCondo->id,
        ]);

        $pagoExcedente->invoices()->attach($invSep1B->id, ['monto_aplicado' => $invSep1B->monto_total]);
        $invSep1B->update([
            'monto_pagado' => $invSep1B->monto_total,
            'estado' => 'pagado',
        ]);

        // Crear la Nota de Crédito activa
        CreditNote::create([
            'condominio_id' => $condominio->id,
            'apartamento_id' => $apartamentosMap['1-B']['apto']->id,
            'payment_id' => $pagoExcedente->id,
            'numero_nota_credito' => 'NC2026-00001',
            'fecha_emision' => '2026-09-04',
            'monto_original' => $excedenteBs,
            'monto_original_usd' => $excedenteUsd,
            'monto_disponible' => $excedenteBs,
            'monto_disponible_usd' => $excedenteUsd,
            'tasa_cambio' => $tasaBcv,
            'estado' => 'disponible',
            'motivo' => 'Excedente de pago en transferencia bancaria septiembre 2026',
            'observaciones' => 'Saldo a favor disponible para deducir automáticamente en el próximo período.',
        ]);

        // --- CASO 3: APTO 2-A (Andrés Romero) -> PAGO PARCIAL / ABONO ---
        // Pagó Mayo, Junio, EXT-01, Julio, Agosto, EXT-02
        $periodosPaga2A = ['2026-05', '2026-06', 'EXT-2026-01', '2026-07', '2026-08', 'EXT-2026-02'];
        foreach ($periodosPaga2A as $pKey) {
            $registrarPagoAprobado($invoicesMatrix['2-A'][$pKey]);
        }
        // En Septiembre: Hace un abono parcial de $50.00 USD
        $invSep2A = $invoicesMatrix['2-A']['2026-09'];
        $abonoUsd = 50.00;
        $abonoBs = round($abonoUsd * $tasaBcv, 2);

        $pagoParcial = Payment::create([
            'invoice_id' => $invSep2A->id,
            'cuenta_bancaria_id' => $cuentaBs->id,
            'condominio_id' => $condominio->id,
            'numero_recibo_pago' => sprintf("RP2026-%05d", $correlativoRp++),
            'monto' => $abonoBs,
            'moneda_origen' => 'VES',
            'monto_divisa' => $abonoUsd,
            'tasa_cambio' => $tasaBcv,
            'fecha_pago' => '2026-09-05',
            'metodo_pago' => 'pago_movil',
            'referencia' => 'PM-33445566',
            'banco' => 'Banesco',
            'banco_origen' => 'Banco Mercantil',
            'telefono_origen' => '0416-4567890',
            'cedula_origen' => 'V-12984561',
            'estado' => 'aprobado',
            'observaciones' => "Abono parcial de {$abonoUsd} USD al recibo de Septiembre.",
            'registrado_por' => $adminCondo->id,
        ]);

        $pagoParcial->invoices()->attach($invSep2A->id, ['monto_aplicado' => $abonoBs]);
        $invSep2A->update([
            'monto_pagado' => $abonoBs,
            'estado' => 'parcial',
        ]);

        // --- CASO 4: APTO 2-B (Beatriz Salazar) -> PAGO PENDIENTE POR CONCILIAR ---
        // Pagó Mayo, Junio, EXT-01, Julio, Agosto, EXT-02
        $periodosPaga2B = ['2026-05', '2026-06', 'EXT-2026-01', '2026-07', '2026-08', 'EXT-2026-02'];
        foreach ($periodosPaga2B as $pKey) {
            $registrarPagoAprobado($invoicesMatrix['2-B'][$pKey]);
        }
        // En Septiembre: Reportó transferencia bancaria que aún está en revisión (para conciliar)
        $invSep2B = $invoicesMatrix['2-B']['2026-09'];
        Payment::create([
            'invoice_id' => $invSep2B->id,
            'cuenta_bancaria_id' => $cuentaBs->id,
            'condominio_id' => $condominio->id,
            'numero_recibo_pago' => null, // Pendiente de aprobación
            'monto' => $invSep2B->monto_total,
            'moneda_origen' => 'VES',
            'monto_divisa' => $invSep2B->monto_total_usd,
            'tasa_cambio' => $tasaBcv,
            'fecha_pago' => '2026-09-12',
            'metodo_pago' => 'transferencia',
            'referencia' => 'TRF-55443322',
            'banco' => 'Banesco',
            'banco_origen' => 'Banesco Banco Universal',
            'telefono_origen' => '0424-5678901',
            'cedula_origen' => 'V-17892341',
            'estado' => 'pendiente', // Listo para conciliar
            'observaciones' => 'Pago reportado por propietaria vía portal móvil. Pendiente de conciliar con extracto Banesco.',
            'registrado_por' => $apartamentosMap['2-B']['user']->id,
        ]);
        // Factura se mantiene en 'pendiente' hasta que el banco concilie

        // --- CASO 5: APTO 3-A (Roberto Gómez) -> MOROSO LEVE (30 DÍAS) ---
        // Pagó Mayo, Junio, EXT-01, Julio, Agosto, EXT-02
        $periodosPaga3A = ['2026-05', '2026-06', 'EXT-2026-01', '2026-07', '2026-08', 'EXT-2026-02'];
        foreach ($periodosPaga3A as $pKey) {
            $registrarPagoAprobado($invoicesMatrix['3-A'][$pKey]);
        }
        // Debe Septiembre (recibo vencido hace más de 10 días). No ha reportado pago.
        // Factura 'pendiente' sin pagos.

        // --- CASO 6: APTO 3-B (Marcos Vargas) -> MOROSO CRÓNICO GRAVE (+90 DÍAS) ---
        // Solo pagó Mayo y Junio.
        $registrarPagoAprobado($invoicesMatrix['3-B']['2026-05']);
        $registrarPagoAprobado($invoicesMatrix['3-B']['2026-06']);
        // Debe: EXT-2026-01 (Techos), 2026-07 (Julio), 2026-08 (Agosto), EXT-2026-02 (Ascensor) y 2026-09 (Septiembre).
        // 5 facturas en deuda acumulada por más de 90 días.

        // --- CASO 7: APTO 4-A (Daniel Rivas) -> PAGO RECHAZADO (COMPROBANTE FALSO / ERROR) ---
        // Pagó Mayo, Junio, EXT-01, Julio, Agosto, EXT-02
        $periodosPaga4A = ['2026-05', '2026-06', 'EXT-2026-01', '2026-07', '2026-08', 'EXT-2026-02'];
        foreach ($periodosPaga4A as $pKey) {
            $registrarPagoAprobado($invoicesMatrix['4-A'][$pKey]);
        }
        // En Septiembre: Reportó Pago Móvil pero la referencia no existe en el banco
        $invSep4A = $invoicesMatrix['4-A']['2026-09'];
        Payment::create([
            'invoice_id' => $invSep4A->id,
            'cuenta_bancaria_id' => $cuentaBs->id,
            'condominio_id' => $condominio->id,
            'numero_recibo_pago' => null,
            'monto' => $invSep4A->monto_total,
            'moneda_origen' => 'VES',
            'monto_divisa' => $invSep4A->monto_total_usd,
            'tasa_cambio' => $tasaBcv,
            'fecha_pago' => '2026-09-07',
            'metodo_pago' => 'pago_movil',
            'referencia' => 'PM-00099988',
            'banco' => 'Banesco',
            'banco_origen' => 'Banco de Venezuela',
            'telefono_origen' => '0416-8901234',
            'cedula_origen' => 'V-15678901',
            'estado' => 'rechazado',
            'motivo_rechazo' => 'Referencia PM-00099988 no encontrada en los extractos de la cuenta corriente de Banesco. Por favor valide el comprobante con su banco emisor.',
            'observaciones' => 'Rechazado tras verificación con conciliación bancaria.',
            'registrado_por' => $adminCondo->id,
        ]);
        // Factura se mantiene en 'pendiente'

        // --- CASO 8: APTO 4-B (Diana Peña) -> MOROSO MEDIO (60 DÍAS) ---
        // Pagó Mayo, Junio, EXT-01, Julio
        $periodosPaga4B = ['2026-05', '2026-06', 'EXT-2026-01', '2026-07'];
        foreach ($periodosPaga4B as $pKey) {
            $registrarPagoAprobado($invoicesMatrix['4-B'][$pKey]);
        }
        // Debe Agosto, EXT-2026-02 y Septiembre.

        // --- CASO 9: PH-1 (Patricia Colmenares) -> 100% SOLVENTE EN DIVISAS (USD) ---
        // Pagó todos los 5 meses y las 2 cuotas extraordinarias en USD en efectivo / custodia BNC
        foreach ($invoicesMatrix['PH-1'] as $per => $inv) {
            $registrarPagoAprobado($inv, 'efectivo', $cuentaUsd->id, 'USD');
        }

        // --- CASO 10: PH-2 (Fernando Castillo) -> 100% SOLVENTE AL DÍA EN BOLÍVARES ---
        // Pagó todos los 5 meses y las 2 cuotas extraordinarias puntuales por Pago Móvil
        foreach ($invoicesMatrix['PH-2'] as $per => $inv) {
            $registrarPagoAprobado($inv, 'pago_movil', $cuentaBs->id, 'VES');
        }

        // =========================================================================
        // 8. CREACIÓN DE ÁREAS COMUNES Y RESERVAS
        // =========================================================================
        $this->comment('🏊 Configurando áreas comunes y reservas...');

        $areaSalon = CommonArea::create([
            'condominio_id' => $condominio->id,
            'nombre' => 'Salón de Fiestas y Eventos',
            'descripcion' => 'Espacio climatizado con barra, mesas y capacidad para 80 personas.',
            'ubicacion' => 'Piso PB Torre Principal',
            'capacidad_maxima' => 80,
            'requiere_reserva' => true,
            'costo_reserva' => 50.00,
            'horarios_disponibles' => ['10:00 - 16:00', '17:00 - 23:00'],
            'reglas_uso' => ['Música a volumen moderado hasta las 23:00', 'No fumar en áreas cerradas'],
            'activo' => true,
        ]);

        $areaParrillera = CommonArea::create([
            'condominio_id' => $condominio->id,
            'nombre' => 'Área de Parrillera / Quincho',
            'descripcion' => 'Parrillera a gas y carbón con pérgola y mobiliario rústico.',
            'ubicacion' => 'Terraza de Planta Baja',
            'capacidad_maxima' => 25,
            'requiere_reserva' => true,
            'costo_reserva' => 20.00,
            'horarios_disponibles' => ['11:00 - 15:00', '16:00 - 20:00'],
            'reglas_uso' => ['Dejar parrilla limpia y gas cerrado'],
            'activo' => true,
        ]);

        $areaGym = CommonArea::create([
            'condominio_id' => $condominio->id,
            'nombre' => 'Gimnasio y Cancha Multiuso',
            'descripcion' => 'Equipamiento cardiovascular y de pesas libre para copropietarios solventes.',
            'ubicacion' => 'Mezzanina',
            'capacidad_maxima' => 15,
            'requiere_reserva' => false,
            'costo_reserva' => 0.00,
            'horarios_disponibles' => ['06:00 - 22:00'],
            'reglas_uso' => ['Uso obligatorio de toalla y ropa deportiva'],
            'activo' => true,
        ]);

        // 1 Reserva pasada completada (Apto 1-A)
        Reservation::create([
            'common_area_id' => $areaSalon->id,
            'user_id' => $apartamentosMap['1-A']['user']->id,
            'apartamento_id' => $apartamentosMap['1-A']['apto']->id,
            'condominio_id' => $condominio->id,
            'fecha_reserva' => '2026-05-20',
            'hora_inicio' => '17:00',
            'hora_fin' => '23:00',
            'estado' => 'aprobada',
            'motivo' => 'Cumpleaños familiar',
            'numero_personas' => 45,
            'costo_total' => 50.00,
            'pago_realizado' => true,
            'notas' => 'Evento concluido satisfactoriamente sin incidencias.',
        ]);

        // 1 Reserva aprobada próxima (Apto 1-B)
        Reservation::create([
            'common_area_id' => $areaParrillera->id,
            'user_id' => $apartamentosMap['1-B']['user']->id,
            'apartamento_id' => $apartamentosMap['1-B']['apto']->id,
            'condominio_id' => $condominio->id,
            'fecha_reserva' => Carbon::now()->addDays(5)->format('Y-m-d'),
            'hora_inicio' => '12:00',
            'hora_fin' => '16:00',
            'estado' => 'aprobada',
            'motivo' => 'Almuerzo familiar domingo',
            'numero_personas' => 15,
            'costo_total' => 20.00,
            'pago_realizado' => true,
            'notas' => 'Pago verificado y canon transferido.',
        ]);

        // 1 Reserva pendiente de aprobación (Apto 2-A)
        Reservation::create([
            'common_area_id' => $areaSalon->id,
            'user_id' => $apartamentosMap['2-A']['user']->id,
            'apartamento_id' => $apartamentosMap['2-A']['apto']->id,
            'condominio_id' => $condominio->id,
            'fecha_reserva' => Carbon::now()->addDays(12)->format('Y-m-d'),
            'hora_inicio' => '18:00',
            'hora_fin' => '23:00',
            'estado' => 'pendiente',
            'motivo' => 'Reunión de aniversario',
            'numero_personas' => 50,
            'costo_total' => 50.00,
            'pago_realizado' => false,
            'notas' => 'Esperando comprobante de depósito de garantía.',
        ]);

        // =========================================================================
        // 9. CREACIÓN DE ASAMBLEAS Y VOTACIONES POR ALÍCUOTA PONDERADA
        // =========================================================================
        $this->comment('🗳️ Creando asambleas de copropietarios con votaciones por alícuota ponderada...');

        // Asamblea 1: Finalizada con Quórum aprobado del 89.75%
        $poll1 = Poll::create([
            'condominio_id' => $condominio->id,
            'titulo' => 'Aprobación de Proyecto y Cuota Extraordinaria de Impermeabilización de Azotea',
            'descripcion' => 'Votación estatutaria para autorizar la contratación de la empresa de impermeabilización y la emisión de la cuota extraordinaria EXT-2026-01 por $800.00 USD.',
            'fecha_inicio' => '2026-06-05 08:00:00',
            'fecha_fin' => '2026-06-12 20:00:00',
            'estado' => 'finalizada',
            'permitir_multiple' => false,
            'creado_por' => $adminCondo->id,
        ]);

        $opcion1A = PollOption::create(['poll_id' => $poll1->id, 'texto' => 'Aprobar Proyecto de Impermeabilización y Cuota Extraordinaria', 'orden' => 1]);
        $opcion1B = PollOption::create(['poll_id' => $poll1->id, 'texto' => 'Rechazar Proyecto y Mantener Presupuesto Ordinario', 'orden' => 2]);

        // Votantes de la Asamblea 1 (Total Quórum: 89.75%)
        // Votaron Sí (Aprobado): 1-A (8.5%), 1-B (8.5%), 2-A (9.5%), 2-B (9.5%), 3-A (10.0%), 4-A (11.0%), PH-1 (11.25%), PH-2 (11.25%) = 79.50%
        // Votaron No: 4-B (11.0%) = 11.00%
        $siAptos = ['1-A', '1-B', '2-A', '2-B', '3-A', '4-A', 'PH-1', 'PH-2'];
        foreach ($siAptos as $sApto) {
            Vote::create([
                'poll_id' => $poll1->id,
                'poll_option_id' => $opcion1A->id,
                'user_id' => $apartamentosMap[$sApto]['user']->id,
                'apartamento_id' => $apartamentosMap[$sApto]['apto']->id,
                'condominio_id' => $condominio->id,
            ]);
        }
        Vote::create([
            'poll_id' => $poll1->id,
            'poll_option_id' => $opcion1B->id,
            'user_id' => $apartamentosMap['4-B']['user']->id,
            'apartamento_id' => $apartamentosMap['4-B']['apto']->id,
            'condominio_id' => $condominio->id,
        ]);

        // Asamblea 2: Activa en Curso (Elección de Junta de Condominio 2026-2027)
        $poll2 = Poll::create([
            'condominio_id' => $condominio->id,
            'titulo' => 'Elección de la Junta de Condominio Período 2026-2027',
            'descripcion' => 'Elección democrática de la nueva junta directiva (Presidente, Tesorero, Secretario y Vocales) para el período estatutario anual.',
            'fecha_inicio' => '2026-09-08 08:00:00',
            'fecha_fin' => Carbon::now()->addDays(7)->format('Y-m-d H:i:s'),
            'estado' => 'activa',
            'permitir_multiple' => false,
            'creado_por' => $adminCondo->id,
        ]);

        $opcion2A = PollOption::create(['poll_id' => $poll2->id, 'texto' => 'Plancha 1: Excelencia y Transparencia (Liderada por Ing. Carlos Mendoza)', 'orden' => 1]);
        $opcion2B = PollOption::create(['poll_id' => $poll2->id, 'texto' => 'Plancha 2: Continuidad Operativa (Liderada por Dra. Patricia Colmenares)', 'orden' => 2]);
        $opcion2C = PollOption::create(['poll_id' => $poll2->id, 'texto' => 'Abstención / Voto en Blanco', 'orden' => 3]);

        // Votos emitidos hasta hoy: Apto 1-A vota Plancha 1, PH-1 vota Plancha 2, Apto 2-A vota Plancha 1
        // Quórum actual: 8.5% + 11.25% + 9.5% = 29.25%
        Vote::create([
            'poll_id' => $poll2->id,
            'poll_option_id' => $opcion2A->id,
            'user_id' => $apartamentosMap['1-A']['user']->id,
            'apartamento_id' => $apartamentosMap['1-A']['apto']->id,
            'condominio_id' => $condominio->id,
        ]);
        Vote::create([
            'poll_id' => $poll2->id,
            'poll_option_id' => $opcion2B->id,
            'user_id' => $apartamentosMap['PH-1']['user']->id,
            'apartamento_id' => $apartamentosMap['PH-1']['apto']->id,
            'condominio_id' => $condominio->id,
        ]);
        Vote::create([
            'poll_id' => $poll2->id,
            'poll_option_id' => $opcion2A->id,
            'user_id' => $apartamentosMap['2-A']['user']->id,
            'apartamento_id' => $apartamentosMap['2-A']['apto']->id,
            'condominio_id' => $condominio->id,
        ]);

        // =========================================================================
        // 10. INCIDENCIAS, COMUNICADOS Y NOTIFICACIONES
        // =========================================================================
        $this->comment('📢 Registrando incidencias, comunicados de junta y cobranza preventiva...');

        // Comunicados
        Comunicado::create([
            'condominio_id' => $condominio->id,
            'titulo' => 'Culminación Exitosa de Modernización de Ascensor Torre A',
            'contenido' => 'Estimados copropietarios: Nos complace informar que los trabajos de sustitución del variador de frecuencia y tablero de control del ascensor han concluido con éxito. El ascensor se encuentra 100% operativo.',
            'fecha_publicacion' => '2026-09-02',
            'fecha_expiracion' => '2026-09-30',
            'enviar_email' => true,
            'enviar_whatsapp' => false,
        ]);

        Comunicado::create([
            'condominio_id' => $condominio->id,
            'titulo' => 'Normas de Convivencia para el Uso de la Parrillera y Salón de Fiestas',
            'contenido' => 'Recordamos a todos los residentes la importancia de respetar los horarios de música (hasta las 23:00 hrs) y hacer uso adecuado de las papeleras y áreas verdes.',
            'fecha_publicacion' => '2026-08-20',
            'fecha_expiracion' => '2026-10-31',
            'enviar_email' => true,
            'enviar_whatsapp' => true,
        ]);

        // Incidencias
        Incidencia::create([
            'condominio_id' => $condominio->id,
            'apartamento_id' => $apartamentosMap['1-B']['apto']->id,
            'user_id' => $apartamentosMap['1-B']['user']->id,
            'titulo' => 'Baja presión de agua en tubería matriz piso 1',
            'descripcion' => 'Se reportó baja presión en horas pico de la mañana en los apartamentos del piso 1.',
            'prioridad' => 'media',
            'responsable_nombre' => 'Técnico de Bombas Hidroneumáticas',
            'fecha_solucion' => '2026-08-18',
            'estado' => 'resuelto',
        ]);

        Incidencia::create([
            'condominio_id' => $condominio->id,
            'apartamento_id' => $apartamentosMap['3-A']['apto']->id,
            'user_id' => $apartamentosMap['3-A']['user']->id,
            'titulo' => 'Lámpara LED intermitente en pasillo frente al ascensor piso 3',
            'descripcion' => 'La lámpara titila en las noches. Se solicita reemplazo del balastro o foco.',
            'prioridad' => 'baja',
            'responsable_nombre' => 'Conserjería / Electricista',
            'fecha_solucion' => null,
            'estado' => 'en_proceso',
        ]);

        // Historial de cobranza preventiva automática
        NotificacionHistorial::create([
            'condominio_id' => $condominio->id,
            'user_id' => $apartamentosMap['3-A']['user']->id,
            'tipo' => 'email',
            'plantilla' => 'recordatorio_pago',
            'destinatario' => $apartamentosMap['3-A']['user']->email,
            'mensaje' => "Estimado(a) Roberto Gómez, le recordamos cordialmente que hoy vence el recibo de Septiembre 2026 por Bs. " . number_format($invoicesMatrix['3-A']['2026-09']->monto_total, 2, ',', '.') . ". Cuenta Banesco 0134-0012-34-1234567890.",
            'estado' => 'enviado',
            'created_at' => '2026-09-06 08:00:00',
        ]);

        NotificacionHistorial::create([
            'condominio_id' => $condominio->id,
            'user_id' => $apartamentosMap['3-B']['user']->id,
            'tipo' => 'email',
            'plantilla' => 'recordatorio_pago',
            'destinatario' => $apartamentosMap['3-B']['user']->email,
            'mensaje' => "Estimado(a) Marcos Vargas, le informamos que presenta cuotas acumuladas vencidas por más de 60 días. Por favor comuníquese con la administración para formalizar convenio de pago.",
            'estado' => 'enviado',
            'created_at' => '2026-09-10 08:00:00',
        ]);

        // =========================================================================
        // 11. REPORTE FINAL Y TABLA RESUMEN EN CONSOLA
        // =========================================================================
        $this->output->success('🎉 SIMULACIÓN COMPLETADA CON ÉXITO.');

        $this->table(
            ['Parámetro', 'Valor Registrado'],
            [
                ['Condominio Nuevo', 'Residencias Parque Cristal (RIF: J-40129384-5)'],
                ['Tasa Oficial BCV Aplicada', "Bs. " . number_format($tasaBcv, 4, ',', '.') . " ({$fuenteBcv})"],
                ['Total Apartamentos', '10 apartamentos (Suma de alícuotas = 100.0000%)'],
                ['Períodos Emitidos', '5 meses ordinarios (Mayo a Sep 2026) + 2 cuotas extraordinarias'],
                ['Total Recibos Creados', '70 facturas individuales certificadas'],
                ['Fondo de Reserva Acumulado', '$3,500.00 USD (Bs. ' . number_format(3500.00 * $tasaBcv, 2, ',', '.') . ')'],
                ['Cuentas Bancarias', 'Banesco (Bs) con Pago Móvil + BNC (USD) Custodia'],
                ['Asambleas Ponderadas', '1 Finalizada (Quórum 89.75%) | 1 Activa en Curso (Quórum 29.25%)'],
                ['Áreas Comunes / Reservas', '3 Áreas Comunes | 3 Reservas (Completada, Aprobada, Pendiente)'],
            ]
        );

        $this->info('👥 Credenciales de Acceso para Pruebas:');
        $this->table(
            ['Rol', 'Usuario', 'Contraseña', 'Apartamento', 'Caso de Prueba'],
            [
                ['Super Admin', 'master@condominio.com', '123456', 'Plataforma SaaS', 'Control Total Global'],
                ['Administradora', 'admin@condominio.com', '123456', 'Administración', 'Gestión Contable y Bancaria'],
                ['Propietario', 'carlos.mendoza@gmail.com', '123456', '1-A (8.5%)', '100% Solvente (Todos los meses pagados)'],
                ['Propietaria', 'elena.morales@gmail.com', '123456', '1-B (8.5%)', 'Solvente + Nota de Crédito de $35.00 activa'],
                ['Propietario', 'andres.romero@gmail.com', '123456', '2-A (9.5%)', 'Abono Parcial ($50.00 pagados, saldo pendiente)'],
                ['Propietaria', 'beatriz.salazar@gmail.com', '123456', '2-B (9.5%)', 'Pago Pendiente de Conciliar en Banesco'],
                ['Propietario', 'roberto.gomez@gmail.com', '123456', '3-A (10.0%)', 'Moroso Leve (Debe recibo de Septiembre)'],
                ['Propietario', 'marcos.vargas@gmail.com', '123456', '3-B (10.0%)', 'Moroso Crónico Grave (+90 días de deuda)'],
                ['Propietario', 'daniel.rivas@gmail.com', '123456', '4-A (11.0%)', 'Pago Rechazado con Motivo Explicativo'],
                ['Propietaria', 'diana.pena@gmail.com', '123456', '4-B (11.0%)', 'Moroso Medio (60 días de deuda)'],
                ['Propietaria', 'patricia.colmenares@gmail.com', '123456', 'PH-1 (11.0%)', '100% Solvente en Divisas USD (BNC)'],
                ['Propietario', 'fernando.castillo@gmail.com', '123456', 'PH-2 (11.0%)', '100% Solvente en Bolívares (Pago Móvil)'],
            ]
        );

        return 0;
    }
}
