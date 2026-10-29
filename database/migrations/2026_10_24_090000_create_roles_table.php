<?php

use Database\Seeders\RoleSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The roles an account may be given. Until now these lived in a constant on
 * the controller; holding them here is what lets permissions be attached to
 * them later.
 */
return new class extends Migration
{
    /**
     * Accounts already carry the slug form of the three original roles. The
     * table holds the names as they read on screen, so stored values are
     * brought into line with it.
     */
    private const RENAMED = [
        'administrator' => 'Administrator',
        'registry-officer' => 'Registry Officer',
        'user' => 'User',
    ];

    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->ulid('role_ulid')->unique();
            $table->string('role')->unique();
            // Null for the roles the system ships with; set for any added later.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
        });

        (new RoleSeeder)->run();

        foreach (self::RENAMED as $slug => $name) {
            DB::table('users')->where('role', $slug)->update(['role' => $name]);
        }
    }

    public function down(): void
    {
        foreach (self::RENAMED as $slug => $name) {
            DB::table('users')->where('role', $name)->update(['role' => $slug]);
        }

        Schema::dropIfExists('roles');
    }
};
