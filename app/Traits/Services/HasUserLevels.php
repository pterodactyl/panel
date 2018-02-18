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
     * @param int $level
     * @return $this
     */
    public function setUserLevel(int $level)
    {
        $this->userLevel = $level;

        return $this;
    }

    /**
     * Determine which level this function is running at.
     *
     * @return int
     */
    public function getUserLevel(): int
    {
        return $this->userLevel;
    }

    /**
     * Determine if the current user level is set to a specific level.
     *
     * @param int $level
     * @return bool
     */
    public function isUserLevel(int $level): bool
    {
        return $this->getUserLevel() === $level;
    }
}
