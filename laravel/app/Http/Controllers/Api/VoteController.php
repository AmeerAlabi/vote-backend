<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vote\CastVoteRequest;
use App\Http\Requests\Vote\ConfirmCodeRequest;
use App\Http\Requests\Vote\VerifyEmailRequest;
use App\Mail\VoterVerificationCode;
use App\Models\Election;
use App\Models\VoterSession;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * The three-step voting flow: verify email → confirm code → cast vote.
 *
 * Step 3 is authenticated by a short-lived Sanctum token issued in step 2
 * and scoped, via a token ability, to exactly one election.
 */
class VoteController extends Controller
{
    /**
     * Verify voter email
     *
     * Emails a 6-digit code if the address belongs to an allowed domain and has not voted yet.
     */
    public function verify(VerifyEmailRequest $request, Election $election): JsonResponse
    {
        if (! $election->active) {
            return response()->json(['message' => 'This election is not active'], 400);
        }

        $email = $request->email();

        if (! $election->allowsEmail($email)) {
            return response()->json(['message' => 'Email domain not allowed for this election'], 400);
        }

        if ($election->votes()->where('voter_email', $email)->exists()) {
            return response()->json(['message' => 'You have already voted in this election'], 400);
        }

        // One session per email per election; a repeat request rotates the code.
        $session = $election->voterSessions()->firstOrNew(['email' => $email]);
        $code = $session->issueCode();

        Mail::to($email)->send(new VoterVerificationCode($code, $election->title));

        return response()->json([
            'message' => 'Verification code sent to your email',
            'sessionId' => $session->id,
        ]);
    }

    /**
     * Confirm voter verification code
     *
     * Exchanges the emailed code for a vote token valid for 15 minutes.
     */
    public function confirm(ConfirmCodeRequest $request, Election $election): JsonResponse
    {
        $session = $election->voterSessions()->find($request->string('sessionId'));

        if ($session === null) {
            return response()->json(['message' => 'Invalid session'], 400);
        }

        if ($session->isExpired()) {
            return response()->json(['message' => 'Verification code has expired. Please request a new one.'], 400);
        }

        if (! $session->codeMatches($request->string('code'))) {
            return response()->json(['message' => 'Invalid verification code'], 400);
        }

        $token = $session->markVerifiedAndIssueVoteToken();

        return response()->json([
            'message' => 'Verification successful',
            'token' => $token,
            'voteToken' => $token, // kept for clients written against the original API
        ]);
    }

    /**
     * Cast a vote
     *
     * Requires the vote token from `confirm`. One vote per email per election is
     * enforced by a database constraint, so concurrent submissions cannot double-vote.
     */
    public function cast(CastVoteRequest $request, Election $election): JsonResponse
    {
        /** @var VoterSession $session */
        $session = $request->user('voter');

        if ($session->election_id !== $election->id || ! $session->tokenCan(VoterSession::voteAbility($election->id))) {
            return response()->json(['message' => 'Vote token is not valid for this election'], 403);
        }

        if (! $session->isVerified()) {
            return response()->json(['message' => 'Email has not been verified'], 403);
        }

        if (! $election->active) {
            return response()->json(['message' => 'This election is not active'], 400);
        }

        $candidate = $election->candidates()->find($request->string('candidateId'));

        if ($candidate === null) {
            return response()->json(['message' => 'Candidate not found in this election'], 404);
        }

        try {
            $vote = $election->votes()->create([
                'candidate_id' => $candidate->id,
                'voter_email' => $session->email,
            ]);
        } catch (UniqueConstraintViolationException) {
            return response()->json(['message' => 'You have already voted in this election'], 400);
        }

        // The session has served its purpose; this also revokes the vote token.
        $session->tokens()->delete();
        $session->delete();

        return response()->json([
            'message' => 'Vote cast successfully',
            'voteId' => $vote->id,
            'monitorUrl' => route('monitor.page', $election),
        ]);
    }
}
