<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appointment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('success')
                ->comment('success, failed');
            $table->string('action')->default('BOOKED')
                ->comment('BOOKED, MESSAGE_SENT, UPDATED, CHECKED_OUT, CANCELED');
            $table->string('description')->default('appointment booked')
                ->comment('appointment booked, message sent, waiting for service, checked out, appointment canceled');
            $table->dateTime('booking_time')->comment('The time of the service appointment')->nullable();
            $table->string('service_title')->nullable();
            $table->string('customer_name')->nullable();
            $table->text('comments')->nullable();
            $table->string('staff_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_logs');
    }
};
