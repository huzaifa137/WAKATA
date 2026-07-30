<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A "sync device" is one offline installation (a school's XAMPP machine,
 * or a town field-office laptop) that is allowed to push/pull data
 * to/from this central server. Each device gets its own long random
 * token so access can be revoked per-school without affecting others.
 *
 * This table lives centrally. School installs never issue tokens
 * themselves — they only store the one token they were given.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_devices', function (Blueprint $table) {
            $table->id();
            $table->string('school_number')->index();
            $table->string('device_name');
            $table->string('token_hash', 64)->unique();
            $table->enum('role', ['school', 'central'])->default('school');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_devices');
    }
};
