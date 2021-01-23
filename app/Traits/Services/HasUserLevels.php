<?php

namespace Pterodactyl\Traits\Services;

use Pterodactyl\Models\User;

trait HasUserLevels
{
    /**
     * @var int
     */
    private $userLevel = User::USER_LEVEL_USER;

    /**
     * Set the access level for running this function.
     *
     * @return $this
     */
    public function setUserLevel(int $level)
    {
        $this->userLevel = $level;

        return $this;
    }

    /**
     * Determine which level this function is running at.
     */
    public function getUserLevel(): int
    {
        return $this->userLevel;
    }

    /**
     * Determine if the current user level is set to a specific level.
     */
    public function isUserLevel(int $level): bool
    {
        return $this->getUserLevel() === $level;
    }
}
