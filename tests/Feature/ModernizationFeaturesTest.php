<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Condominio;
use App\Models\Apartamento;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Vote;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class ModernizationFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Evitar que errores de postgres constraint afecten sqlite en pruebas
    }

    public function test_conciliacion_bancaria_service_matches_payments()
    {
        $condominio = Condominio::create([
            'nombre' => 'Residencias Los Andes',
            'rif' => 'J-12345678-9',
            'direccion' => 'Av. Principal 123, Caracas',
            'activo' => true,
        ]);

        $admin = User::create([
            'name' => 'Admin Condominio',
            'email' => 'admin@condominio.com',
            'password' => bcrypt('123456'),
            'rol' => 'admin',
            'condominio_id' => $condominio->id,
            'activo' => true,
        ]);

        $apto = Apartamento::create([
            'condominio_id' => $condominio->id,
            'numero' => '1-A',
            'piso' => 1,
            'alicuota' => 10.0,
            'alicuotas_detalle' => [['numero' => 1, 'nombre' => 'Gastos', 'porcentaje' => 10.0]],
        ]);

        $invoice = Invoice::create([
            'condominio_id' => $condominio->id,
            'apartamento_id' => $apto->id,
            'numero_factura' => 'REC-CONC-001',
            'periodo' => '2026-09',
            'fecha_emision' => Carbon::today(),
            'fecha_vencimiento' => Carbon::today()->addDays(5),
            'monto_total' => 150.00,
            'estado' => 'emitido',
        ]);

        $payment = Payment::create([
            'condominio_id' => $condominio->id,
            'invoice_id' => $invoice->id,
            'registrado_por' => $admin->id,
            'monto' => 150.00,
            'referencia' => '98765432',
            'metodo_pago' => 'transferencia',
            'banco' => 'Banesco',
            'fecha_pago' => Carbon::today(),
            'estado' => 'pendiente',
        ]);

        $csvContent = "Fecha,Referencia,Monto,Concepto\n" . Carbon::today()->format('d/m/Y') . ",98765432,150.00,Transferencia de Propietario";
        $tempPath = tempnam(sys_get_temp_dir(), 'extracto_') . '.csv';
        file_put_contents($tempPath, $csvContent);

        $service = new \App\Services\ConciliacionBancariaService();
        $resultado = $service->procesarExtracto($tempPath, $condominio->id);
        @unlink($tempPath);

        $this->assertEquals(1, $resultado['total_movimientos_leidos']);
        $this->assertEquals(1, $resultado['coincidencias_exactas']);
        $this->assertEquals('exacto', $resultado['movimientos'][0]['match_status']);
    }

    public function test_flujo_caja_calculates_real_time_balances()
    {
        $condominio = Condominio::create([
            'nombre' => 'Torre Norte',
            'rif' => 'J-98765432-1',
            'direccion' => 'Av. Francisco de Miranda',
            'activo' => true,
        ]);

        $admin = User::create([
            'name' => 'Admin Finanzas',
            'email' => 'finanzas@condominio.com',
            'password' => bcrypt('123456'),
            'rol' => 'admin',
            'condominio_id' => $condominio->id,
            'activo' => true,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->withHeaders(['X-Condominio-Id' => $condominio->id])
            ->getJson('/api/v1/contabilidad/flujo-caja');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'resumen' => [
                    'total_cobrado_banco_bs',
                    'total_gastos_pagados_bs',
                    'saldo_bancario_disponible_bs',
                ],
                'cuentas_bancarias',
                'historico_flujo',
            ]
        ]);
    }

    public function test_certificacion_publica_recibo_sha256()
    {
        $condominio = Condominio::create([
            'nombre' => 'Residencias Avila',
            'rif' => 'J-55555555-5',
            'direccion' => 'Urb. Avila',
            'activo' => true,
        ]);

        $apto = Apartamento::create([
            'condominio_id' => $condominio->id,
            'numero' => 'PH-01',
            'piso' => 10,
            'alicuota' => 5.2500,
            'alicuotas_detalle' => [['numero' => 1, 'nombre' => 'Gastos', 'porcentaje' => 5.25]],
        ]);

        $invoice = Invoice::create([
            'condominio_id' => $condominio->id,
            'apartamento_id' => $apto->id,
            'numero_factura' => 'REC-2026-PH01',
            'periodo' => '2026-09',
            'fecha_emision' => Carbon::today(),
            'fecha_vencimiento' => Carbon::today()->addDays(5),
            'monto_total' => 120.00,
            'estado' => 'emitido',
        ]);

        $docNumero = $invoice->numero_factura;
        $hashSha256 = hash('sha256', "AZPRO_INVOICE_{$invoice->id}_{$docNumero}_{$invoice->monto_total}_{$invoice->created_at}_{$invoice->condominio_id}");
        $token = substr($hashSha256, 0, 24);

        $response = $this->get("/validar-recibo/{$invoice->id}?token={$token}");
        $response->assertStatus(200);
        $response->assertSee('Documento Auténtico y Certificado');
        $response->assertSee('PH-01');
        $response->assertSee('REC-2026-PH01');
    }

    public function test_backup_command_creates_backup_file()
    {
        $exitCode = Artisan::call('condominio:backup');
        $this->assertEquals(0, $exitCode);

        $backupDir = storage_path('app/backups');
        $this->assertTrue(File::exists($backupDir));
        $files = File::files($backupDir);
        $this->assertNotEmpty($files);
    }

    public function test_two_factor_authentication_flow()
    {
        $user = User::create([
            'name' => 'Usuario Seguro',
            'email' => 'seguro@condominio.com',
            'password' => bcrypt('secreto123'),
            'rol' => 'propietario',
            'activo' => true,
            'dos_factores_activo' => true,
        ]);

        // Paso 1: Intento de login normal
        $response = $this->postJson('/api/v1/login', [
            'email' => 'seguro@condominio.com',
            'password' => 'secreto123',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['requires_2fa' => true]);

        $user->refresh();
        $this->assertNotNull($user->two_factor_code);

        $tempToken = $response->json('data.temp_token');

        // Paso 2: Validación OTP
        $verifyResponse = $this->postJson('/api/v1/verificar-2fa', [
            'email' => 'seguro@condominio.com',
            'code' => $user->two_factor_code,
            'temp_token' => $tempToken,
        ]);

        $verifyResponse->assertStatus(200);
        $verifyResponse->assertJsonStructure([
            'success',
            'data' => ['user', 'token']
        ]);
    }

    public function test_asambleas_weighted_voting_and_quorum()
    {
        $condominio = Condominio::create([
            'nombre' => 'Conjunto Las Trinitarias',
            'rif' => 'J-11112222-3',
            'direccion' => 'Calle Los Samanes',
            'activo' => true,
        ]);

        $admin = User::create([
            'name' => 'Presidente Junta',
            'email' => 'junta@condominio.com',
            'password' => bcrypt('123456'),
            'rol' => 'admin',
            'condominio_id' => $condominio->id,
            'activo' => true,
        ]);

        $apto = Apartamento::create([
            'condominio_id' => $condominio->id,
            'numero' => '101',
            'piso' => 1,
            'alicuota' => 25.0000,
            'alicuotas_detalle' => [['numero' => 1, 'nombre' => 'General', 'porcentaje' => 25.0]],
        ]);

        $votante = User::create([
            'name' => 'Propietario 101',
            'email' => 'apto101@condominio.com',
            'password' => bcrypt('123456'),
            'rol' => 'propietario',
            'condominio_id' => $condominio->id,
            'apartamento_id' => $apto->id,
            'activo' => true,
        ]);

        // Crear asamblea
        $createResponse = $this->actingAs($admin, 'sanctum')
            ->withHeaders(['X-Condominio-Id' => $condominio->id])
            ->postJson('/api/v1/asambleas', [
                'titulo' => 'Aprobación de Pintura Fachada',
                'descripcion' => 'Propuesta para renovar la pintura exterior del edificio.',
                'fecha_inicio' => Carbon::now()->subHour()->format('Y-m-d H:i:s'),
                'fecha_fin' => Carbon::now()->addDays(5)->format('Y-m-d H:i:s'),
                'opciones' => [
                    ['texto' => 'Aprobar Presupuesto A'],
                    ['texto' => 'Rechazar'],
                ],
            ]);

        $createResponse->assertStatus(200);
        $pollId = $createResponse->json('data.id');
        $opcionId = $createResponse->json('data.opciones.0.id');

        // Votar con ponderación de 25% de alícuota
        $voteResponse = $this->actingAs($votante, 'sanctum')
            ->withHeaders(['X-Condominio-Id' => $condominio->id])
            ->postJson("/api/v1/asambleas/{$pollId}/votar", [
                'opcion_id' => $opcionId,
            ]);

        $voteResponse->assertStatus(200);
        $voteResponse->assertJson([
            'success' => true,
            'data' => [
                'total_votos' => 1,
                'alicuota_votada_total' => 25.0000,
            ]
        ]);
    }
}
