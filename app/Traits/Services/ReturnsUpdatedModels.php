<?php

namespace Pterodactyl\Traits\Services;

trait ReturnsUpdatedModels
{
    private bool $updatedModel = false;

    public function getUpdatedModel(): bool
    {
        return $this->updatedModel;
    }

    /**
     * If called a fresh model will be returned from the database. This is used
     * for API calls, but is unnecessary for UI based updates where the page is
     * being reloaded and a fresh model will be pulled anyways.
     */
    public function returnUpdatedModel(bool $toggle = true): self
    {
        $this->updatedModel = $toggle;

        return $this;
    }
}
