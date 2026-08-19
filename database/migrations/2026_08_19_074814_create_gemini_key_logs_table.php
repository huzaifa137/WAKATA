<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gemini_key_logs', function (Blueprint $table) {
            $table->id();
            $table->string('key_label');      // e.g. "key_1 (...ab12)"
            $table->string('status');         // success | failed | skipped
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('error_reason')->nullable(); // e.g. ACCESS_TOKEN_TYPE_UNSUPPORTED
            $table->unsignedInteger('latency_ms')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['key_label', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gemini_key_logs');
    }
};