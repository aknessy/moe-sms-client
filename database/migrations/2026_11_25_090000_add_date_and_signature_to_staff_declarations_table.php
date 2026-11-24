<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Staff Declaration is dated and signed by the officer, like the
 * declarations after it. Nullable, because declarations already on file were
 * made without either.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_declarations', function (Blueprint $table) {
            $table->date('declared_on')->nullable()->after('comments');
            $table->string('signature', 160)->nullable()->after('declared_on');
        });
    }

    public function down(): void
    {
        Schema::table('staff_declarations', function (Blueprint $table) {
            $table->dropColumn(['declared_on', 'signature']);
        });
    }
};
