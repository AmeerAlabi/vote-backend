<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Election\StoreElectionRequest;
use App\Http\Requests\Election\UpdateElectionRequest;
use App\Http\Requests\Election\UpdateElectionStatusRequest;
use App\Http\Resources\CandidateResource;
use App\Http\Resources\ElectionResource;
use App\Models\Election;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ElectionController extends Controller
{
    /**
     * Get all elections
     *
     * Lists the elections created by the authenticated admin, newest first.
     */
    public function index(Request $request): JsonResponse
    {
        $elections = $request->user()->elections()->latest()->get();

        return response()->json(['elections' => ElectionResource::collection($elections)]);
    }

    /**
     * Create a new election
     *
     * Returns the election id and the public voting link to share with voters.
     */
    public function store(StoreElectionRequest $request): JsonResponse
    {
        $election = $request->user()->elections()->create($request->electionAttributes());

        return response()->json([
            'message' => 'Election created successfully',
            'electionId' => $election->id,
            'votingLink' => $this->votingLink($election),
            'election' => new ElectionResource($election),
        ], 201);
    }

    /**
     * Get election details
     *
     * Public: what a voter sees before voting, including the candidates.
     */
    public function show(Election $election): JsonResponse
    {
        return response()->json([
            'election' => new ElectionResource($election),
            'candidates' => CandidateResource::collection($election->candidates()->oldest()->get()),
        ]);
    }

    /**
     * Update election details
     */
    public function update(UpdateElectionRequest $request, Election $election): JsonResponse
    {
        Gate::authorize('update', $election);

        $election->update($request->electionAttributes());

        return response()->json([
            'message' => 'Election updated successfully',
            'election' => new ElectionResource($election),
        ]);
    }

    /**
     * Update election status
     *
     * Activates or deactivates voting. Inactive elections reject verification and votes.
     */
    public function updateStatus(UpdateElectionStatusRequest $request, Election $election): JsonResponse
    {
        Gate::authorize('update', $election);

        $active = $request->boolean('active');
        $election->update(['active' => $active]);

        return response()->json([
            'message' => 'Election '.($active ? 'activated' : 'deactivated').' successfully',
            'electionId' => $election->id,
            'active' => $active,
        ]);
    }

    /**
     * Delete an election
     *
     * Removes the election together with its candidates, votes and voter sessions.
     */
    public function destroy(Election $election): JsonResponse
    {
        Gate::authorize('delete', $election);

        $election->delete();

        return response()->json(['message' => 'Election deleted successfully']);
    }

    /**
     * Get election results
     *
     * Vote counts per candidate, highest first. Owner only — the public
     * monitor endpoint serves the same data once an admin shares its link.
     */
    public function results(Election $election): JsonResponse
    {
        Gate::authorize('viewResults', $election);

        $candidates = $election->candidates()->withCount('votes')->orderByDesc('votes_count')->get();

        return response()->json([
            'electionId' => $election->id,
            'title' => $election->title,
            'totalVotes' => $candidates->sum('votes_count'),
            'results' => $candidates->map(fn ($candidate) => [
                'candidateId' => $candidate->id,
                'name' => $candidate->name,
                'voteCount' => $candidate->votes_count,
            ]),
        ]);
    }

    private function votingLink(Election $election): string
    {
        return rtrim(config('app.frontend_url'), '/')."/vote/{$election->id}";
    }
}
