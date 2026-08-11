<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('sede_id')
                ->nullable()
                ->after('active')
                ->constrained('sedes')
                ->nullOnDelete();
            $table->string('estado', 20)->default('pendiente')->after('sede_id')->index();
        });

        DB::table('users')->orderBy('id')->each(function (object $user): void {
            $estado = $user->active
                ? 'activo'
                : 'deshabilitado';

            DB::table('users')->where('id', $user->id)->update(['estado' => $estado]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['estado']);
            $table->dropColumn('estado');
            $table->dropConstrainedForeignId('sede_id');
        });
    }
};
