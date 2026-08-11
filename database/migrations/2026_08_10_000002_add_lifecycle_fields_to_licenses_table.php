<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->string('estado', 20)->default('disponible')->after('status')->index();
            $table->timestamp('blocked_at')->nullable()->after('used_at');
        });

        DB::table('licenses')->orderBy('id')->each(function (object $license): void {
            DB::table('licenses')->where('id', $license->id)->update([
                'estado' => $license->status === 'usada' ? 'activada' : 'disponible',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropIndex(['estado']);
            $table->dropColumn(['estado', 'blocked_at']);
        });
    }
};
