<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Candidate\StoreCandidateRequest;
use App\Http\Requests\Candidate\UpdateCandidateRequest;
use App\Http\Resources\CandidateResource;
use App\Models\Candidate;
use App\Models\Election;
use App\Services\CloudinaryUploader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CandidateController extends Controller
{
    public function __construct(private readonly CloudinaryUploader $uploader) {}

    /**
     * Get all candidates
     *
     * Every candidate across the authenticated admin's elections, newest first.
     */
    public function index(Request $request): JsonResponse
    {
        $candidates = Candidate::query()
            ->whereRelation('election', 'admin_id', $request->user()->id)
            ->with('election')
            ->latest()
            ->get();

        return response()->json([
            'candidates' => CandidateResource::collection($candidates),
            'count' => $candidates->count(),
        ]);
    }

    /**
     * Get candidates for an election
     *
     * Public.
     */
    public function indexForElection(Election $election): JsonResponse
    {
        $candidates = $election->candidates()->latest()->get();

        return response()->json([
            'electionId' => $election->id,
            'candidates' => CandidateResource::collection($candidates),
            'count' => $candidates->count(),
        ]);
    }

    /**
     * Add a candidate to an election
     *
     * Multipart form: `name`, `bio` and a JPEG/PNG `photo` (max 5MB), which is stored on Cloudinary.
     */
    public function store(StoreCandidateRequest $request, Election $election): JsonResponse
    {
        Gate::authorize('manageCandidates', $election);

        $candidate = $election->candidates()->create([
            ...$request->safe()->only(['name', 'bio']),
            'photo_url' => $this->uploader->upload($request->file('photo'), "elections/{$election->id}"),
        ]);

        return response()->json([
            'message' => 'Candidate added successfully',
            'candidateId' => $candidate->id,
            'candidate' => new CandidateResource($candidate),
        ], 201);
    }

    /**
     * Update candidate details
     *
     * Any of `name`, `bio`, `photo` may be sent; omitted fields are left unchanged.
     */
    public function update(UpdateCandidateRequest $request, Election $election, Candidate $candidate): JsonResponse
    {
        Gate::authorize('manageCandidates', $election);

        $attributes = $request->safe()->only(['name', 'bio']);

        if ($request->hasFile('photo')) {
            $attributes['photo_url'] = $this->uploader->upload($request->file('photo'), "elections/{$election->id}");
        }

        $candidate->update($attributes);

        return response()->json([
            'message' => 'Candidate updated successfully',
            'candidate' => new CandidateResource($candidate),
        ]);
    }

    /**
     * Delete a candidate
     *
     * Also removes any votes cast for the candidate.
     */
    public function destroy(Election $election, Candidate $candidate): JsonResponse
    {
        Gate::authorize('manageCandidates', $election);

        $candidate->delete();

        return response()->json(['message' => 'Candidate deleted successfully']);
    }
}
