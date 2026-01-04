<?php

namespace Pterodactyl\Observers;

use Pterodactyl\Models\EggVariable;

class EggVariableObserver
{
    public function creating(EggVariable $variable): void
    {
        // @phpstan-ignore-next-line property.notFound
        if ($variable->field_type) {
            unset($variable->field_type);
        }
    }

    public function updating(EggVariable $variable): void
    {
        // @phpstan-ignore-next-line property.notFound
        if ($variable->field_type) {
            unset($variable->field_type);
        }
    }
}
