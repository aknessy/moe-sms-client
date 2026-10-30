<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An officer's passport photograph. Held on the private disk like the benefit
 * receipts are, so the path is what the record carries and the file itself is
 * only ever handed back through the dashboard.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('basic_informations', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('address');
            // What they called it, so a download reads as the file they sent.
            $table->string('photo_name')->nullable()->after('photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('basic_informations', function (Blueprint $table) {
            $table->dropColumn(['photo_path', 'photo_name']);
        });
    }
};
