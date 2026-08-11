<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sede_id')->constrained('sedes')->cascadeOnDelete();
            $table->enum('tipo', ['apertura', 'cierre']);
            $table->decimal('monto_esperado', 10, 2)->nullable();
            $table->decimal('monto_contado', 10, 2);
            $table->decimal('diferencia', 10, 2)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamp('fecha_hora');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_registers');
    }
};
