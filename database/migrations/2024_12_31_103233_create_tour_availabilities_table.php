<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('tour_availabilities', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->time('time');
            $table->string('user_email')->nullable(); // Nullable for non-booked statuses
            $table->string('name')->nullable();       // Nullable for non-booked statuses
            $table->string('phone_number')->nullable(); // Nullable for non-booked statuses
            $table->integer('num_visitors')->nullable(); // Nullable for non-booked statuses
            $table->enum('status', ['available', 'unavailable', 'booked']);
            $table->timestamps();
        });
        
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tour_availabilities');
    }
};
