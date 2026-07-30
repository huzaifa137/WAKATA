<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every create/update/delete made to a Syncable model on a "school"
 * install gets queued here first. `sync:push` reads unsynced rows and
 * sends them to the central server. Rows are coalesced per model+key
 * so editing the same mark 10 times offline only ever queues once.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_outbox', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('model_type');
            $table->string('model_key');   // canonical JSON of the natural key, e.g. {"student_id":"...","subject_id":5}
            $table->string('operation');   // created | updated | deleted
            $table->longText('payload');   // JSON snapshot of the row's syncable attributes
            $table->string('school_number')->nullable();
            $table->string('device_name')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('synced_at')->nullable();

            $table->index(['model_type', 'model_key']);
            $table->index('synced_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_outbox');
    }
};
