<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Models\Announcement;

class AnnouncementTransformer extends TransformerAbstract
{
    /**
     * Transform the given announcement model into an array.
     *
     * @param \App\Models\Announcement $announcement
     * @return array
     */
    public function transform(Announcement $announcement)
    {
        return [
            'id' => $announcement->id,
            'text' => $announcement->text,
            'mode' => $announcement->mode,
            'created_at' => $announcement->created_at->toIso8601String(),
        ];
    }

    /**
     * Transform a collection of announcements.
     *
     * @param \Illuminate\Support\Collection $announcements
     * @return array
     */
    public function transformCollection($announcements)
    {
        return $announcements->map(function ($announcement) {
            return $this->transform($announcement);
        })->toArray();
    }
}
