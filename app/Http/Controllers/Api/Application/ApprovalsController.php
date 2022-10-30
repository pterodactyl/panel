<?php

namespace Pterodactyl\Http\Controllers\Api\Application;

use Pterodactyl\Models\User;
use Illuminate\Http\Response;

class ApprovalsController extends ApplicationApiController
{
    /**
     * Render the Jexactyl referrals interface.
     */
    public function index(): User
    {
        return User::where('approved', false)->get();
    }

    /**
     * Approve an incoming approval request.
     */
    public function approve(int $id): Response
    {
        $user = User::where('id', $id)->first();
        $user->update(['approved' => true]);
        // This gives the user access to the frontend.

        return $this->returnNoContent();
    }

    /**
     * Deny an incoming approval request.
     */
    public function deny(int $id): Response
    {
        $user = User::where('id', $id)->first();
        $user->delete();
        // While typically we should look for associated servers, there
        // shouldn't be any present - as the user has been waiting for approval.

        return $this->returnNoContent();
    }
}
