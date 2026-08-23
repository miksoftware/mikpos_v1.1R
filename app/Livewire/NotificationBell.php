<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\KitchenNotification;
use App\Models\Sale;
use Livewire\Component;

class NotificationBell extends Component
{
    public array $notifications = [];
    public bool $isOpen = false;
    public ?string $lastCheckedAt = null;
    public int $unreadCount = 0;

    public function mount()
    {
        $this->lastCheckedAt = now()->toDateTimeString();
        $this->loadNotifications();
    }

    public function poll()
    {
        $user = auth()->user();
        if (!$user) {
            return;
        }

        $hasNew = false;
        $newKitchenCount = 0;
        $newEcommerceCount = 0;
        $latestNotification = null;

        // 1. Check Kitchen Notifications
        $branchId = $user->branch_id;
        $kitchenQuery = KitchenNotification::query();

        if ($user->hasRole('admin') || $user->hasRole('super_admin') || $user->hasRole('supervisor')) {
            if ($branchId) {
                $kitchenQuery->where('branch_id', $branchId);
            }
        } else {
            $kitchenQuery->where(function ($q) use ($user, $branchId) {
                $q->where('user_id', $user->id);
                if ($branchId) {
                    $q->orWhere(function ($sub) use ($branchId) {
                        $sub->whereNull('user_id')->where('branch_id', $branchId);
                    });
                }
            });
        }

        $newKitchen = (clone $kitchenQuery)
            ->where('created_at', '>', $this->lastCheckedAt)
            ->where('read', false)
            ->orderByDesc('created_at')
            ->get();

        if ($newKitchen->isNotEmpty()) {
            $hasNew = true;
            $newKitchenCount = $newKitchen->count();
            $latestNotification = $newKitchen->first();

            foreach ($newKitchen as $kNotif) {
                // Avoid duplicates in memory
                $existingIndex = collect($this->notifications)->search(fn($n) => ($n['type'] ?? '') === 'kitchen' && ($n['id'] ?? 0) === $kNotif->id);
                if ($existingIndex === false) {
                    array_unshift($this->notifications, [
                        'type'          => 'kitchen',
                        'id'            => $kNotif->id,
                        'title'         => $kNotif->title,
                        'message'       => $kNotif->message,
                        'station_name'  => $kNotif->station_name,
                        'station_icon'  => $kNotif->station_icon ?: '🍳',
                        'station_color' => $kNotif->station_color ?: '#ff7261',
                        'mesa_name'     => $kNotif->mesa_name,
                        'status'        => $kNotif->status,
                        'time'          => $kNotif->created_at->diffForHumans(),
                        'created_at'    => $kNotif->created_at->toDateTimeString(),
                        'read'          => false,
                    ]);
                }
            }
        }

        // 2. Check Ecommerce Orders (if enabled)
        $ecommerceEnabled = Branch::where('is_active', true)->where('ecommerce_enabled', true)->exists();
        if ($ecommerceEnabled && ($user->hasRole('admin') || $user->hasRole('super_admin') || $user->hasRole('supervisor') || $user->hasPermission('ecommerce_orders.view'))) {
            $newOrders = Sale::where('source', 'ecommerce')
                ->where('status', 'pending_approval')
                ->where('created_at', '>', $this->lastCheckedAt)
                ->with('customer')
                ->orderByDesc('created_at')
                ->get();

            if ($newOrders->isNotEmpty()) {
                $hasNew = true;
                $newEcommerceCount = $newOrders->count();
                if (!$latestNotification) {
                    $latestOrder = $newOrders->first();
                }

                foreach ($newOrders as $order) {
                    $customerName = $order->customer ? $order->customer->full_name : 'Cliente';
                    $existingIndex = collect($this->notifications)->search(fn($n) => ($n['type'] ?? '') === 'ecommerce' && ($n['id'] ?? 0) === $order->id);
                    if ($existingIndex === false) {
                        array_unshift($this->notifications, [
                            'type'       => 'ecommerce',
                            'id'         => $order->id,
                            'title'      => "Nuevo pedido ecommerce #{$order->invoice_number}",
                            'message'    => "Cliente: {$customerName}",
                            'customer'   => $customerName,
                            'total'      => $order->total,
                            'time'       => $order->created_at->diffForHumans(),
                            'created_at' => $order->created_at->toDateTimeString(),
                            'read'       => false,
                        ]);
                    }
                }
            }
        }

        if ($hasNew) {
            $this->unreadCount = collect($this->notifications)->where('read', false)->count();
            $this->lastCheckedAt = now()->toDateTimeString();

            // Dispatch sound event
            $this->dispatch('play-notification-sound');

            // Dispatch browser notification
            if ($latestNotification) {
                $this->dispatch('kitchen-ready-notification', [
                    'title'   => $latestNotification->title,
                    'body'    => $latestNotification->message,
                    'station' => $latestNotification->station_name,
                    'mesa'    => $latestNotification->mesa_name,
                ]);
            } elseif ($newEcommerceCount > 0) {
                $this->dispatch('new-order-notification');
            }
        }
    }

