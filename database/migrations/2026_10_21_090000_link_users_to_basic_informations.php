<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A dashboard account belongs to an officer already on the staff register, so
 * the personal details live once on basic_informations and the users table
 * keeps only what is about the account: credentials, role and permissions.
 *
 * Existing accounts are matched to a staff record by name. Anything that
 * cannot be matched is left unlinked rather than given invented details —
 * basic_informations requires a date of birth and phone number, and guessing
 * either would be worse than an administrator linking the account by hand.
 */
return new class extends Migration
{
    private const MOVED = ['surname', 'firstname', 'othernames', 'ministry', 'department'];

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->char('basic_information_ulid', 26)->nullable()->after('id');

            // One dashboard account per officer. Repeated NULLs are allowed,
            // which is what lets an unmatched legacy account sit here.
            $table->unique('basic_information_ulid');
        });

        $this->linkExistingAccounts();

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(self::MOVED);
        });
    }

    private function linkExistingAccounts(): void
    {
        $staff = DB::table('basic_informations')->get(['ulid', 'surname', 'firstname']);

        foreach (DB::table('users')->get() as $user) {
            $match = $staff->first(fn ($s) => $this->same($s->surname, $user->surname)
                                          && $this->same($s->firstname, $user->firstname));

            if ($match === null) {
                continue;
            }

            DB::table('users')->where('id', $user->id)
                ->update(['basic_information_ulid' => $match->ulid]);
        }
    }

    private function same(?string $a, ?string $b): bool
    {
        return strcasecmp(trim((string) $a), trim((string) $b)) === 0;
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('surname')->nullable();
            $table->string('firstname')->nullable();
            $table->string('othernames')->nullable();
            $table->integer('ministry')->nullable();
            $table->integer('department')->nullable();
        });

        // Copy the names back off the staff record so nothing is lost.
        foreach (DB::table('users')->whereNotNull('basic_information_ulid')->get() as $user) {
            $staff = DB::table('basic_informations')
                ->where('ulid', $user->basic_information_ulid)
                ->first(['surname', 'firstname', 'othernames']);

            if ($staff) {
                DB::table('users')->where('id', $user->id)->update([
                    'surname' => $staff->surname,
                    'firstname' => $staff->firstname,
                    'othernames' => $staff->othernames,
                ]);
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['basic_information_ulid']);
            $table->dropColumn('basic_information_ulid');
        });
    }
};
