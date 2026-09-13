<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'estado_certificacion')) {
                $table->string('estado_certificacion', 30)->default('certificado')->after('estado')->comment('borrador | certificado');
            }
            if (!Schema::hasColumn('invoices', 'fecha_certificacion')) {
                $table->timestamp('fecha_certificacion')->nullable()->after('estado_certificacion');
            }
            if (!Schema::hasColumn('invoices', 'certificado_por_id')) {
                $table->foreignId('certificado_por_id')->nullable()->constrained('users')->nullOnDelete()->after('fecha_certificacion');
            }
            if (!Schema::hasColumn('invoices', 'veces_reabierto')) {
                $table->integer('veces_reabierto')->default(0)->after('certificado_por_id')->comment('Contador de reaperturas por Super Admin (máx 2)');
            }
            if (!Schema::hasColumn('invoices', 'reabierto_por_id')) {
                $table->foreignId('reabierto_por_id')->nullable()->constrained('users')->nullOnDelete()->after('veces_reabierto');
            }
            if (!Schema::hasColumn('invoices', 'motivo_reapertura')) {
                $table->text('motivo_reapertura')->nullable()->after('reabierto_por_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['certificado_por_id']);
            $table->dropForeign(['reabierto_por_id']);
            $table->dropColumn([
                'estado_certificacion',
                'fecha_certificacion',
                'certificado_por_id',
                'veces_reabierto',
                'reabierto_por_id',
                'motivo_reapertura'
            ]);
        });
    }
};
