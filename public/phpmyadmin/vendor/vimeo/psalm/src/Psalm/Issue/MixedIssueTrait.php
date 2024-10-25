<?php

namespace Psalm\Issue;

use Psalm\CodeLocation;

trait MixedIssueTrait
{
    /**
     * @var ?CodeLocation
     * @readonly
     */
    public $origin_location;

    public function __construct(
        string $message,
        CodeLocation $code_location,
        ?CodeLocation $origin_location = null
    ) {
        $this->code_location = $code_location;
        $this->message = $message;
        $this->origin_location = $origin_location;
    }

    public function getMixedOriginMessage(): string
    {
        return $this->message
            . ($this->origin_location
                ? '. Consider improving the type at ' . $this->origin_location->getShortSummary()
                : '');
    }

    public function getOriginalLocation(): ?CodeLocation
    {
        return $this->origin_location;
    }
}
