<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Repositories\NotificationRepository;
use Illuminate\Support\Facades\Gate;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $totalUnreadNotifications = NotificationRepository::make()->where_Notifiable(Auth::user())->where_Unread()->getBuilder()->count();
        $notifications = Auth::user()->notifications()->simplePaginate(20);

        $notifications = collect(['total_unread' => $totalUnreadNotifications])->merge($notifications);
        return response()->json(["notifications" => $notifications]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     *  return the notification by id and mark the returned notification as readed
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $notificationQuery = NotificationRepository::find($id);
        $notification = $notificationQuery->getBuilder()->first();
        if (Gate::denies("has-notification", $notification))
            return response("Forbidden", 403);
        $notificationQuery->markAsRead();

        return response()->json(["notification" => $notification]);
    }

    /**
     * Mark notification as read.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function markAsRead($id)
    {
        $notificationQuery = NotificationRepository::find($id);
        $notification = $notificationQuery->getBuilder()->first();
        if (Gate::denies("has-notification", $notification))
            return response("Forbidden", 403);
        $notificationQuery->markAsRead();

        return response()->json(["message" => "notification marked as read."]);
    }


    /**
     * Mark notification as unread.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function markAsUnread($id)
    {
        $notificationQuery = NotificationRepository::find($id);
        $notification = $notificationQuery->getBuilder()->first();
        if (Gate::denies("has-notification", $notification))
            return response("Forbidden", 403);
        $notificationQuery->markAsUnread();

        return response()->json(["message" => "notification marked as unread."]);
    }

    /**
     * Remove the readed notifications .
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function deleteReaded()
    {
        $deleteReadedNotifications =  NotificationRepository::make()->where_Notifiable(Auth::user())->where_Unread()
            ->getBuilder()->delete();

        return $deleteReadedNotifications ?
            response()->json(['message' => "readed notifications deleted successfully."])
            : response()->json(['error_message' => "Could not delete."]);
    }
}
