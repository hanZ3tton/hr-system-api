<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Additive only
            $table->string('check_in_photo_path', 1024)->nullable()->after('check_in_location');
            $table->string('check_out_photo_path', 1024)->nullable()->after('check_out_location');
            // Optional: content type or thumb if needed later
            $table->string('check_in_photo_mime', 50)->nullable()->after('check_in_photo_path');
            $table->string('check_out_photo_mime', 50)->nullable()->after('check_out_photo_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'check_in_photo_path',
                'check_out_photo_path',
                'check_in_photo_mime',
                'check_out_photo_mime',
            ]);
        });
    }
};
