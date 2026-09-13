<?php

namespace Database\Seeders;

use App\Models\Condominio;
use App\Models\User;
use App\Models\Role;
use App\Models\Apartamento;
use App\Models\Propietario;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\CondominioConcepto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AlpakoRealDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Rol de Propietario y Admin
        $rolePropietario = Role::firstOrCreate(['slug' => 'propietario'], [
            'nombre' => 'Propietario/Residente',
            'descripcion' => 'Residente y copropietario de apartamento.',
            'status' => true,
        ]);

        $roleAdmin = Role::firstOrCreate(['slug' => 'admin-condominio'], [
            'nombre' => 'Admin de Condominio',
            'descripcion' => 'Administrador de condominio.',
            'status' => true,
        ]);

        // 2. Condominio Residencias Alpako con todos los datos fiscales y bancarios del recibo
        $condominio = Condominio::updateOrCreate([
            'rif' => 'J-300576531',
        ], [
            'nombre' => 'CONDOMINIO RESIDENCIAS ALPAKO',
            'direccion' => 'AV. ROMULO GALLEGOS CON AV. SANZ EDIFICIO ALPAKO PISO PB APT PB URB. EL MARQUEZ CARACAS (PETARE) EDO MIRANDA',
            'telefono' => '0412-377-95-75',
            'email' => 'edificioalpako2015@yahoo.com',
            'numero_apartamentos' => 30,
            'cuota_mantenimiento_base' => 38339.45,
            'porcentaje_mora' => 5.00,
            'dias_gracia' => 5,
            'moneda_base' => 'VES',
            'tasa_cambio' => 746.63,
            'banco_nombre' => 'BNC',
            'cuenta_bancaria_bs' => '0191 0514 8221 0001 8351',
            'cuenta_bancaria_usd' => '0191 0012 0223 1202 5152',
            'fondo_reserva_porcentaje' => 10.00,
            'fondo_reserva_acumulado' => 2997.06,
            'notas_recibo' => 'VENCIMIENTO A LOS 5 DÍAS DE SU EMISION. APLICA TASA DE CAMBIO BCV A LA FECHA DE PAGO DE CONFORMIDAD CON LO DISPUESTO EN EL ART. 8 Y ART 128 CONVENIO CAMBIARIO. TRANSFERENCIA EN BS CTA. CTE. BNC 0191 0514 8221 0001 8351 A NOMBRE RESIDENCIAS ALPAKO' . "\n" .
                'Se notifica que a partir del presente mes todo propietario o inquilino del Edificio Residencias Alpako que se encuentre insolvente con al menos tres (3) meses de condominio será pasado automáticamente al Departamento Legal a los fines legales consiguientes.' . "\n" .
                'NOTAS: POR FAVOR NOTIFIQUE EL PAGO A TRAVÉS DEL SISTEMA CON SU CLAVE PERSONAL O ENVIE SOPORTE AL CORREO EDIFICIOALPAKO2015@YAHOO.COM !GRACIAS!, DEPÓSITOS DE DOLARES EN EFECTIVO EN EL BNC 0191 0012 0223 1202 5152 A NOMBRE DE CONDOMINIO RESIDENCIAS ALPAKO , DOLARES EN EFECTIVO AVISAR AL 0412-377-95-75',
            'plan_suscripcion' => 'premium',
            'fecha_vencimiento_suscripcion' => Carbon::now()->addYear(),
            'estado_suscripcion' => 'activo',
            'activo' => true,
        ]);

        // Administrador para este condominio
        User::updateOrCreate(['email' => 'admin.alpako@condominio.com'], [
            'name' => 'Administración Residencias Alpako',
            'password' => Hash::make('123456'),
            'rol' => 'Admin de Condominio',
            'role_id' => $roleAdmin->id,
            'condominio_id' => $condominio->id,
            'telefono' => '0412-377-95-75',
            'cedula' => 'V-18765432',
            'activo' => true,
        ]);

        // 3. Apartamento 01-A con su Alícuota exacta (3.03460000%)
        $apartamento = Apartamento::updateOrCreate([
            'condominio_id' => $condominio->id,
            'numero' => '01-A',
        ], [
            'piso' => 'PB',
            'alicuota' => 3.03460000,
            'grupo_alicuota' => '1',
            'metros_cuadrados' => 95.50,
            'habitaciones' => 3,
            'banos' => 2,
            'ocupado' => true,
            'descripcion' => 'Apartamento Planta Baja',
        ]);

        // 4. Copropietario oficial (Pedro Figueroa y María Elena Lampo)
        $userPropietario = User::updateOrCreate(['email' => 'ccpg007@gmail.com'], [
            'name' => 'PEDRO FIGUEROA Y MARIA ELENA LAMPO',
            'password' => Hash::make('123456'),
            'rol' => 'Propietario/Residente',
            'role_id' => $rolePropietario->id,
            'condominio_id' => $condominio->id,
            'apartamento_id' => $apartamento->id,
            'telefono' => '0414-123-45-67',
            'cedula' => 'V-3.241.429',
            'activo' => true,
        ]);

        Propietario::updateOrCreate(['apartamento_id' => $apartamento->id], [
            'user_id' => $userPropietario->id,
            'condominio_id' => $condominio->id,
            'nombre_completo' => 'PEDRO FIGUEROA Y MARIA ELENA LAMPO',
            'cedula' => 'V-3.241.429',
            'telefono' => '0414-123-45-67',
            'email' => 'ccpg007@gmail.com',
            'es_propietario_principal' => true,
            'fecha_inicio' => Carbon::parse('2015-01-01'),
            'activo' => true,
        ]);

        // 5. Los 23 Conceptos de Gasto exactos en condominio_conceptos
        $conceptos = [
            ['ali' => '1', 'concepto' => '01 PRESTACIONES SOCIALES', 'monto' => 10.00, 'alicu' => 0.30],
            ['ali' => '1', 'concepto' => '02 SUELDOS Y SALARIOS TRABAJADORAS RESIDENCIALES', 'monto' => 0.50, 'alicu' => 0.02],
            ['ali' => '1', 'concepto' => '03 LEY POLÍTICA HABITACIONAL', 'monto' => 0.11, 'alicu' => 0.00],
            ['ali' => '1', 'concepto' => '04 IVSS', 'monto' => 0.10, 'alicu' => 0.00],
            ['ali' => '1', 'concepto' => '05 BONO LEY ALIMENTACION', 'monto' => 40.00, 'alicu' => 1.21],
            ['ali' => '1', 'concepto' => '06 BONO COMPLEMENTARIO', 'monto' => 55.00, 'alicu' => 1.67],
            ['ali' => '1', 'concepto' => '09 BONO COMPLEMENTARIO INTEGRAL INDEXADO', 'monto' => 145.00, 'alicu' => 4.40],
            ['ali' => '1', 'concepto' => 'ADQUISICIÓN Sistema de Cámaras, tuberías, fuente de poder, cableado 5 de 8', 'monto' => 165.32, 'alicu' => 5.02],
            ['ali' => '1', 'concepto' => 'CORPOELEC Jul-2026', 'monto' => 42.82, 'alicu' => 1.30],
            ['ali' => '1', 'concepto' => 'GASTOS ADMINISTRATIVOS SISTEMA AUTOMATIZADO ISAC junio y julio', 'monto' => 50.56, 'alicu' => 1.53],
            ['ali' => '1', 'concepto' => 'GASTOS ADMINISTRATIVOS Honorarios', 'monto' => 170.00, 'alicu' => 5.16],
            ['ali' => '1', 'concepto' => 'GASTOS BANCARIOS', 'monto' => 8.00, 'alicu' => 0.24],
            ['ali' => '1', 'concepto' => 'HIDROCAPITAL', 'monto' => 73.58, 'alicu' => 2.23],
            ['ali' => '1', 'concepto' => 'MANO DE OBRA Albañileria quitar frizo viejo y colocar el nuevo pared del lado del estacionamiento 1B, 6A, 11B, 2B. 4A 1 de 3', 'monto' => 116.67, 'alicu' => 3.54],
            ['ali' => '1', 'concepto' => 'MANTENIMIENTO DE JARDINES Y PODA DE ARBOLES', 'monto' => 140.00, 'alicu' => 4.25],
            ['ali' => '1', 'concepto' => 'MATERIALES Y ARTÍCULOS DE LIMPIEZA y Bolsas negras 1 de 2', 'monto' => 73.53, 'alicu' => 2.23],
            ['ali' => '1', 'concepto' => 'SERVICIO COPIA DEL PLANO ESTRUCTUAL DEL EDIFICIO', 'monto' => 11.00, 'alicu' => 0.33],
            ['ali' => '1', 'concepto' => 'SERVICIO RECOLECCION DE DESECHO Y ESCOMBROS', 'monto' => 20.00, 'alicu' => 0.61],
            ['ali' => '1', 'concepto' => 'SERVICIO DE TELÉFONO Jul-2026', 'monto' => 9.54, 'alicu' => 0.29],
            ['ali' => '1', 'concepto' => 'Z. COMISION X CRED. INMDTO', 'monto' => 0.00, 'alicu' => 0.74],
            ['ali' => '1', 'concepto' => 'Z. PUESTA DE AGUA', 'monto' => 0.00, 'alicu' => 5.00],
            ['ali' => '3', 'concepto' => 'ADQUISICIÓN E INSTALACION de un Contactor de 110 voltios para el circuito de alta velocidad ascensor par 1 de 3', 'monto' => 166.67, 'alicu' => 5.30],
            ['ali' => '3', 'concepto' => 'MANTENIMIENTO DE ASCENSORES Jul-2026', 'monto' => 80.00, 'alicu' => 2.55],
        ];

        foreach ($conceptos as $c) {
            CondominioConcepto::updateOrCreate([
                'condominio_id' => $condominio->id,
                'concepto' => $c['concepto'],
            ], [
                'ali' => $c['ali'],
                'monto_base' => $c['monto'],
                'tipo' => 'fijo',
                'categoria' => 'mantenimiento',
                'vigente_desde' => Carbon::parse('2026-07-01'),
                'activo' => true,
            ]);
        }

        // 6. Factura / Aviso de Cobro congelado para Julio del 2026
        $fondosData = [
            'nombre' => 'FONDO DE RESERVA',
            'acumulado' => 2997.06,
            'monto_mes' => 113.17,
            'monto_alicuota' => 3.43,
        ];

        $invoice = Invoice::updateOrCreate([
            'condominio_id' => $condominio->id,
            'apartamento_id' => $apartamento->id,
            'periodo' => 'Julio del 2026',
        ], [
            'numero_factura' => 'DOC: 646014',
            'fecha_emision' => Carbon::parse('2026-07-31'),
            'fecha_vencimiento' => Carbon::parse('2026-08-05'),
            'monto_total' => 38339.45,
            'monto_total_usd' => 51.35,
            'monto_alicuota_usd' => 47.92,
            'tasa_cambio' => 746.63,
            'monto_pagado' => 38339.45,
            'estado' => 'pagado',
            'descripcion' => 'Aviso de cobro y expensas comunes Julio 2026',
            'detalles_gastos' => $conceptos,
            'fondos' => $fondosData,
        ]);

        // 7. Pago conciliado
        Payment::updateOrCreate([
            'invoice_id' => $invoice->id,
        ], [
            'condominio_id' => $condominio->id,
            'monto' => 38339.45,
            'tasa_cambio' => 746.63,
            'fecha_pago' => Carbon::parse('2026-07-31'),
            'metodo_pago' => 'pago_movil',
            'banco' => 'BNC',
            'referencia' => 'PM-646014',
            'estado' => 'aprobado',
            'registrado_por' => $userPropietario->id,
        ]);
    }
}
