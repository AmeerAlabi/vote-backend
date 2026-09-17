<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Election;
use Illuminate\Auth\Access\Response;

/**
 * Every admin-side mutation or results view is restricted to the admin who
 * created the election. Public reads (details, candidates) need no policy.
 */
class ElectionPolicy
{
    public function update(Admin $admin, Election $election): Response
    {
        return $this->owns($admin, $election, 'Not authorized to update this election');
    }

    public function delete(Admin $admin, Election $election): Response
    {
        return $this->owns($admin, $election, 'Not authorized to delete this election');
    }

    public function viewResults(Admin $admin, Election $election): Response
    {
        return $this->owns($admin, $election, 'Not authorized to view results of this election');
    }

    public function manageCandidates(Admin $admin, Election $election): Response
    {
        return $this->owns($admin, $election, 'Not authorized to modify this election');
    }

    private function owns(Admin $admin, Election $election, string $denial): Response
    {
        return $election->admin_id === $admin->id
            ? Response::allow()
            : Response::deny($denial);
    }
}
