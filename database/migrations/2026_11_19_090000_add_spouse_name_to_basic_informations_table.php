<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marital status says how an officer is married; this names who to.
     */
    public function up(): void
    {
        Schema::table('basic_informations', function (Blueprint $table) {
            $table->string('spouse_name')->nullable()
                ->after('marital_status');
        });
    }

    public function down(): void
    {
        Schema::table('basic_informations', function (Blueprint $table) {
            $table->dropColumn('spouse_name');
        });
    }
};
