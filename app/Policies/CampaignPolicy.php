<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

class CampaignPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Campaign $campaign): bool
    {
        return $campaign->isMember($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model (and its contents: wiki, pins, notes).
     */
    public function update(User $user, Campaign $campaign): bool
    {
        // Both GMs and Players attached to the campaign can edit content.
        return $campaign->isMember($user);
    }

    /**
     * Determine whether the user can update campaign details.
     */
    public function updateDetails(User $user, Campaign $campaign): bool
    {
        return $campaign->isGm($user);
    }

    /**
     * Determine whether the user can delete items or the campaign.
     */
    public function delete(User $user, Campaign $campaign): bool
    {
        return $campaign->isGm($user);
    }

    /**
     * Determine whether the user can restore items or the campaign.
     */
    public function restore(User $user, Campaign $campaign): bool
    {
        return $campaign->isGm($user);
    }

    /**
     * Determine whether the user can force delete / empty trash.
     */
    public function forceDelete(User $user, Campaign $campaign): bool
    {
        return $campaign->isGm($user);
    }

    /**
     * Determine whether the user can manage members and invites.
     */
    public function manageMembers(User $user, Campaign $campaign): bool
    {
        return $campaign->isGm($user);
    }

    /**
     * Determine whether the user can empty trash and compact revisions.
     */
    public function manageTrash(User $user, Campaign $campaign): bool
    {
        return $campaign->isGm($user);
    }
}