    public function loadNotifications()
    {
        $user = auth()->user();
        if (!$user) {
            return;
        }

        $allNotifications = [];

        // 1. Kitchen notifications
        $branchId = $user->branch_id;
        $kitchenQuery = KitchenNotification::query();

        if ($user->hasRole('admin') || $user->hasRole('super_admin') || $user->hasRole('supervisor')) {
            if ($branchId) {
                $kitchenQuery->where('branch_id', $branchId);
            }
        } else {
            $kitchenQuery->where(function ($q) use ($user, $branchId) {
                $q->where('user_id', $user->id);
                if ($branchId) {
                    $q->orWhere(function ($sub) use ($branchId) {
                        $sub->whereNull('user_id')->where('branch_id', $branchId);
                    });
                }
            });
        }

        $kitchenNotifications = $kitchenQuery
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        foreach ($kitchenNotifications as $kNotif) {
            $allNotifications[] = [
                'type'          => 'kitchen',
                'id'            => $kNotif->id,
                'title'         => $kNotif->title,
                'message'       => $kNotif->message,
                'station_name'  => $kNotif->station_name,
                'station_icon'  => $kNotif->station_icon ?: '🍳',
                'station_color' => $kNotif->station_color ?: '#ff7261',
                'mesa_name'     => $kNotif->mesa_name,
                'status'        => $kNotif->status,
                'time'          => $kNotif->created_at->diffForHumans(),
                'created_at'    => $kNotif->created_at->toDateTimeString(),
                'read'          => (bool) $kNotif->read,
            ];
        }

        // 2. Ecommerce notifications
        $ecommerceEnabled = Branch::where('is_active', true)->where('ecommerce_enabled', true)->exists();
        if ($ecommerceEnabled && ($user->hasRole('admin') || $user->hasRole('super_admin') || $user->hasRole('supervisor') || $user->hasPermission('ecommerce_orders.view'))) {
            $pendingOrders = Sale::where('source', 'ecommerce')
                ->where('status', 'pending_approval')
                ->with('customer')
                ->orderByDesc('created_at')
                ->limit(20)
                ->get();

            foreach ($pendingOrders as $order) {
                $customerName = $order->customer ? $order->customer->full_name : 'Cliente';
                $allNotifications[] = [
                    'type'       => 'ecommerce',
                    'id'         => $order->id,
                    'title'      => "Pedido ecommerce #{$order->invoice_number}",
                    'message'    => "Cliente: {$customerName}",
                    'customer'   => $customerName,
                    'total'      => $order->total,
                    'time'       => $order->created_at->diffForHumans(),
                    'created_at' => $order->created_at->toDateTimeString(),
                    'read'       => true,
                ];
            }
        }

        // Sort all by created_at desc
        usort($allNotifications, function ($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });

        $this->notifications = array_slice($allNotifications, 0, 25);
        $this->unreadCount = collect($this->notifications)->where('read', false)->count();
    }

    public function toggleDropdown()
    {
        $this->isOpen = !$this->isOpen;
        if ($this->isOpen) {
            $this->markAllRead();
        }
    }

    public function markAllRead()
    {
        $kitchenIds = collect($this->notifications)
            ->where('type', 'kitchen')
            ->where('read', false)
            ->pluck('id')
            ->toArray();

        if (!empty($kitchenIds)) {
            KitchenNotification::whereIn('id', $kitchenIds)->update([
                'read'    => true,
                'read_at' => now(),
            ]);
        }

        foreach ($this->notifications as &$notification) {
            $notification['read'] = true;
        }
        $this->unreadCount = 0;
    }

    public function dismissNotification(int $index)
    {
        if (isset($this->notifications[$index])) {
            $notif = $this->notifications[$index];

            if (($notif['type'] ?? '') === 'kitchen') {
                KitchenNotification::where('id', $notif['id'])->update([
                    'read'    => true,
                    'read_at' => now(),
                ]);
            }

            if (!$notif['read']) {
                $this->unreadCount = max(0, $this->unreadCount - 1);
            }

            array_splice($this->notifications, $index, 1);
        }
    }

    public function goToMostrador()
    {
        $this->markAllRead();
        $this->isOpen = false;
        return $this->redirect('/mostrador', navigate: true);
    }

    public function goToOrders()
    {
        $this->markAllRead();
        $this->isOpen = false;
        return $this->redirect('/ecommerce-orders', navigate: true);
    }

    public function render()
    {
        return view('livewire.notification-bell');
    }
}
