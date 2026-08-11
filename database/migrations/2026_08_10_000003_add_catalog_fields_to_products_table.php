<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('descripcion')->nullable()->after('nombre');
            $table->string('unidad', 30)->default('unidad')->after('precio_oficial');
            $table->unsignedInteger('stock_minimo')->default(0)->after('unidad');
            $table->index('nombre');
            $table->index(['category_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['nombre']);
            $table->dropIndex(['category_id', 'active']);
            $table->dropColumn(['descripcion', 'unidad', 'stock_minimo']);
        });
    }
};
