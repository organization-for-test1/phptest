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
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->integer('tourId');
            $table->string('guetsName');
            $table->string('contactNo');
            $table->string('email');
            $table->string('tourType');
            $table->string('duration');
            $table->integer('adults');
            $table->integer('child');
            $table->integer('familyHead');
            $table->string('reference');
            $table->integer('guestReferrId');
            $table->string('priority');
            $table->string('nextFollowUp');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enquiries');
    }
};
