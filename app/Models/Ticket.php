<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Ticket extends Model
{
    use LogsActivity;

    protected $fillable = [
        'user_id', 'ticketcategory_id', 'ticket_id', 'title', 'priority', 'message', 'status', 'server',
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            -> logOnlyDirty()
            -> logOnly(['*'])
            -> dontSubmitEmptyLogs();
    }

    public function ticketcategory()
    {
        return $this->belongsTo(TicketCategory::class);
    }

    public function ticketcomments()
    {
        return $this->hasMany(TicketComment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
