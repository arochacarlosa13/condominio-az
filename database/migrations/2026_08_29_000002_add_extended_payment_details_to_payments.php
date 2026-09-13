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
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'banco_origen')) {
                $table->string('banco_origen')->nullable()->after('banco');
            }
            if (!Schema::hasColumn('payments', 'telefono_origen')) {
                $table->string('telefono_origen')->nullable()->after('banco_origen');
            }
            if (!Schema::hasColumn('payments', 'cedula_origen')) {
                $table->string('cedula_origen')->nullable()->after('telefono_origen');
            }
            if (!Schema::hasColumn('payments', 'moneda_origen')) {
                $table->string('moneda_origen', 10)->default('VES')->after('monto');
            }
            if (!Schema::hasColumn('payments', 'monto_divisa')) {
                $table->decimal('monto_divisa', 12, 2)->nullable()->after('moneda_origen');
            }
            if (!Schema::hasColumn('payments', 'comprobante_path')) {
                $table->string('comprobante_path')->nullable()->after('estado');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('payments', 'banco_origen')) $columns[] = 'banco_origen';
            if (Schema::hasColumn('payments', 'telefono_origen')) $columns[] = 'telefono_origen';
            if (Schema::hasColumn('payments', 'cedula_origen')) $columns[] = 'cedula_origen';
            if (Schema::hasColumn('payments', 'moneda_origen')) $columns[] = 'moneda_origen';
            if (Schema::hasColumn('payments', 'monto_divisa')) $columns[] = 'monto_divisa';
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
