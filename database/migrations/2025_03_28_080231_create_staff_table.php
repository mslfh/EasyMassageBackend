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
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('profile_photo_path',2048)->nullable();
            $table->string('name')->nullable();
            $table->string('position')->nullable();
            $table->string('description')->nullable();
            $table->string('tag')->nullable();
            $table->string('has_certificate')->default("false");
            $table->string('status')->default('active');
            $table->double('level')->default('5.0');
            $table->integer('sort')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
