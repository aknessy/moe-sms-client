<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Whoever acts on the Suggestions (By Superior Officer) section signs it and
 * says the day they acted. Nullable, because suggestions already on file were
 * made without either.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suggestions', function (Blueprint $table) {
            $table->date('acted_on')->nullable();
            $table->string('signature', 160)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('suggestions', function (Blueprint $table) {
            $table->dropColumn(['acted_on', 'signature']);
        });
    }
};
