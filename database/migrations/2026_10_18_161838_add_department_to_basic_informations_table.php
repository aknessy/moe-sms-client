<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('basic_informations', function (Blueprint $table) {
            // Posting history tracks where an officer has moved; these hold where
            // they currently sit, which correspondence and the nominal roll need.
            $table->string('department')->nullable()->after('othernames');
            $table->string('division')->nullable()->after('department');
            $table->string('designation')->nullable()->after('division');
            $table->string('grade_level')->nullable()->after('designation');
        });
    }

    public function down(): void
    {
        Schema::table('basic_informations', function (Blueprint $table) {
            $table->dropColumn(['department', 'division', 'designation', 'grade_level']);
        });
    }
};
