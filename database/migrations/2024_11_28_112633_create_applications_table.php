<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Applicant's name
            $table->string('address')->nullable();
            $table->string('contact_number');
            $table->string('occupation')->nullable();
            $table->timestamp('check_in_date')->nullable();
            $table->integer('duration')->nullable(); // Duration of stay
            $table->text('reservation_details')->nullable();
            $table->string('status')->default('pending'); // Status: pending/approved/rejected
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('applications');
    }
}
