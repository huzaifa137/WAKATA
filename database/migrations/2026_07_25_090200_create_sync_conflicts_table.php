<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Written centrally whenever an incoming pushed change can't be applied
 * automatically — e.g. the central copy of that same row was already
 * changed (by another user, another school's admin correction, etc.)
 * after the offline edit was made. A human resolves these from
 * /sync/conflicts rather than data being silently overwritten.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_conflicts', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->string('model_key');
            $table->longText('incoming_payload');
            $table->longText('current_payload')->nullable();
            $table->string('school_number')->nullable();
            $table->string('device_name')->nullable();
            $table->string('status')->default('pending'); // pending | kept_incoming | kept_current
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_conflicts');
    }
};
