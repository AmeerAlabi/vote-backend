<?php

use App\Models\Admin;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\Vote;

describe('POST /api/elections', function () {
    it('creates an election and returns a voting link on the frontend host', function () {
        $admin = actingAsAdmin();

        $response = $this->postJson('/api/elections', [
            'title' => 'Student Council',
            'description' => 'Vote for president',
            'allowedDomains' => ['@School.edu', 'student.school.edu', 'school.edu'],
        ]);

        $response->assertCreated()->assertJson(['message' => 'Election created successfully']);

        $election = Election::find($response->json('electionId'));
        expect($election->admin_id)->toBe($admin->id)
            ->and($election->allowed_domains)->toBe(['school.edu', 'student.school.edu'])
            ->and($election->active)->toBeTrue()
            ->and($response->json('votingLink'))->toBe("https://vote.example.test/vote/{$election->id}");
    });

    it('requires at least one well-formed domain', function () {
        actingAsAdmin();

        $this->postJson('/api/elections', ['title' => 't', 'description' => 'd', 'allowedDomains' => []])
            ->assertStatus(400);

        $this->postJson('/api/elections', ['title' => 't', 'description' => 'd', 'allowedDomains' => ['not a domain']])
            ->assertStatus(400)
            ->assertJsonValidationErrorFor('allowedDomains.0', responseKey: 'errors');
    });

    it('requires an admin token', function () {
        $this->postJson('/api/elections', ['title' => 't', 'description' => 'd', 'allowedDomains' => ['school.edu']])
            ->assertUnauthorized();
    });

    it('accepts the legacy x-auth-token header', function () {
        $admin = Admin::factory()->create();

        $this->withHeader('x-auth-token', $admin->issueToken())
            ->getJson('/api/elections')
            ->assertOk();
    });
});

describe('GET /api/elections', function () {
    it('lists only the elections of the authenticated admin, newest first', function () {
        $admin = actingAsAdmin();
        $older = Election::factory()->for($admin)->create(['created_at' => now()->subDay()]);
        $newer = Election::factory()->for($admin)->create();
        Election::factory()->create(); // someone else's

        $this->getJson('/api/elections')
            ->assertOk()
            ->assertJsonCount(2, 'elections')
            ->assertJsonPath('elections.0.id', $newer->id)
            ->assertJsonPath('elections.1.id', $older->id);
    });
});

describe('GET /api/elections/{election}', function () {
    it('is public and includes the candidates', function () {
        $election = Election::factory()->create();
        Candidate::factory()->count(2)->for($election)->create();

        $this->getJson("/api/elections/{$election->id}")
            ->assertOk()
            ->assertJsonPath('election.id', $election->id)
            ->assertJsonPath('election.allowedDomains', ['school.edu'])
            ->assertJsonCount(2, 'candidates')
            ->assertJsonStructure(['candidates' => [['id', 'name', 'bio', 'photoUrl']]]);
    });

    it('returns a friendly 404 for unknown or malformed ids', function () {
        $this->getJson('/api/elections/'.fake()->uuid())
            ->assertNotFound()
            ->assertJson(['message' => 'Election not found']);

        $this->getJson('/api/elections/not-a-uuid')->assertNotFound();
    });
});

describe('PATCH /api/elections/{election}', function () {
    it('updates the fields that were sent', function () {
        $admin = actingAsAdmin();
        $election = Election::factory()->for($admin)->create(['title' => 'Old']);

        $this->patchJson("/api/elections/{$election->id}", ['title' => 'New', 'allowedDomains' => ['@new.edu']])
            ->assertOk()
            ->assertJsonPath('election.title', 'New')
            ->assertJsonPath('election.allowedDomains', ['new.edu']);

        expect($election->fresh()->description)->toBe($election->description);
    });

    it('forbids other admins', function () {
        actingAsAdmin();
        $election = Election::factory()->create();

        $this->patchJson("/api/elections/{$election->id}", ['title' => 'Hijack'])
            ->assertForbidden()
            ->assertJson(['message' => 'Not authorized to update this election']);
    });
});

describe('PATCH /api/elections/{election}/status', function () {
    it('toggles the active flag', function () {
        $admin = actingAsAdmin();
        $election = Election::factory()->for($admin)->create();

        $this->patchJson("/api/elections/{$election->id}/status", ['active' => false])
            ->assertOk()
            ->assertJson(['message' => 'Election deactivated successfully', 'active' => false]);

        expect($election->fresh()->active)->toBeFalse();
    });

    it('rejects a non-boolean', function () {
        $admin = actingAsAdmin();
        $election = Election::factory()->for($admin)->create();

        $this->patchJson("/api/elections/{$election->id}/status", ['active' => 'yes please'])
            ->assertStatus(400)
            ->assertJson(['message' => 'Active status must be a boolean']);
    });
});

describe('DELETE /api/elections/{election}', function () {
    it('cascades to candidates and votes', function () {
        $admin = actingAsAdmin();
        $election = Election::factory()->for($admin)->create();
        $candidate = Candidate::factory()->for($election)->create();
        Vote::factory()->for($candidate)->create();

        $this->deleteJson("/api/elections/{$election->id}")->assertOk();

        expect(Election::count())->toBe(0)
            ->and(Candidate::count())->toBe(0)
            ->and(Vote::count())->toBe(0);
    });

    it('forbids other admins', function () {
        actingAsAdmin();
        $election = Election::factory()->create();

        $this->deleteJson("/api/elections/{$election->id}")->assertForbidden();
        expect($election->fresh())->not->toBeNull();
    });
});

describe('GET /api/elections/{election}/results', function () {
    it('returns vote counts sorted highest first', function () {
        $admin = actingAsAdmin();
        $election = Election::factory()->for($admin)->create();
        [$a, $b, $c] = Candidate::factory()->count(3)->for($election)->create();
        Vote::factory()->count(3)->for($b)->create();
        Vote::factory()->count(1)->for($c)->create();

        $this->getJson("/api/elections/{$election->id}/results")
            ->assertOk()
            ->assertJson(['electionId' => $election->id, 'title' => $election->title, 'totalVotes' => 4])
            ->assertJsonPath('results.0.candidateId', $b->id)
            ->assertJsonPath('results.0.voteCount', 3)
            ->assertJsonPath('results.1.candidateId', $c->id)
            ->assertJsonPath('results.2.candidateId', $a->id)
            ->assertJsonPath('results.2.voteCount', 0);
    });

    it('is restricted to the owner', function () {
        actingAsAdmin();
        $election = Election::factory()->create();

        $this->getJson("/api/elections/{$election->id}/results")
            ->assertForbidden()
            ->assertJson(['message' => 'Not authorized to view results of this election']);
    });
});
