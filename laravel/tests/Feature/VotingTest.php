<?php

use App\Mail\VoterVerificationCode;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\Vote;
use App\Models\VoterSession;
use Database\Factories\VoterSessionFactory;
use Illuminate\Support\Facades\Mail;

beforeEach(fn () => Mail::fake());

describe('POST /api/vote/{election}/verify', function () {
    it('creates a session and emails a code for an allowed domain', function () {
        $election = Election::factory()->create(['allowed_domains' => ['school.edu']]);

        $response = $this->postJson("/api/vote/{$election->id}/verify", ['email' => 'Student@School.edu']);

        $response->assertOk()->assertJson(['message' => 'Verification code sent to your email']);

        $session = VoterSession::find($response->json('sessionId'));
        expect($session->email)->toBe('student@school.edu')
            ->and($session->election_id)->toBe($election->id)
            ->and($session->isVerified())->toBeFalse();

        Mail::assertQueued(VoterVerificationCode::class, fn ($mail) => $mail->hasTo('student@school.edu')
            && $mail->electionTitle === $election->title);
    });

    it('requires an exact domain match', function () {
        $election = Election::factory()->create(['allowed_domains' => ['school.edu']]);

        $this->postJson("/api/vote/{$election->id}/verify", ['email' => 'attacker@evilschool.edu'])
            ->assertStatus(400)
            ->assertJson(['message' => 'Email domain not allowed for this election']);

        $this->postJson("/api/vote/{$election->id}/verify", ['email' => 'attacker@school.edu.evil.com'])
            ->assertStatus(400);

        Mail::assertNothingQueued();
    });

    it('rejects inactive elections', function () {
        $election = Election::factory()->inactive()->create();

        $this->postJson("/api/vote/{$election->id}/verify", ['email' => 'student@school.edu'])
            ->assertStatus(400)
            ->assertJson(['message' => 'This election is not active']);
    });

    it('rejects emails that already voted', function () {
        $candidate = Candidate::factory()->create();
        Vote::factory()->for($candidate)->create(['voter_email' => 'student@school.edu']);

        $this->postJson("/api/vote/{$candidate->election_id}/verify", ['email' => 'student@school.edu'])
            ->assertStatus(400)
            ->assertJson(['message' => 'You have already voted in this election']);
    });

    it('re-uses the session and rotates the code on a repeat request', function () {
        $election = Election::factory()->create();

        $first = $this->postJson("/api/vote/{$election->id}/verify", ['email' => 'student@school.edu'])->json('sessionId');
        $second = $this->postJson("/api/vote/{$election->id}/verify", ['email' => 'student@school.edu'])->json('sessionId');

        expect($second)->toBe($first)->and(VoterSession::count())->toBe(1);
        Mail::assertQueuedCount(2);
    });
});

describe('POST /api/vote/{election}/confirm', function () {
    it('returns a vote token for the right code', function () {
        $session = VoterSession::factory()->create();

        $response = $this->postJson("/api/vote/{$session->election_id}/confirm", [
            'sessionId' => $session->id,
            'code' => VoterSessionFactory::CODE,
        ]);

        $response->assertOk()->assertJson(['message' => 'Verification successful']);
        expect($response->json('token'))->toBe($response->json('voteToken'))->not->toBeEmpty()
            ->and($session->fresh()->isVerified())->toBeTrue();
    });

    it('rejects a wrong code, a foreign session and an expired session', function () {
        $session = VoterSession::factory()->create();

        $this->postJson("/api/vote/{$session->election_id}/confirm", ['sessionId' => $session->id, 'code' => '000000'])
            ->assertStatus(400)
            ->assertJson(['message' => 'Invalid verification code']);

        $other = Election::factory()->create();
        $this->postJson("/api/vote/{$other->id}/confirm", ['sessionId' => $session->id, 'code' => VoterSessionFactory::CODE])
            ->assertStatus(400)
            ->assertJson(['message' => 'Invalid session']);

        $expired = VoterSession::factory()->expired()->create();
        $this->postJson("/api/vote/{$expired->election_id}/confirm", ['sessionId' => $expired->id, 'code' => VoterSessionFactory::CODE])
            ->assertStatus(400)
            ->assertJson(['message' => 'Verification code has expired. Please request a new one.']);
    });
});

