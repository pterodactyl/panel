<?php

namespace Pterodactyl\Transformers;

use Pterodactyl\Models\User;
use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform(User $user)
    {
        return $user;
    }

}
