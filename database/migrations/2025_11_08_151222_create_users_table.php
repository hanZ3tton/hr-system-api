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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('password', 255);

            $table->string('employee_number', 50)->unique()->nullable();
            $table->string('phone', 30)->nullable();

            $table->unsignedBigInteger('department_id')->nullable();

            $table->boolean('is_active')->default(true);
            $table->string('timezone', 50)->default('Asia/Jakarta');

            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index('employee_number');
            $table->index('department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
