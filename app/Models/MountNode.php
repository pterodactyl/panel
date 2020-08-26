<?php

use Pterodactyl\Models\Node;
use Pterodactyl\Models\Mount;
use Illuminate\Database\Eloquent\Model;

class MountNode extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var string
     */
    protected $table = 'mount_node';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function node()
    {
        return $this->belongsTo(Node::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function mount()
    {
        return $this->belongsTo(Mount::class);
    }
}
