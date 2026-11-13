<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            // Private link to the owner's follow-up page (visits and feedback).
            $table->string('owner_token', 64)->nullable()->unique()->after('owner_phone');
            // Current occupant (outgoing tenant): informed of every visit.
            $table->string('occupant_name')->nullable()->after('owner_token');
            $table->string('occupant_email')->nullable()->after('occupant_name');
            $table->string('occupant_phone', 32)->nullable()->after('occupant_email');
        });

        Schema::table('bookings', function (Blueprint $table) {
            // Visit voucher (bon de visite) signed on the agent's phone.
            $table->string('voucher_signer_name')->nullable();
            $table->longText('voucher_signature')->nullable(); // PNG data URL
            $table->timestamp('voucher_signed_at')->nullable();
            // Visitor feedback after the visit.
            $table->string('feedback_interest', 16)->nullable(); // yes | maybe | no
            $table->string('feedback_price', 16)->nullable();    // high | fair | low
            $table->text('feedback_comment')->nullable();
            $table->timestamp('feedback_at')->nullable();
        });

        Schema::create('waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('property_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->timestamps();

            $table->unique(['property_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlist_entries');

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'voucher_signer_name', 'voucher_signature', 'voucher_signed_at',
                'feedback_interest', 'feedback_price', 'feedback_comment', 'feedback_at',
            ]);
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropUnique(['owner_token']);
            $table->dropColumn(['owner_token', 'occupant_name', 'occupant_email', 'occupant_phone']);
        });
    }
};
