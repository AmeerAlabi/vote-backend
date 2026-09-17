<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('election_id')->constrained('elections')->cascadeOnDelete();
            $table->foreignUuid('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->string('voter_email');
            $table->timestamp('created_at')->useCurrent();

            // One vote per email per election, enforced by the database.
            $table->unique(['election_id', 'voter_email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
