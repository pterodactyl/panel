<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Transformers\AnnouncementTransformer;

class AnnouncementController extends ApplicationApiController
{
    /**
     * Display a listing of announcements.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $announcements = Announcement::all();
        $data = $this->fractal
            ->createData($this->getTransformer(AnnouncementTransformer::class)->transformCollection($announcements))
            ->toArray();
        return response()->json($data);
    }

}
