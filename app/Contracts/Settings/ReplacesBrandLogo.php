<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Settings;

use Illuminate\Http\UploadedFile;

interface ReplacesBrandLogo
{
    /**
     * Stores the upload as the panel's logo, or removes it given null, and deletes the logo it
     * replaces. Returns where the new logo is served from, null once it is removed.
     */
    public function replace(?UploadedFile $logo): ?string;
}
