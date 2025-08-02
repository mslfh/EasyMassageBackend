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
        Schema::create('voucher_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained()->onDelete('cascade');

            $table->integer('user_id')->nullable();
            $table->integer('appointment_id')->nullable();

            $table->string('phone')->nullable();
            $table->string('name')->nullable();
            $table->string('service')->nullable();

            $table->string('action')->default('consume')
            ->comment('Action taken on the voucher, e.g., init, consume, edit etc.');
            $table->string('description')->nullable();
            $table->double('pre_amount')->default(0);
            $table->double('after_amount')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher_histories');
    }
};
