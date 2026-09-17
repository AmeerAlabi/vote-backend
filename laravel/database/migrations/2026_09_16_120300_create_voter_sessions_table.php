<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voter_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('election_id')->constrained('elections')->cascadeOnDelete();
            $table->string('email');
            $table->string('code_hash');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->unique(['election_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voter_sessions');
    }
};
