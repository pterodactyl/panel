<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Model;
use Barryvdh\DomPDF\Facade as PDF;

class Invoice extends Model
{
    /**
     * Download as PDF
     * 
     * 
     */
    public function downloadPdf()
    {
        $pdf = PDF::loadView('pdf.invoice', $this);
        return $pdf->download("invoice-{$this->id}.pdf");
    }

    /**
     * Gets the user who owns the server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
