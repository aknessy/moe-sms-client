<?php

use App\Models\BasicInformation;
use App\Models\DepartmentHead;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which role heads which department.
 *
 * An application has to reach somebody, and the only thing on a staff record
 * that says where they work is a free-text department. The HOD roles are named
 * after directorates, which do not match those strings — "HOD VEP" against
 * "Voter Education & Publicity" — so the two are tied together here rather
 * than guessed at in code.
 *
 * Seeded only where the match is beyond doubt. Everything else is left for an
 * administrator to set in Settings, because an application routed to the wrong
 * desk is worse than one that goes to the administrators.
 */
return new class extends Migration
{
    /** Department as it appears on the staff record => the role that heads it. */
    private const OBVIOUS = [
        'ICT & Voter Registry' => 'HOD ICT',
        'Voter Education & Publicity' => 'HOD VEP',
        'Operations' => 'HOD EOPs',
    ];

    public function up(): void
    {
        Schema::create('department_heads', function (Blueprint $table) {
            $table->id();
            $table->string('department')->unique();
            // The role, by name, as users.role reads it. Null until someone
            // says which role heads this department.
            $table->string('role')->nullable();
            $table->timestamps();
        });

        // A row per department actually in use, so Settings has something to
        // show without an administrator having to type the names in.
        $departments = BasicInformation::query()
            ->whereNotNull('department')
            ->distinct()
            ->pluck('department');

        foreach ($departments as $department) {
            DepartmentHead::create([
                'department' => $department,
                'role' => self::OBVIOUS[$department] ?? null,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('department_heads');
    }
};
