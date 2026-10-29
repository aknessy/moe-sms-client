<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a role may do. One row per permission granted, so a role holding
 * several has several rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            // The role this is granted to, by the reference roles are known by.
            $table->ulid('role_ulid');
            $table->string('permission');
            $table->timestamp('created_at')->nullable();

            $table->foreign('role_ulid')
                ->references('role_ulid')->on('roles')
                ->cascadeOnDelete();

            // Always read as "what may this role do", and a permission is
            // either granted or it is not — never granted twice.
            $table->index('role_ulid');
            $table->unique(['role_ulid', 'permission']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
