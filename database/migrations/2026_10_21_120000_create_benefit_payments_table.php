<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Money actually paid out against an officer's exit, with the receipt that
 * evidences it. The status fields on staff_exits say where a benefit has got
 * to; this says what was paid, when, to whom, and on what authority.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('benefit_payments', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('basic_information_ulid')->index();

            // gratuity | pension | condolence-pause | hazard | group-life
            $table->string('benefit_type')->index();
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->string('reference_number')->nullable();
            // For a death benefit this is the next of kin who received it.
            $table->string('paid_to')->nullable();

            // The receipt lives on the private disk; this is its path there,
            // alongside the name it was uploaded under so it can be offered
            // back for download as the user recognises it.
            $table->string('receipt_path')->nullable();
            $table->string('receipt_name')->nullable();

            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('basic_information_ulid')
                ->references('ulid')->on('basic_informations')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('benefit_payments');
    }
};
