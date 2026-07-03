<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->boolean('active')->default(true)->after('tax_id');
            $table->foreignId('deactivated_by')
                ->nullable()
                ->after('active')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('deactivation_source', 20)->nullable()->after('deactivated_by');
            $table->timestamp('deactivated_at')->nullable()->after('deactivation_source');
        });

        DB::table('owners')
            ->join('users', 'users.id', '=', 'owners.user_id')
            ->where('users.active', false)
            ->update([
                'owners.active' => false,
                'owners.deactivation_source' => 'admin',
                'owners.deactivated_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->dropForeign(['deactivated_by']);
            $table->dropColumn([
                'active',
                'deactivated_by',
                'deactivation_source',
                'deactivated_at',
            ]);
        });
    }
};
