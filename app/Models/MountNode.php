<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Model;

#[Table(name: 'mount_node')]
#[WithoutIncrementing]
class MountNode extends Model
{
    protected $primaryKey;
}
