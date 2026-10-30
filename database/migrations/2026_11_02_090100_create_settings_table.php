<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Things about this command that belong to the installation rather than to the
 * code: its name, where it sits, what its telephone is. They print at the head
 * of every letter the system raises, so they have to be recorded somewhere an
 * administrator can correct them.
 *
 * Key and value, because there are few of them and they are read by name.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
