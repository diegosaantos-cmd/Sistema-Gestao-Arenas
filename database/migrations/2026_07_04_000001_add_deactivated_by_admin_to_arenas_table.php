<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arenas', function (Blueprint $table) {
            $table->boolean('deactivated_by_admin')
                ->default(false)
                ->after('active');
        });
    }

    public function down(): void
    {
        Schema::table('arenas', function (Blueprint $table) {
            $table->dropColumn('deactivated_by_admin');
        });
    }
};
