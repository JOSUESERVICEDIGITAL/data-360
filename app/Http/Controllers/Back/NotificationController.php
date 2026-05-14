<?php
// app/Http/Controllers/Back/NotificationController.php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Http\Requests\Back\NotificationRequest;
use App\Models\Back\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
  public function index(Request $request)
    {
        $query = Notification::with('user')
            ->orderBy('created_at', 'desc');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('is_global')) {
            $query->where('is_global', (bool) $request->is_global);
        }

        $notifications = $query->paginate(20);
        $users = User::orderBy('name')->get();

        return view('back.notifications.index', compact('notifications', 'users'));
    }

    public function create()
    {
        $users = User::orderBy('name')->get();
        return view('back.notifications.create', compact('users'));
    }

    public function store(NotificationRequest $request)
    {
        $data = $request->validated();

        if ($request->is_global) {
            $data['user_id'] = null;
        }

        Notification::create($data);

        return redirect()->route('back.notifications.index')
            ->with('success', 'Notification créée avec succès.');
    }



  public function userIndex(Request $request)
    {
        $query = Notification::forUser(auth()->id())
            ->notExpired()
            ->orderBy('created_at', 'desc');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('is_read')) {
            $query->where('is_read', $request->is_read == '1');
        }

        $notifications = $query->paginate(20);

        return view('front.notifications.index', compact('notifications'));
    }






     public function show($id)
    {
        $notification = Notification::forUser(auth()->id())
            ->where('id', $id)
            ->firstOrFail();

        // Marquer comme lue si ce ne l'est pas
        if (!$notification->is_read) {
            $notification->markAsRead();
        }

        return view('front.notifications.show', compact('notification'));
    }

    public function edit(Notification $notification)
    {
        $users = User::orderBy('name')->get();
        return view('back.notifications.edit', compact('notification', 'users'));
    }

    public function update(NotificationRequest $request, Notification $notification)
    {
        $data = $request->validated();

        if ($request->is_global) {
            $data['user_id'] = null;
        }

        $notification->update($data);

        return redirect()->route('back.notifications.index')
            ->with('success', 'Notification mise à jour.');
    }

    public function destroy(Notification $notification)
    {
        $notification->delete();

        return redirect()->route('back.notifications.index')
            ->with('success', 'Notification supprimée.');
    }

    public function markAsRead($id)
    {
        $notification = Notification::forUser(auth()->id())
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    public function markAllAsRead()
    {
        Notification::forUser(auth()->id())
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return redirect()->back()->with('success', 'Toutes les notifications ont été marquées comme lues.');
    }

    public function fetchUnread()
    {
        $notifications = Notification::forUser(auth()->id())
            ->notExpired()
            ->unread()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $count = Notification::forUser(auth()->id())
            ->notExpired()
            ->unread()
            ->count();

        return response()->json([
            'count' => $count,
            'notifications' => $notifications->map(function ($notif) {
                $typeInfo = Notification::types()[$notif->type] ?? Notification::types()['info'];
                return [
                    'id' => $notif->id,
                    'type' => $notif->type,
                    'title' => $notif->title,
                    'message' => $notif->message,
                    'icon' => $notif->icon ?? $typeInfo['icon'],
                    'link' => $notif->link,
                    'created_at' => $notif->created_at->diffForHumans(),
                ];
            }),
        ]);
    }
}