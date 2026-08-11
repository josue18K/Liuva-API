<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('detalle');
            $table->string('user_agent', 500)->nullable()->after('ip_address');
            $table->json('metadata')->nullable()->after('user_agent');
            $table->index(['modelo', 'modelo_id']);
            $table->index(['accion', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['modelo', 'modelo_id']);
            $table->dropIndex(['accion', 'created_at']);
            $table->dropColumn(['ip_address', 'user_agent', 'metadata']);
        });
    }
};
