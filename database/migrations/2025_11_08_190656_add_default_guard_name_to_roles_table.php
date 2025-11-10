<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            // Ubah kolom guard_name untuk memiliki nilai default 'web'
            $table->string('guard_name', 125)->default('web')->change();
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            // Kembalikan kolom ke kondisi semula (misalnya, tanpa default)
            $table->string('guard_name', 125)->default(null)->change();
        });
    }
};
