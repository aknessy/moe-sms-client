<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('surname')->after('name')->nullable();
            $table->string('firstname')->after('surname')->nullable();
            $table->string('othernames')->after('firstname')->nullable();
            $table->integer('is_active')->default(1)->after('email_verified_at');
            $table->integer('is_admin')->default(0)->after('is_active');
            $table->string('role')->default('user')->after('is_admin');
            $table->json('permissions')->nullable()->after('role');
            $table->integer('ministry')->nullable()->after('permissions');
            $table->integer('department')->nullable()->after('ministry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
