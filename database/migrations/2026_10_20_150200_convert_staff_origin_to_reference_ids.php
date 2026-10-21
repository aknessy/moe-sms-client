<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * state_of_origin and lga_of_origin were always declared integer, but existing
 * rows hold the names as text (SQLite stores whatever it is given). Translate
 * those names to the matching states.id / lgas.id now that the lookups exist.
 * Anything that matches nothing is cleared rather than guessed at.
 */
return new class extends Migration
{
    public function up(): void
    {
        // An officer's origin may simply not be on file, and the wizard already
        // treats both as optional, so neither can stay NOT NULL.
        Schema::table('basic_informations', function (Blueprint $table) {
            $table->integer('state_of_origin')->nullable()->change();
            $table->integer('lga_of_origin')->nullable()->change();
        });

        $states = DB::table('states')->pluck('id', 'name');
        $lgas = DB::table('lgas')->get();

        foreach (DB::table('basic_informations')->get() as $row) {
            $update = [];

            $stateId = is_numeric($row->state_of_origin)
                ? (int) $row->state_of_origin
                : $update['state_of_origin'] = $this->matchState($states, $row->state_of_origin);

            if (! is_numeric($row->lga_of_origin)) {
                // Scoped to the officer's state: several states share an LGA
                // name (Kano and Nasarawa both have a "Nasarawa"), so matching
                // across the whole table would pick an arbitrary one.
                $update['lga_of_origin'] = $this->matchLga($lgas, $row->lga_of_origin, $stateId);
            }

            if ($update) {
                DB::table('basic_informations')->where('id', $row->id)->update($update);
            }
        }

        Schema::table('basic_informations', function (Blueprint $table) {
            $table->index('state_of_origin');
            $table->index('lga_of_origin');
        });
    }

    private function matchState($states, $name): ?int
    {
        if (blank($name)) {
            return null;
        }

        $needle = $this->normalise($name);

        foreach ($states as $stateName => $id) {
            if ($this->normalise($stateName) === $needle) {
                return $id;
            }
        }

        return null;
    }

    private function matchLga($lgas, $name, ?int $stateId): ?int
    {
        if (blank($name) || $stateId === null) {
            return null;
        }

        $needle = $this->normalise($name);

        foreach ($lgas as $lga) {
            if ($lga->state_id === $stateId && $this->normalise($lga->lga_name) === $needle) {
                return $lga->id;
            }
        }

        return null;
    }

    /** Fold case, spacing and hyphens so "Cross-River" matches "Cross River". */
    private function normalise(string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower($value));
    }

    public function down(): void
    {
        Schema::table('basic_informations', function (Blueprint $table) {
            $table->dropIndex(['state_of_origin']);
            $table->dropIndex(['lga_of_origin']);
        });
    }
};
