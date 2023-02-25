<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /** Display a listing of the resource. */
    public function index()
    {
        $notifications = Auth::user()->notifications()->paginate();

        return view('notifications.index')->with([
            'notifications' => $notifications,
        ]);
    }

    /** Display the specified resource. */
    public function show(string $id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);

        $notification->markAsRead();

        return view('notifications.show')->with([
            'notification' => $notification,
        ]);
    }

    public function readAll()
    {
        $notifications = Auth::user()->notifications()->get();
        foreach ($notifications as $notification) {
            $notification->markAsRead();
        }

        return redirect()->back();
    }
}
