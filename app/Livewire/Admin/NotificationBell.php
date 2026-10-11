<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Computed;
use Livewire\Component;

class NotificationBell extends Component
{
    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    #[Computed]
    public function items()
    {
        return auth()->user()->notifications()->latest()->limit(8)->get();
    }

    public function openNotification(string $id)
    {
        $notification = auth()->user()->notifications()->whereKey($id)->first();

        if (! $notification) {
            return;
        }

        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        // Hanya arahkan ke URL internal
        if ($url && str_starts_with($url, url('/'))) {
            return redirect($url);
        }
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    public function render()
    {
        return view('livewire.admin.notification-bell');
    }
}