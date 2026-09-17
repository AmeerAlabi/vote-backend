<?php

namespace App\Http\Resources;

use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Candidate */
class CandidateResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'electionId' => $this->election_id,
            'name' => $this->name,
            'bio' => $this->bio,
            'photoUrl' => $this->photo_url,
            'createdAt' => $this->created_at,
            'electionTitle' => $this->whenLoaded('election', fn () => $this->election->title),
            'voteCount' => $this->whenCounted('votes'),
        ];
    }
}
