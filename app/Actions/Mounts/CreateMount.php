<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Mounts;

use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Mounts\CreatesMounts;
use Pterodactyl\Models\Mount;
use Pterodactyl\Services\Extensions\ExtensionFields;
use Pterodactyl\Services\Extensions\ValidatedExtensionValues;
use Ramsey\Uuid\Uuid;

final readonly class CreateMount implements CreatesMounts
{
    public function __construct(private ExtensionFields $extensions) {}

    /**
     * Create a new mount from validated attributes, assigning it a fresh UUID.
     *
     * @param  MountData  $data
     */
    public function create(array $data): Mount
    {
        $extensions = ValidatedExtensionValues::of($data['extensions'] ?? null);
        unset($data['extensions']);

        return DB::transaction(function () use ($data, $extensions): Mount {
            $mount = (new Mount)->fill($data);
            $mount->forceFill(['uuid' => Uuid::uuid4()->toString()]);
            $mount->saveOrFail();

            $this->extensions->save($mount, $extensions);

            return $mount;
        });
    }
}
