<?php

namespace Pterodactyl\Traits\Services;

trait ReturnsUpdatedModels
{
    /**
     * @var bool
     */
    private $updatedModel = false;

    /**
     * @return bool
     */
    public function getUpdatedModel()
    {
        return $this->updatedModel;
    }

    /**
     * If called a fresh model will be returned from the database. This is used
     * for API calls, but is unnecessary for UI based updates where the page is
     * being reloaded and a fresh model will be pulled anyways.
     *
     * @return $this
     */
    public function returnUpdatedModel(bool $toggle = true)
    {
        $this->updatedModel = $toggle;

        return $this;
    }
}
