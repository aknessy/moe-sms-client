<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The bio-data carries a family life as well as a working one: how the
     * officer is married, and the children the service should know about —
     * at most two, and named with the day each was born.
     */
    public function up(): void
    {
        Schema::table('basic_informations', function (Blueprint $table) {
            $table->string('marital_status')->nullable()
                ->after('gender');
            $table->json('children')->nullable()
                ->after('marital_status');
        });
    }

    public function down(): void
    {
        Schema::table('basic_informations', function (Blueprint $table) {
            $table->dropColumn(['marital_status', 'children']);
        });
    }
};
