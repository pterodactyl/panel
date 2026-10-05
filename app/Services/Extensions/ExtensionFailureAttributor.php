<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Throwable;

/**
 * Attributes reported exceptions to the extension whose code raised them.
 *
 * Extension code that core calls directly - an action wrapper, a console command, a
 * controller - throws straight to the caller and is never caught on the extension's
 * behalf, so nothing about the exception is altered. When such an exception is
 * reported, the first frame outside the panel's own vendor directory decides the owner:
 * if it lies inside an enabled extension's package, the failure is recorded against that
 * extension. An exception a core action raised while an extension merely wrapped it
 * starts in core code and is left alone, as are exceptions the handler does not report.
 */
final class ExtensionFailureAttributor
{
    private bool $attributing = false;

    public function __construct(private readonly ExtensionRepository $extensions) {}

    public function attribute(Throwable $throwable): void
    {
        // Recording a failure dispatches an event whose listeners may fail in turn.
        if ($this->attributing || ! config('extensions.enabled')) {
            return;
        }

        $this->attributing = true;
        try {
            $identifier = $this->owner($throwable);
            if ($identifier !== null) {
                $this->extensions->recordFailure($identifier, $throwable->getMessage(), $throwable);
            }
        } catch (Throwable) {
            // Attribution is best effort and must never mask the exception being reported.
        } finally {
            $this->attributing = false;
        }
    }

    private function normalize(string $path): string
    {
        return mb_rtrim(str_replace('\\', '/', $path), '/');
    }

    private function owner(Throwable $throwable): ?string
    {
        $vendor = $this->normalize(base_path('vendor')).'/';
        foreach ([$throwable->getFile(), ...array_column($throwable->getTrace(), 'file')] as $file) {
            $file = $this->normalize($file);
            if (str_starts_with($file, $vendor)) {
                continue;
            }

            foreach ($this->extensions->enabled() as $manifest) {
                foreach (array_unique([$manifest->directory, realpath($manifest->directory) ?: $manifest->directory]) as $directory) {
                    if (str_starts_with($file, $this->normalize($directory).'/')) {
                        return $manifest->id;
                    }
                }
            }

            return null;
        }

        return null;
    }
}
