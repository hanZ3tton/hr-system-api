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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->date('date')->index();

            $table->timestamp('check_in_at')->nullable();
            $table->string('check_in_location')->nullable();
            $table->string('check_in_ip', 45)->nullable();

            $table->timestamp('check_out_at')->nullable();
            $table->string('check_out_location')->nullable();
            $table->string('check_out_ip', 45)->nullable();

            $table->enum('status', ['present', 'absent', 'on_leave'])->default('present');
            $table->integer('worked_second')->nullable();
            $table->text('note')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
