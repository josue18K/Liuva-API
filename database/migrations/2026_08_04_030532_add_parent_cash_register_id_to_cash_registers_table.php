<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->foreignId('parent_cash_register_id')
                ->nullable()
                ->after('id')
                ->constrained('cash_registers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_cash_register_id');
        });
    }
};
