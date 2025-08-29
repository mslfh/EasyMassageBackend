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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('packages')->onDelete('cascade');
            $table->string('title');
            $table->string('description')->nullable();
            $table->integer('duration')->nullable();
            $table->string('status')->default('active');
            $table->boolean('is_viewable')->default(false);
            $table->double('price');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
