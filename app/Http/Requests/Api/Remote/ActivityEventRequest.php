<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Remote;

use Pterodactyl\Actions\Activity\IngestActivityLogs;
use Pterodactyl\Http\Requests\Api\ApiRequest;
use Pterodactyl\Models\Node;

class ActivityEventRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return IngestActivityLogs::rules();
    }

    public function node(): Node
    {
        return RemoteRequestNode::get($this);
    }
}
