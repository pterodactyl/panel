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

    /**
     * Store a newly created announcement in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'text' => 'required|string',
            'mode' => 'required|integer|in:0,1,2,3',
        ]);

        $announcement = Announcement::create($validated);
        $data = $this->fractal
            ->createData($this->getTransformer(AnnouncementTransformer::class)->transform($announcement))
            ->toArray();
        return response()->json($data, Response::HTTP_CREATED);
    }

    /**
     * Display the specified announcement.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $announcement = Announcement::findOrFail($id);
        $data = $this->fractal
            ->createData($this->getTransformer(AnnouncementTransformer::class)->transform($announcement))
            ->toArray();
        return response()->json($data);
    }

    /**
     * Update the specified announcement in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'text' => 'sometimes|required|string',
            'mode' => 'sometimes|required|integer|in:0,1,2,3',
        ]);

        $announcement = Announcement::findOrFail($id);
        $announcement->update($validated);
        $data = $this->fractal
            ->createData($this->getTransformer(AnnouncementTransformer::class)->transform($announcement))
            ->toArray();
        return response()->json($data);
    }

    /**
     * Remove the specified announcement from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->delete();
        return $this->returnNoContent();
    }
}
