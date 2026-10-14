<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Remote;

use Pterodactyl\Actions\Backups\CompleteBackup;
use Pterodactyl\Http\Requests\Api\ApiRequest;

class ReportBackupCompleteRequest extends ApiRequest
{
    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return CompleteBackup::rules();
    }
}
