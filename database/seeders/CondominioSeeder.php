<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use App\Models\Menu;
use App\Models\SelectOption;
use App\Models\Plan;
use App\Models\Condominio;
use App\Models\User;
use App\Models\Apartamento;
use App\Models\Propietario;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\CommonArea;
use App\Models\Reservation;
use App\Models\Visitante;
use App\Models\Incidencia;
use App\Models\Comunicado;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class CondominioSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // 1. Roles del Sistema
        // ==========================================
        $roleMaster = Role::updateOrCreate(['slug' => 'super-admin'], [
            'nombre' => 'Super Admin',
            'descripcion' => 'Dueño del sistema con acceso total.',
            'status' => true,
        ]);

        $roleAdmin = Role::updateOrCreate(['slug' => 'admin-condominio'], [
            'nombre' => 'Admin de Condominio',
            'descripcion' => 'Administrador de uno o varios condominios.',
            'status' => true,
        ]);

        $roleSupervisor = Role::updateOrCreate(['slug' => 'supervisor'], [
            'nombre' => 'Supervisor',
            'descripcion' => 'Verifica pagos y gestiona cobros a morosos.',
            'status' => true,
        ]);

        $roleAnalista = Role::updateOrCreate(['slug' => 'analista'], [
            'nombre' => 'Analista del Sistema',
            'descripcion' => 'Visualiza sección de deudores y morosidad.',
            'status' => true,
        ]);

        $rolePropietario = Role::updateOrCreate(['slug' => 'propietario'], [
            'nombre' => 'Propietario/Residente',
            'descripcion' => 'Residente y copropietario de apartamento.',
            'status' => true,
        ]);

        // ==========================================
        // 2. Permisos Estándar por Módulo
        // ==========================================
        $modulos = [
            'Usuarios' => ['usuarios.crear', 'usuarios.consultar', 'usuarios.modificar', 'usuarios.listar', 'usuarios.eliminar'],
            'Condominios' => ['condominios.crear', 'condominios.consultar', 'condominios.modificar', 'condominios.listar', 'condominios.eliminar'],
            'Apartamentos' => ['apartamentos.crear', 'apartamentos.consultar', 'apartamentos.modificar', 'apartamentos.listar', 'apartamentos.eliminar'],
            'Propietarios' => ['propietarios.crear', 'propietarios.consultar', 'propietarios.modificar', 'propietarios.listar', 'propietarios.eliminar'],
            'Contabilidad' => ['contabilidad.ver', 'contabilidad.modificar', 'gastos.crear', 'gastos.listar'],
            'Pagos' => ['pagos.crear', 'pagos.consultar', 'pagos.modificar', 'pagos.listar', 'pagos.aprobar'],
            'Reservas' => ['reservas.crear', 'reservas.consultar', 'reservas.modificar', 'reservas.listar', 'reservas.aprobar'],
            'Visitantes' => ['visitantes.crear', 'visitantes.consultar', 'visitantes.listar'],
            'Incidencias' => ['incidencias.crear', 'incidencias.consultar', 'incidencias.modificar', 'incidencias.listar'],
            'Comunicados' => ['comunicados.crear', 'comunicados.consultar', 'comunicados.listar'],
        ];

        $allPermissionIds = [];
        foreach ($modulos as $modulo => $perms) {
            foreach ($perms as $pNombre) {
                $p = Permission::updateOrCreate(['nombre' => $pNombre], [
                    'descripcion' => "Permiso para {$pNombre}",
                    'modulo' => $modulo,
                ]);
                $allPermissionIds[] = $p->id;
            }
        }

        // Asignar todos los permisos al Super Admin
        $roleMaster->permissions()->sync($allPermissionIds);

        // ==========================================
        // 3. Menús Dinámicos
        // ==========================================
        $menus = [
            ['nombre' => 'Dashboard Global', 'icono' => 'mdi-view-dashboard', 'ruta' => '/dashboard/master', 'orden' => 1],
            ['nombre' => 'Condominios', 'icono' => 'mdi-office-building', 'ruta' => '/condominios', 'orden' => 2],
            ['nombre' => 'Planes y Suscripciones', 'icono' => 'mdi-package-variant-closed', 'ruta' => '/suscripciones/planes', 'orden' => 3],
            ['nombre' => 'Cobranza SaaS', 'icono' => 'mdi-cash-register', 'ruta' => '/contabilidad/super-admin', 'orden' => 4],
            ['nombre' => 'Campos Select', 'icono' => 'mdi-tune', 'ruta' => '/configuracion/select-options', 'orden' => 5],
            ['nombre' => 'Roles y Permisos', 'icono' => 'mdi-shield-account', 'ruta' => '/seguridad/roles', 'orden' => 6],
            ['nombre' => 'Menús Dinámicos', 'icono' => 'mdi-menu', 'ruta' => '/seguridad/menus', 'orden' => 7],
            ['nombre' => 'Auditoría', 'icono' => 'mdi-history', 'ruta' => '/auditoria', 'orden' => 8],
        ];

        foreach ($menus as $m) {
            Menu::updateOrCreate(['ruta' => $m['ruta']], $m);
        }

        // ==========================================
        // 4. Catálogos Select Dinámicos
        // ==========================================
        $options = [
            ['tipo' => 'bancos', 'valor' => 'banesco', 'etiqueta' => 'Banesco Banco Universal'],
            ['tipo' => 'bancos', 'valor' => 'venezuela', 'etiqueta' => 'Banco de Venezuela'],
            ['tipo' => 'bancos', 'valor' => 'mercantil', 'etiqueta' => 'Banco Mercantil'],
            ['tipo' => 'bancos', 'valor' => 'provincial', 'etiqueta' => 'BBVA Provincial'],
            ['tipo' => 'bancos', 'valor' => 'bnc', 'etiqueta' => 'Banco Nacional de Crédito (BNC)'],
            ['tipo' => 'status_usuario', 'valor' => 'activo', 'etiqueta' => 'Activo'],
            ['tipo' => 'status_usuario', 'valor' => 'inactivo', 'etiqueta' => 'Inactivo'],
            ['tipo' => 'status_usuario', 'valor' => 'bloqueado', 'etiqueta' => 'Bloqueado Temporalmente'],
            ['tipo' => 'tipos_area_comun', 'valor' => 'piscina', 'etiqueta' => 'Piscina'],
            ['tipo' => 'tipos_area_comun', 'valor' => 'salon_fiestas', 'etiqueta' => 'Salón de Fiestas'],
            ['tipo' => 'tipos_area_comun', 'valor' => 'quincho', 'etiqueta' => 'Quincho / Parrillera'],
            ['tipo' => 'tipos_area_comun', 'valor' => 'gimnasio', 'etiqueta' => 'Gimnasio'],
            ['tipo' => 'metodos_pago', 'valor' => 'transferencia', 'etiqueta' => 'Transferencia Bancaria'],
            ['tipo' => 'metodos_pago', 'valor' => 'pago_movil', 'etiqueta' => 'Pago Móvil'],
            ['tipo' => 'metodos_pago', 'valor' => 'efectivo', 'etiqueta' => 'Efectivo Divisas / Bs.'],
            ['tipo' => 'categorias_gasto', 'valor' => 'servicios', 'etiqueta' => 'Servicios Públicos (Agua/Luz)'],
            ['tipo' => 'categorias_gasto', 'valor' => 'mantenimiento', 'etiqueta' => 'Mantenimiento de Ascensores y Bombas'],
            ['tipo' => 'categorias_gasto', 'valor' => 'sueldos', 'etiqueta' => 'Sueldos de Conserjería y Vigilancia'],
        ];

        foreach ($options as $opt) {
            SelectOption::updateOrCreate(['tipo' => $opt['tipo'], 'valor' => $opt['valor']], [
                'etiqueta' => $opt['etiqueta'],
                'activo' => true,
            ]);
        }

        // ==========================================
        // 5. Planes SaaS
        // ==========================================
        $planPremium = Plan::where('nombre', 'like', '%Profesional%')->first() ?: Plan::first();

        // ==========================================
        // 6. Super Admin User
        // ==========================================
        $master = User::updateOrCreate(['email' => 'master@condominio.com'], [
            'name' => 'Super Admin',
            'password' => Hash::make('123456'),
            'rol' => 'Super Admin',
            'role_id' => $roleMaster->id,
            'telefono' => '+584141234567',
            'cedula' => 'V-12345678',
            'activo' => true,
        ]);

        // ==========================================
        // 7. Condominios de Demostración
        // ==========================================
        $condominiosData = [
            ['nombre' => 'Residencias El Parque', 'direccion' => 'Av. Principal, Caracas', 'rif' => 'J-123456789'],
            ['nombre' => 'Conjunto Residencial La Florida', 'direccion' => 'Calle 5, Maracaibo', 'rif' => 'J-987654321'],
        ];

        foreach ($condominiosData as $idx => $cData) {
            $condominio = Condominio::updateOrCreate(['rif' => $cData['rif']], [
                'nombre' => $cData['nombre'],
                'direccion' => $cData['direccion'],
                'telefono' => '+58212' . rand(1000000, 9999999),
                'email' => 'contacto@' . strtolower(str_replace(' ', '', $cData['nombre'])) . '.com',
                'numero_apartamentos' => 15,
                'cuota_mantenimiento_base' => 45000,
                'porcentaje_mora' => 5.00,
                'dias_gracia' => 5,
                'moneda_base' => 'VES',
                'tasa_cambio' => 36.50,
                'plan_id' => $planPremium->id,
                'plan_suscripcion' => 'premium',
                'fecha_vencimiento_suscripcion' => Carbon::now()->addYear(),
                'estado_suscripcion' => 'activo',
                'activo' => true,
            ]);

            // Crear Administrador de Condominio
            $admin = User::updateOrCreate(['email' => 'admin' . ($idx + 1) . '@condominio.com'], [
                'name' => 'Admin ' . ($idx + 1),
                'password' => Hash::make('123456'),
                'rol' => 'Admin de Condominio',
                'role_id' => $roleAdmin->id,
                'condominio_id' => $condominio->id,
                'telefono' => '+58424' . rand(1000000, 9999999),
                'cedula' => 'V-' . rand(10000000, 29999999),
                'activo' => true,
            ]);

            // Crear Supervisor y Analista para el primer condominio
            if ($idx === 0) {
                User::updateOrCreate(['email' => 'supervisor@condominio.com'], [
                    'name' => 'Supervisor de Pagos',
                    'password' => Hash::make('123456'),
                    'rol' => 'Supervisor',
                    'role_id' => $roleSupervisor->id,
                    'condominio_id' => $condominio->id,
                    'telefono' => '+584149876543',
                    'cedula' => 'V-19876543',
                    'activo' => true,
                ]);

                User::updateOrCreate(['email' => 'analista@condominio.com'], [
                    'name' => 'Analista de Cartera',
                    'password' => Hash::make('123456'),
                    'rol' => 'Analista del Sistema',
                    'role_id' => $roleAnalista->id,
                    'condominio_id' => $condominio->id,
                    'telefono' => '+584167654321',
                    'cedula' => 'V-21654321',
                    'activo' => true,
                ]);
            }

            // Crear Apartamentos y Propietarios
            for ($piso = 1; $piso <= 3; $piso++) {
                for ($ap = 1; $ap <= 5; $ap++) {
                    $numeroApto = $piso . '0' . $ap;
                    $apto = Apartamento::updateOrCreate([
                        'condominio_id' => $condominio->id,
                        'numero' => $numeroApto,
                    ], [
                        'piso' => (string) $piso,
                        'metros_cuadrados' => rand(70, 110),
                        'habitaciones' => rand(2, 3),
                        'banos' => rand(1, 2),
                        'ocupado' => true,
                        'descripcion' => 'Apartamento Residencial',
                    ]);

                    // Propietario demo para owner1@condominio.com
                    if ($piso === 1 && $ap === 1 && $idx === 0) {
                        $ownerUser = User::updateOrCreate(['email' => 'owner1@condominio.com'], [
                            'name' => 'Carlos Propietario',
                            'password' => Hash::make('123456'),
                            'rol' => 'Propietario/Residente',
                            'role_id' => $rolePropietario->id,
                            'condominio_id' => $condominio->id,
                            'apartamento_id' => $apto->id,
                            'telefono' => '+584121112233',
                            'cedula' => 'V-15678901',
                            'activo' => true,
                        ]);

                        Propietario::updateOrCreate(['apartamento_id' => $apto->id], [
                            'user_id' => $ownerUser->id,
                            'condominio_id' => $condominio->id,
                            'nombre_completo' => 'Carlos Propietario',
                            'cedula' => 'V-15678901',
                            'telefono' => '+584121112233',
                            'email' => 'owner1@condominio.com',
                            'es_propietario_principal' => true,
                            'fecha_inicio' => Carbon::now()->subYear(),
                            'activo' => true,
                        ]);

                        // Factura emitida
                        $inv = Invoice::updateOrCreate([
                            'numero_factura' => 'FAC-' . $condominio->id . '-' . $apto->id . '-202608',
                        ], [
                            'apartamento_id' => $apto->id,
                            'condominio_id' => $condominio->id,
                            'fecha_emision' => Carbon::now()->startOfMonth(),
                            'fecha_vencimiento' => Carbon::now()->addDays(15),
                            'monto_total' => 45000,
                            'monto_pagado' => 45000,
                            'estado' => 'pagado',
                            'periodo' => '202608',
                            'descripcion' => 'Cuota de Mantenimiento Agosto 2026',
                        ]);

                        Payment::create([
                            'invoice_id' => $inv->id,
                            'condominio_id' => $condominio->id,
                            'monto' => 45000,
                            'tasa_cambio' => 36.50,
                            'fecha_pago' => Carbon::now()->subDays(2),
                            'metodo_pago' => 'pago_movil',
                            'referencia' => 'PM-987654321',
                            'banco' => 'Banesco',
                            'estado' => 'aprobado',
                            'registrado_por' => $ownerUser->id,
                        ]);
                    }
                }
            }

            // Crear Conceptos de Gastos Configurables y Dinámicos del Condominio
            $conceptosDemo = [
                ['ali' => '1', 'concepto' => '01 PRESTACIONES SOCIALES', 'monto_base' => 10.00],
                ['ali' => '1', 'concepto' => '02 SUELDOS Y SALARIOS TRABAJADORAS RESIDENCIALES', 'monto_base' => 0.50],
                ['ali' => '1', 'concepto' => '03 LEY POLÍTICA HABITACIONAL', 'monto_base' => 0.11],
                ['ali' => '1', 'concepto' => '04 IVSS', 'monto_base' => 0.10],
                ['ali' => '1', 'concepto' => '05 BONO LEY ALIMENTACION', 'monto_base' => 40.00],
                ['ali' => '1', 'concepto' => '06 BONO COMPLEMENTARIO', 'monto_base' => 55.00],
                ['ali' => '1', 'concepto' => '09 BONO COMPLEMENTARIO INTEGRAL INDEXADO', 'monto_base' => 145.00],
                ['ali' => '1', 'concepto' => 'ADQUISICIÓN Sistema de Cámaras, tuberías, fuente de poder, cableado 5 de 8', 'monto_base' => 165.32],
                ['ali' => '1', 'concepto' => 'CORPOELEC Jul-2026', 'monto_base' => 42.82],
                ['ali' => '1', 'concepto' => 'GASTOS ADMINISTRATIVOS SISTEMA AUTOMATIZADO ISAC junio y julio', 'monto_base' => 50.56],
                ['ali' => '1', 'concepto' => 'GASTOS ADMINISTRATIVOS Honorarios', 'monto_base' => 170.00],
                ['ali' => '1', 'concepto' => 'GASTOS BANCARIOS', 'monto_base' => 8.00],
                ['ali' => '1', 'concepto' => 'HIDROCAPITAL', 'monto_base' => 73.58],
                ['ali' => '1', 'concepto' => 'MANO DE OBRA Albañileria quitar frizo viejo y colocar el nuevo pared estacionamiento', 'monto_base' => 116.67],
                ['ali' => '1', 'concepto' => 'MANTENIMIENTO DE JARDINES Y PODA DE ARBOLES', 'monto_base' => 140.00],
                ['ali' => '1', 'concepto' => 'MATERIALES Y ARTÍCULOS DE LIMPIEZA y Bolsas negras 1 de 2', 'monto_base' => 73.53],
                ['ali' => '1', 'concepto' => 'SERVICIO COPIA DEL PLANO ESTRUCTURAL DEL EDIFICIO', 'monto_base' => 11.00],
                ['ali' => '1', 'concepto' => 'SERVICIO RECOLECCION DE DESECHO Y ESCOMBROS', 'monto_base' => 20.00],
                ['ali' => '1', 'concepto' => 'SERVICIO DE TELÉFONO Jul-2026', 'monto_base' => 9.54],
                ['ali' => '1', 'concepto' => 'Z. COMISION X CRED. INMDTO', 'monto_base' => 0.00],
                ['ali' => '1', 'concepto' => 'Z. PUESTA DE AGUA', 'monto_base' => 0.00],
                ['ali' => '3', 'concepto' => 'ADQUISICIÓN E INSTALACION Contactor 110V alta velocidad ascensor par 1 de 3', 'monto_base' => 166.67],
                ['ali' => '3', 'concepto' => 'MANTENIMIENTO DE ASCENSORES Jul-2026', 'monto_base' => 80.00],
            ];

            foreach ($conceptosDemo as $cDemo) {
                \App\Models\CondominioConcepto::updateOrCreate([
                    'condominio_id' => $condominio->id,
                    'concepto' => $cDemo['concepto'],
                ], [
                    'ali' => $cDemo['ali'],
                    'monto_base' => $cDemo['monto_base'],
                    'tipo' => 'fijo',
                    'categoria' => 'mantenimiento',
                    'vigente_desde' => Carbon::now()->startOfMonth(),
                    'activo' => true,
                ]);
            }

            // Crear Áreas Comunes
            CommonArea::updateOrCreate(['condominio_id' => $condominio->id, 'nombre' => 'Piscina y Solárium'], [
                'descripcion' => 'Piscina para adultos y niños',
                'capacidad_maxima' => 25,
                'requiere_reserva' => true,
                'costo_reserva' => 30000,
                'activo' => true,
            ]);

            CommonArea::updateOrCreate(['condominio_id' => $condominio->id, 'nombre' => 'Salón de Eventos'], [
                'descripcion' => 'Salón para reuniones sociales',
                'capacidad_maxima' => 50,
                'requiere_reserva' => true,
                'costo_reserva' => 80000,
                'activo' => true,
            ]);

            // Crear Gastos de Demostración
            Expense::create([
                'condominio_id' => $condominio->id,
                'descripcion' => 'Servicio eléctrico áreas comunes y bombas',
                'monto_bs' => 65000,
                'monto_usd' => 65000 / 36.50,
                'tasa_cambio' => 36.50,
                'categoria' => 'servicios',
                'fecha_gasto' => Carbon::now()->subDays(5),
                'estado_pago' => 'pagado',
                'proveedor' => 'Corpoelec',
                'referencia_pago' => 'REF-889911',
                'registrado_por' => $admin->id,
            ]);
        }
    }
}