<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Departments, and the units inside them.
 *
 * `department_heads` was named for the one thing it did when it was made —
 * tie a department to the role that heads it — but it has been the list of
 * departments all along, and it is about to own the units as well. So it
 * takes the name it earned.
 *
 * Units start empty. The `division` column looks like it holds them, but it
 * holds offices — "Calabar HQ", "Akamkpa LGA Office" — which are places
 * rather than desks, and belong to no department. An officer's unit is a
 * separate thing, recorded against them once there are units to choose.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('department_heads', 'departments');

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            // A unit is named once within its department. The same name may
            // appear in two departments — "Registry" means a different desk
            // in each.
            $table->unique(['department_id', 'name'], 'one_unit_name_per_department');
        });

        // Where an officer's unit is recorded. Not `division`, which holds
        // the office they sit in rather than the desk they hold.
        Schema::table('basic_informations', function (Blueprint $table) {
            $table->string('unit')->nullable()->after('division');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('basic_informations', 'unit')) {
            Schema::table('basic_informations', function (Blueprint $table) {
                $table->dropColumn('unit');
            });
        }

        Schema::dropIfExists('units');
        Schema::rename('departments', 'department_heads');
    }
};
