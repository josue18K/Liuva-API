<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('cash_register_id')
                ->nullable()
                ->after('sede_id')
                ->constrained('cash_registers')
                ->nullOnDelete();
            $table->string('forma_pago', 20)->default('efectivo')->after('cash_register_id');
            $table->string('comprobante_numero', 30)->nullable()->after('total')->unique();
            $table->uuid('comprobante_token')->nullable()->after('comprobante_numero')->unique();
            $table->index(['user_id', 'created_at']);
            $table->index(['sede_id', 'created_at']);
            $table->index(['forma_pago', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['sede_id', 'created_at']);
            $table->dropIndex(['forma_pago', 'created_at']);
            $table->dropUnique(['comprobante_numero']);
            $table->dropUnique(['comprobante_token']);
            $table->dropColumn([
                'forma_pago',
                'comprobante_numero',
                'comprobante_token',
            ]);
            $table->dropConstrainedForeignId('cash_register_id');
        });
    }
};
