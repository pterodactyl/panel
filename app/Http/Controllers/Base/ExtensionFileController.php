<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Base;

use Illuminate\Http\Response;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Extensions\ExtensionSettingFiles;

class ExtensionFileController extends Controller
{
    /**
     * Serves a file uploaded through an extension's `file` setting. These are
     * public by design (login page logos, install icons), so the route carries no
     * session; the names are random and never reused, which makes the response
     * safe to cache forever.
     */
    public function __invoke(ExtensionSettingFiles $files, string $extension, string $file): Response
    {
        $stored = $files->read($extension, $file);
        abort_if($stored === null, Response::HTTP_NOT_FOUND);

        return new Response($stored['contents'], Response::HTTP_OK, [
            'Content-Type' => $stored['mime'],
            'Content-Disposition' => 'inline; filename="'.$file.'"',
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
            // The type is fixed by the stored name and never sniffed; this keeps an
            // SVG opened directly from running script or loading anything.
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
        ]);
    }
}
