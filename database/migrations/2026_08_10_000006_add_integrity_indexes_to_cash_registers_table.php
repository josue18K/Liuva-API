<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->unique('parent_cash_register_id');
            $table->index(['user_id', 'sede_id', 'tipo', 'fecha_hora']);
        });

        Schema::table('cash_register_denominations', function (Blueprint $table) {
            $table->unique(['cash_register_id', 'denominacion']);
        });
    }

    public function down(): void
    {
        Schema::table('cash_register_denominations', function (Blueprint $table) {
            $table->dropUnique(['cash_register_id', 'denominacion']);
        });

        Schema::table('cash_registers', function (Blueprint $table) {
            $table->dropUnique(['parent_cash_register_id']);
            $table->dropIndex(['user_id', 'sede_id', 'tipo', 'fecha_hora']);
        });
    }
};
