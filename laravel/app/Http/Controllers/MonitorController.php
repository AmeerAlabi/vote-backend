<?php

namespace App\Http\Controllers;

use App\Models\Election;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

/**
 * Public live-results monitor. Anyone with the link can watch; the page
 * polls the JSON endpoint every few seconds.
 */
class MonitorController extends Controller
{
    /**
     * Live election results
     *
     * Public. Vote counts and percentages per candidate, highest first.
     */
    public function results(Election $election): JsonResponse
    {
        $candidates = $election->candidates()->withCount('votes')->orderByDesc('votes_count')->orderBy('name')->get();
        $totalVotes = $candidates->sum('votes_count');

        return response()->json([
            'success' => true,
            'election' => [
                'id' => $election->id,
                'title' => $election->title,
                'description' => $election->description,
                'active' => $election->active,
                'createdAt' => $election->created_at,
            ],
            'results' => $candidates->map(fn ($candidate) => [
                'candidateId' => $candidate->id,
                'name' => $candidate->name,
                'bio' => $candidate->bio,
                'photoUrl' => $candidate->photo_url,
                'voteCount' => $candidate->votes_count,
                'percentage' => $totalVotes > 0 ? round($candidate->votes_count / $totalVotes * 100, 2) : 0,
            ]),
            'totalVotes' => $totalVotes,
            'lastUpdated' => now(),
        ]);
    }

    public function page(Election $election): View
    {
        return view('monitor', [
            'election' => $election,
            'resultsUrl' => route('monitor.results', $election),
            'pollIntervalMs' => 5000,
        ]);
    }
}
