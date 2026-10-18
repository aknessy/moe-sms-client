<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_queries', function (Blueprint $table) {
            // Outcome recorded against the officer's written response.
            $table->string('decision')->nullable()->after('response');   // satisfactory | further-action | panel | sanctioned
            $table->date('decision_date')->nullable()->after('decision');
            $table->string('decision_by')->nullable()->after('decision_date');
            $table->text('decision_remarks')->nullable()->after('decision_by');
        });
    }

    public function down(): void
    {
        Schema::table('staff_queries', function (Blueprint $table) {
            $table->dropColumn(['decision', 'decision_date', 'decision_by', 'decision_remarks']);
        });
    }
};
