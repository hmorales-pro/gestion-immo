<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            // ULID primary key: public links (/v/{property}) must not be enumerable.
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('address');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('slot_duration')->default(30); // minutes
            $table->string('booking_mode', 32)->default('OWNER_APPROVAL');
            $table->string('owner_name');
            $table->string('owner_email');
            $table->string('owner_phone', 32)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('property_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('property_id')->constrained()->cascadeOnDelete();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->boolean('is_booked')->default(false);
            $table->timestamps();

            $table->unique(['property_id', 'start_time']);
            $table->index(['property_id', 'is_booked', 'start_time']);
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_slot_id')->constrained()->cascadeOnDelete();
            $table->string('prospect_name');
            $table->string('prospect_email');
            $table->string('prospect_phone', 32);
            $table->string('employment_status', 32);
            $table->text('message')->nullable();
            $table->string('status', 16)->default('PENDING');
            $table->string('token', 64)->unique();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'status']);
            $table->index(['property_slot_id', 'status']);
            $table->index(['prospect_email', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('property_slots');
        Schema::dropIfExists('properties');
    }
};
