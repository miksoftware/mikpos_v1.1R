@php
    /** @var \App\Models\KitchenOrder $order */
    $stationColor = $order->preparationStation?->color ?? '#6b7280';
    $stationIcon  = $order->preparationStation?->icon ?? '🍳';
    $stationName  = $order->preparationStation?->name ?? 'General';

    $elapsed = $order->sent_at ? $order->sent_at->diffInMinutes(now()) : 0;
    $isLate  = $elapsed >= 15 && $order->status !== 'ready';
@endphp

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-md transition-shadow
    {{ $order->status === 'pending' ? 'animate-[kitchenPulse_2s_ease-in-out_infinite]' : '' }}">

    {{-- Header strip (station color) --}}
    <div class="h-1.5" style="background: {{ $stationColor }};"></div>

    <div class="p-3 space-y-2.5">
        {{-- Top row: mesa + elapsed --}}
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <div class="flex items-center gap-1.5">
                    <span class="text-base">{{ $stationIcon }}</span>
                    <h4 class="font-bold text-slate-900 truncate">{{ $order->mesa?->name ?? 'Sin mesa' }}</h4>
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5">
                    {{ $order->number }}
                    @if($order->mesa?->sector)
                        · {{ $order->mesa->sector->name }}
                    @endif
                </p>
            </div>

            <div class="flex flex-col items-end gap-1 flex-shrink-0">
                <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold
                    {{ $isLate ? 'bg-red-100 text-red-700 animate-pulse' : 'bg-slate-100 text-slate-600' }}">
                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ $elapsed }} min
                </span>
                @if($order->user)
                <span class="text-[10px] text-slate-400 truncate max-w-[90px]">{{ $order->user->name }}</span>
                @endif
            </div>
        </div>

        {{-- Station chip --}}
        <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold text-white"
            style="background: {{ $stationColor }};">
            {{ $stationName }}
        </div>

        {{-- Items --}}
        <div class="space-y-1 border-t border-slate-100 pt-2">
            @foreach($order->items as $item)
            <div class="flex items-start gap-2 text-sm">
                <span class="flex-shrink-0 inline-flex items-center justify-center w-6 h-6 rounded bg-slate-100 text-slate-700 text-xs font-bold">
                    {{ rtrim(rtrim(number_format((float)$item->quantity, 3), '0'), '.') }}
                </span>
                <div class="flex-1 min-w-0">
                    <p class="text-slate-800 font-medium leading-tight">{{ $item->item_name }}</p>
                    @if(!empty($item->notes))
                    <p class="text-[11px] text-yellow-700 italic mt-0.5">📝 {{ $item->notes }}</p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        @if($order->notes)
        <div class="rounded-lg bg-yellow-50 border border-yellow-200 px-2 py-1.5">
            <p class="text-[11px] text-yellow-800"><span class="font-semibold">Nota general:</span> {{ $order->notes }}</p>
        </div>
        @endif

        {{-- Actions --}}
        @if(auth()->user()->hasPermission('kitchen.manage'))
        <div class="flex gap-1.5 pt-1">
            @if($order->status === 'pending')
                <button wire:click="takeOrder({{ $order->id }})"
                    class="flex-1 px-2 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors inline-flex items-center justify-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Tomar
                </button>
            @elseif($order->status === 'preparing')
                <button wire:click="markReady({{ $order->id }})"
                    class="flex-1 px-2 py-2 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors inline-flex items-center justify-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Marcar listo
                </button>
            @elseif($order->status === 'ready')
                <button wire:click="markDelivered({{ $order->id }})"
                    class="flex-1 px-2 py-2 text-xs font-bold text-white bg-slate-700 hover:bg-slate-800 rounded-lg transition-colors inline-flex items-center justify-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    Entregar
                </button>
            @endif

            <button wire:click="cancelOrder({{ $order->id }})"
                wire:confirm="¿Cancelar esta comanda?"
                class="px-2 py-2 text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors"
                title="Cancelar comanda">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        @endif
    </div>
</div>

<style>
    @keyframes kitchenPulse {
        0%, 100% { box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        50% { box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35); }
    }
</style>
