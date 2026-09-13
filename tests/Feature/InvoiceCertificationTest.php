<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Condominio;
use App\Models\Apartamento;
use App\Models\Invoice;
use App\Models\CondominioConcepto;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvoiceCertificationTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdmin;
    protected $adminCondo;
    protected $condominio;
    protected $apartamento;

    protected function setUp(): void
    {
        parent::setUp();

        $roleSuper = Role::firstOrCreate(['slug' => 'super-admin'], ['nombre' => 'Super Admin', 'descripcion' => 'Acceso total', 'status' => true]);
        $roleAdmin = Role::firstOrCreate(['slug' => 'admin-condominio'], ['nombre' => 'Admin de Condominio', 'descripcion' => 'Admin', 'status' => true]);

        $this->condominio = Condominio::firstOrCreate(
            ['nombre' => 'Residencias Monte Verde Test'],
            ['rif' => 'J-12345678-9', 'direccion' => 'Calle Principal', 'tasa_cambio' => 36.50, 'fondo_reserva_porcentaje' => 10]
        );

        $this->apartamento = Apartamento::firstOrCreate(
            ['condominio_id' => $this->condominio->id, 'numero' => '1-A'],
            ['piso' => 1, 'alicuota' => 10.0, 'metros_cuadrados' => 80]
        );

        $this->superAdmin = User::firstOrCreate(
            ['email' => 'super_test@condominio.com'],
            ['name' => 'Super User', 'password' => bcrypt('password123'), 'role_id' => $roleSuper->id]
        );

        $this->adminCondo = User::firstOrCreate(
            ['email' => 'admin_test@condominio.com'],
            ['name' => 'Admin User', 'password' => bcrypt('password123'), 'role_id' => $roleAdmin->id, 'condominio_id' => $this->condominio->id]
        );
    }

    public function test_admin_can_generate_draft_and_certify_monthly_receipts()
    {
        // 1. Generar borrador
        $response = $this->actingAs($this->adminCondo, 'sanctum')
            ->withHeader('X-Condominio-Id', $this->condominio->id)
            ->postJson('/api/v1/invoices/generar-masivo', [
                'periodo' => 'Agosto del 2026',
                'conceptos' => [
                    ['ali' => '1', 'concepto' => 'Vigilancia 24h', 'monto' => 300],
                    ['ali' => '1', 'concepto' => 'Mantenimiento Bomba', 'monto' => 150],
                ],
                'certificar_inmediatamente' => false,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('invoices', [
            'condominio_id' => $this->condominio->id,
            'periodo' => 'Agosto del 2026',
            'estado_certificacion' => 'borrador',
        ]);

        // 2. Certificar el período
        $certResponse = $this->actingAs($this->adminCondo, 'sanctum')
            ->withHeader('X-Condominio-Id', $this->condominio->id)
            ->postJson('/api/v1/invoices/certificar-periodo', [
                'periodo' => 'Agosto del 2026',
            ]);

        $certResponse->assertStatus(200);
        $this->assertDatabaseHas('invoices', [
            'condominio_id' => $this->condominio->id,
            'periodo' => 'Agosto del 2026',
            'estado_certificacion' => 'certificado',
        ]);

        // 3. Administrador no puede regenerar mientras esté certificado
        $blockedResponse = $this->actingAs($this->adminCondo, 'sanctum')
            ->withHeader('X-Condominio-Id', $this->condominio->id)
            ->postJson('/api/v1/invoices/generar-masivo', [
                'periodo' => 'Agosto del 2026',
                'conceptos' => [
                    ['ali' => '1', 'concepto' => 'Gasto no autorizado', 'monto' => 999],
                ],
            ]);

        $blockedResponse->assertStatus(403);
    }

    public function test_super_admin_can_reopen_certified_receipt_up_to_two_times()
    {
        // Crear recibo certificado
        $this->actingAs($this->adminCondo, 'sanctum')
            ->withHeader('X-Condominio-Id', $this->condominio->id)
            ->postJson('/api/v1/invoices/generar-masivo', [
                'periodo' => 'Septiembre del 2026',
                'conceptos' => [['ali' => '1', 'concepto' => 'Luz Áreas Comunes', 'monto' => 100]],
                'certificar_inmediatamente' => true,
            ]);

        // Intento de reapertura 1 por Super Admin (debe tener éxito)
        $reabrir1 = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v1/invoices/reabrir-periodo', [
                'condominio_id' => $this->condominio->id,
                'periodo' => 'Septiembre del 2026',
                'motivo' => 'Primera corrección solicitada por junta',
            ]);

        $reabrir1->assertStatus(200);
        $reabrir1->assertJsonPath('data.veces_reabierto', 1);
        $reabrir1->assertJsonPath('data.estado_certificacion', 'borrador');

        // Admin vuelve a certificarlo
        $this->actingAs($this->adminCondo, 'sanctum')
            ->withHeader('X-Condominio-Id', $this->condominio->id)
            ->postJson('/api/v1/invoices/certificar-periodo', [
                'periodo' => 'Septiembre del 2026',
            ])->assertStatus(200);

        // Intento de reapertura 2 por Super Admin (debe tener éxito)
        $reabrir2 = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v1/invoices/reabrir-periodo', [
                'condominio_id' => $this->condominio->id,
                'periodo' => 'Septiembre del 2026',
                'motivo' => 'Segunda corrección autorizada',
            ]);

        $reabrir2->assertStatus(200);
        $reabrir2->assertJsonPath('data.veces_reabierto', 2);

        // Admin vuelve a certificarlo
        $this->actingAs($this->adminCondo, 'sanctum')
            ->withHeader('X-Condominio-Id', $this->condominio->id)
            ->postJson('/api/v1/invoices/certificar-periodo', [
                'periodo' => 'Septiembre del 2026',
            ])->assertStatus(200);

        // Intento de reapertura 3 por Super Admin (DEBE FALLAR con error 422 - límite alcanzado)
        $reabrir3 = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/api/v1/invoices/reabrir-periodo', [
                'condominio_id' => $this->condominio->id,
                'periodo' => 'Septiembre del 2026',
                'motivo' => 'Tercer intento no permitido',
            ]);

        $reabrir3->assertStatus(422);
    }

    public function test_draft_receipt_can_be_downloaded_as_pdf_with_watermark()
    {
        // 1. Generar recibo en borrador
        $this->actingAs($this->adminCondo, 'sanctum')
            ->withHeader('X-Condominio-Id', $this->condominio->id)
            ->postJson('/api/v1/invoices/generar-masivo', [
                'periodo' => 'Octubre del 2026',
                'conceptos' => [['ali' => '1', 'concepto' => 'Gasto de Prueba Borrador', 'monto' => 120]],
                'certificar_inmediatamente' => false,
            ]);

        // 2. Solicitar descarga del PDF de período
        $pdfResponse = $this->get('/api/v1/reportes/recibo-periodo?periodo=Octubre+del+2026&condominio_id=' . $this->condominio->id);

        $pdfResponse->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfResponse->headers->get('Content-Type'));
        $this->assertStringContainsString('BORRADOR-Recibo', $pdfResponse->headers->get('Content-Disposition') ?? '');
    }
}

