<?php

use App\Models\Candidate;
use App\Models\Election;
use App\Models\Vote;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

const CLOUDINARY_URL = 'https://res.cloudinary.com/test-cloud/image/upload/v1/elections/photo.jpg';

function fakeCloudinary(): void
{
    Http::fake([
        'api.cloudinary.com/v1_1/test-cloud/image/upload' => Http::response(['secure_url' => CLOUDINARY_URL]),
    ]);
}

describe('POST /api/elections/{election}/candidates', function () {
    it('uploads the photo to Cloudinary with a signed request and stores the URL', function () {
        fakeCloudinary();
        $admin = actingAsAdmin();
        $election = Election::factory()->for($admin)->create();

        $response = $this->post("/api/elections/{$election->id}/candidates", [
            'name' => 'Jane Doe',
            'bio' => 'Passionate leader',
            'photo' => UploadedFile::fake()->image('jane.png', 300, 300),
        ]);

        $response->assertCreated()
            ->assertJsonPath('candidate.name', 'Jane Doe')
            ->assertJsonPath('candidate.photoUrl', CLOUDINARY_URL)
            ->assertJsonPath('candidate.electionId', $election->id);

        expect(Candidate::find($response->json('candidateId'))->photo_url)->toBe(CLOUDINARY_URL);

        Http::assertSent(function ($request) use ($election) {
            $fields = collect($request->data())->keyBy('name')->map->contents;

            return $fields['folder'] === "elections/{$election->id}"
                && $fields['api_key'] === 'test-key'
                && $fields['signature'] === sha1("folder=elections/{$election->id}&timestamp={$fields['timestamp']}test-secret")
                && $request->hasFile('file');
        });
    });

    it('requires name, bio and an image photo', function () {
        $admin = actingAsAdmin();
        $election = Election::factory()->for($admin)->create();

        $this->postJson("/api/elections/{$election->id}/candidates", ['name' => 'Jane', 'bio' => 'x'])
            ->assertStatus(400)
            ->assertJson(['message' => 'Please provide all required fields (name, bio, and photo)']);

        $this->post("/api/elections/{$election->id}/candidates", [
            'name' => 'Jane',
            'bio' => 'x',
            'photo' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json'])
            ->assertStatus(400)
            ->assertJson(['message' => 'Only JPEG, JPG, and PNG images are allowed']);

        expect(Candidate::count())->toBe(0);
    });

    it('surfaces a Cloudinary failure as a 500 without creating the candidate', function () {
        Http::fake(['api.cloudinary.com/*' => Http::response(['error' => ['message' => 'Invalid Signature']], 401)]);
        $admin = actingAsAdmin();
        $election = Election::factory()->for($admin)->create();

        $this->post("/api/elections/{$election->id}/candidates", [
            'name' => 'Jane',
            'bio' => 'x',
            'photo' => UploadedFile::fake()->image('jane.jpg'),
        ], ['Accept' => 'application/json'])
            ->assertStatus(500)
            ->assertJson(['message' => 'Error processing file upload', 'error' => 'Invalid Signature']);

        expect(Candidate::count())->toBe(0);
    });

    it('forbids admins who do not own the election', function () {
        fakeCloudinary();
        actingAsAdmin();
        $election = Election::factory()->create();

        $this->post("/api/elections/{$election->id}/candidates", [
            'name' => 'Jane',
            'bio' => 'x',
            'photo' => UploadedFile::fake()->image('jane.jpg'),
        ], ['Accept' => 'application/json'])
            ->assertForbidden()
            ->assertJson(['message' => 'Not authorized to modify this election']);

        Http::assertNothingSent();
    });
});

describe('GET candidates', function () {
    it('lists candidates for an election publicly', function () {
        $election = Election::factory()->create();
        Candidate::factory()->count(2)->for($election)->create();
        Candidate::factory()->create();

        $this->getJson("/api/elections/{$election->id}/candidates")
            ->assertOk()
            ->assertJson(['electionId' => $election->id, 'count' => 2])
            ->assertJsonCount(2, 'candidates');
    });

    it('lists all candidates across the admin\'s own elections only', function () {
        $admin = actingAsAdmin();
        $mine = Election::factory()->for($admin)->create();
        Candidate::factory()->count(3)->for($mine)->create();
        Candidate::factory()->count(2)->create();

        $this->getJson('/api/elections/candidates')
            ->assertOk()
            ->assertJson(['count' => 3])
            ->assertJsonPath('candidates.0.electionTitle', $mine->title);
    });
});

describe('PATCH /api/elections/{election}/candidates/{candidate}', function () {
    it('updates text fields without touching the photo', function () {
        $admin = actingAsAdmin();
        $candidate = Candidate::factory()->for(Election::factory()->for($admin))->create(['photo_url' => 'https://old/photo.jpg']);

        $this->patchJson("/api/elections/{$candidate->election_id}/candidates/{$candidate->id}", ['bio' => 'Updated bio'])
            ->assertOk()
            ->assertJsonPath('candidate.bio', 'Updated bio')
            ->assertJsonPath('candidate.photoUrl', 'https://old/photo.jpg');
    });

    it('replaces the photo when one is sent (via POST for multipart clients)', function () {
        fakeCloudinary();
        $admin = actingAsAdmin();
        $candidate = Candidate::factory()->for(Election::factory()->for($admin))->create();

        $this->post("/api/elections/{$candidate->election_id}/candidates/{$candidate->id}", [
            'photo' => UploadedFile::fake()->image('new.jpg'),
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('candidate.photoUrl', CLOUDINARY_URL);
    });

    it('404s when the candidate belongs to a different election', function () {
        $admin = actingAsAdmin();
        $election = Election::factory()->for($admin)->create();
        $foreign = Candidate::factory()->for(Election::factory()->for($admin))->create();

        $this->patchJson("/api/elections/{$election->id}/candidates/{$foreign->id}", ['name' => 'x'])
            ->assertNotFound()
            ->assertJson(['message' => 'Candidate not found']);
    });
});

describe('DELETE /api/elections/{election}/candidates/{candidate}', function () {
    it('deletes the candidate and their votes', function () {
        $admin = actingAsAdmin();
        $candidate = Candidate::factory()->for(Election::factory()->for($admin))->create();
        Vote::factory()->count(2)->for($candidate)->create();

        $this->deleteJson("/api/elections/{$candidate->election_id}/candidates/{$candidate->id}")
            ->assertOk()
            ->assertJson(['message' => 'Candidate deleted successfully']);

        expect(Candidate::count())->toBe(0)->and(Vote::count())->toBe(0);
    });

    it('forbids non-owners', function () {
        actingAsAdmin();
        $candidate = Candidate::factory()->create();

        $this->deleteJson("/api/elections/{$candidate->election_id}/candidates/{$candidate->id}")->assertForbidden();
        expect($candidate->fresh())->not->toBeNull();
    });
});