describe('POST /api/vote/{election}', function () {
    it('records the vote, revokes the session and returns the monitor link', function () {
        $election = Election::factory()->create();
        $candidate = Candidate::factory()->for($election)->create();
        $session = actingAsVoter($election);

        $response = $this->postJson("/api/vote/{$election->id}", ['candidateId' => $candidate->id]);

        $response->assertOk()
            ->assertJson(['message' => 'Vote cast successfully'])
            ->assertJsonPath('monitorUrl', route('monitor.page', $election));

        $vote = Vote::find($response->json('voteId'));
        expect($vote->candidate_id)->toBe($candidate->id)
            ->and($vote->voter_email)->toBe($session->email)
            ->and(VoterSession::find($session->id))->toBeNull();
    });

    it('works end to end with a real token from confirm, including the legacy header', function () {
        $election = Election::factory()->create();
        $candidate = Candidate::factory()->for($election)->create();
        $session = VoterSession::factory()->for($election)->create();

        $token = $this->postJson("/api/vote/{$election->id}/confirm", [
            'sessionId' => $session->id,
            'code' => VoterSessionFactory::CODE,
        ])->json('voteToken');

        $this->withHeader('x-vote-token', $token)
            ->postJson("/api/vote/{$election->id}", ['candidateId' => $candidate->id])
            ->assertOk();

        // The token died with the session. (Guards cache the user per app
        // instance in tests, so reset them to simulate a fresh request.)
        app('auth')->forgetGuards();
        $this->withToken($token)
            ->postJson("/api/vote/{$election->id}", ['candidateId' => $candidate->id])
            ->assertUnauthorized();
    });

    it('refuses a token issued for a different election', function () {
        $home = Election::factory()->create();
        $away = Election::factory()->create();
        $candidate = Candidate::factory()->for($away)->create();
        actingAsVoter($home);

        $this->postJson("/api/vote/{$away->id}", ['candidateId' => $candidate->id])
            ->assertForbidden()
            ->assertJson(['message' => 'Vote token is not valid for this election']);

        expect(Vote::count())->toBe(0);
    });

    it('refuses an admin token', function () {
        $election = Election::factory()->create();
        actingAsAdmin();

        $this->postJson("/api/vote/{$election->id}", ['candidateId' => fake()->uuid()])->assertUnauthorized();
    });

    it('refuses a candidate from another election', function () {
        $election = Election::factory()->create();
        $foreign = Candidate::factory()->create();
        actingAsVoter($election);

        $this->postJson("/api/vote/{$election->id}", ['candidateId' => $foreign->id])
            ->assertNotFound()
            ->assertJson(['message' => 'Candidate not found in this election']);
    });

    it('refuses inactive elections', function () {
        $election = Election::factory()->inactive()->create();
        $candidate = Candidate::factory()->for($election)->create();
        actingAsVoter($election);

        $this->postJson("/api/vote/{$election->id}", ['candidateId' => $candidate->id])
            ->assertStatus(400)
            ->assertJson(['message' => 'This election is not active']);
    });

    it('enforces one vote per email at the database level', function () {
        $election = Election::factory()->create();
        $candidate = Candidate::factory()->for($election)->create();
        $session = actingAsVoter($election);
        Vote::factory()->for($candidate)->create(['voter_email' => $session->email]);

        $this->postJson("/api/vote/{$election->id}", ['candidateId' => $candidate->id])
            ->assertStatus(400)
            ->assertJson(['message' => 'You have already voted in this election']);

        expect(Vote::count())->toBe(1);
    });
});

describe('monitor', function () {
    it('serves public results with percentages', function () {
        $election = Election::factory()->create();
        [$a, $b] = Candidate::factory()->count(2)->for($election)->create();
        Vote::factory()->count(3)->for($a)->create();
        Vote::factory()->count(1)->for($b)->create();

        $this->getJson("/api/monitor/results/{$election->id}")
            ->assertOk()
            ->assertJson(['success' => true, 'totalVotes' => 4])
            ->assertJsonPath('election.id', $election->id)
            ->assertJsonPath('results.0.candidateId', $a->id)
            ->assertJsonPath('results.0.percentage', 75)
            ->assertJsonPath('results.1.percentage', 25);
    });

    it('renders the monitor page', function () {
        $election = Election::factory()->create(['title' => 'Prefect Election']);

        $this->get("/monitor/election/{$election->id}")
            ->assertOk()
            ->assertSee('Prefect Election')
            ->assertSee(route('monitor.results', $election));
    });
});

it('prunes expired voter sessions', function () {
    VoterSession::factory()->expired()->count(2)->create();
    VoterSession::factory()->create();

    $this->artisan('model:prune', ['--model' => [VoterSession::class]])->assertSuccessful();

    expect(VoterSession::count())->toBe(1);
});
