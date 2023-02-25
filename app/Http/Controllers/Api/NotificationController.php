<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiscordUser;
use App\Models\User;
use App\Notifications\DynamicNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Spatie\ValidationRules\Rules\Delimited;

class NotificationController extends Controller
{
    /**
     * Display all notifications of an user.
     *
     * @param  Request  $request
     * @param  int  $userId
     * @return Response
     */
    public function index(Request $request, int $userId)
    {
        $discordUser = DiscordUser::find($userId);
        $user = $discordUser ? $discordUser->user : User::findOrFail($userId);

        return $user->notifications()->paginate($request->query('per_page', 50));
    }

    /**
     * Display a specific notification
     *
     * @param  int  $userId
     * @param  int  $notificationId
     * @return JsonResponse
     */
    public function view(int $userId, $notificationId)
    {
        $discordUser = DiscordUser::find($userId);
        $user = $discordUser ? $discordUser->user : User::findOrFail($userId);

        $notification = $user->notifications()->where('id', $notificationId)->get()->first();

        if (! $notification) {
            return response()->json(['message' => 'Notification not found.'], 404);
        }

        return $notification;
    }

    /**
     * Send a notification to an user.
     *
     * @param  Request  $request
     * @return JsonResponse
     *
     * @throws ValidationException
     */
    public function send(Request $request)
    {
        $data = $request->validate([
            'via' => ['required', new Delimited('in:mail,database')],
            'all' => 'required_without:users|boolean',
            'users' => ['required_without:all'],
            'title' => 'required|string|min:1',
            'content' => 'required|string|min:1',
        ]);
        $via = explode(',', $data['via']);
        $mail = null;
        $database = null;

        if (in_array('database', $via)) {
            $database = [
                'title' => $data['title'],
                'content' => $data['content'],
            ];
        }
        if (in_array('mail', $via)) {
            $mail = (new MailMessage)
                ->subject($data['title'])
                ->line(new HtmlString($data['content']));
        }

        $all = $data['all'] ?? false;
        if ($all) {
            $users = User::all();
        } else {
            $userIds = explode(',', $data['users']);
            $users = User::query()
                ->whereIn('id', $userIds)
                ->orWhereHas('discordUser', function (Builder $builder) use ($userIds) {
                    $builder->whereIn('id', $userIds);
                })
                ->get();
        }

        if ($users->count() == 0) {
            throw ValidationException::withMessages([
                'users' => ['No users found!'],
            ]);
        }

        Notification::send($users, new DynamicNotification($via, $database, $mail));

        return response()->json(['message' => 'Notification successfully sent.', 'user_count' => $users->count()]);
    }

    /**
     * Delete all notifications from an user
     *
     * @param  int  $userId
     * @return JsonResponse
     */
    public function delete(int $userId)
    {
        $discordUser = DiscordUser::find($userId);
        $user = $discordUser ? $discordUser->user : User::findOrFail($userId);

        $count = $user->notifications()->delete();

        return response()->json(['message' => 'All notifications have been successfully deleted.', 'count' => $count]);
    }

    /**
     * Delete a specific notification
     *
     * @param  int  $userId
     * @param  int  $notificationId
     * @return JsonResponse
     */
    public function deleteOne(int $userId, $notificationid)
    {
        $discordUser = DiscordUser::find($userId);
        $user = $discordUser ? $discordUser->user : User::findOrFail($userId);

        $notification = $user->notifications()->where('id', $notificationid)->get()->first();

        if (! $notification) {
            return response()->json(['message' => 'Notification not found.'], 404);
        }

        $notification->delete();

        return response()->json($notification);
    }
}
