<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The General Remarks section of the Annual Performance Evaluation Report:
 * the reporting officer's own words drawing attention to any particular
 * strength or weakness, beyond what the sections before it already asked
 * after one at a time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('general_remarks', function (Blueprint $table) {
            $table->id();
            // One remark per officer; a later save corrects this one rather
            // than adding beside it.
            $table->string('basic_information_ulid', 26)->unique();
            $table->text('remarks');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('general_remarks');
    }
};
