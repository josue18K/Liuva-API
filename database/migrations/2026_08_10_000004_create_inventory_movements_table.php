<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('sede_id')->constrained('sedes')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo', 20);
            $table->unsignedInteger('cantidad');
            $table->integer('stock_anterior');
            $table->integer('stock_nuevo');
            $table->string('origen_tipo', 50)->nullable();
            $table->unsignedBigInteger('origen_id')->nullable();
            $table->text('motivo')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'sede_id', 'created_at']);
            $table->index(['tipo', 'created_at']);
            $table->index(['origen_tipo', 'origen_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
