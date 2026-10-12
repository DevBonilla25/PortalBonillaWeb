<div
    wire:poll.5s="pollWarehouseOperationalNotifications"
    x-data="{
        notificationPermission: ('Notification' in window) ? Notification.permission : 'unsupported',
        playWarehouseNotificationSound(url) {
            if (! url) {
                return
            }

            const audio = new Audio(url)

            audio.play().catch(() => {})
        },
        async enableWarehouseNotifications() {
            if (! ('Notification' in window)) return

            this.notificationPermission = await Notification.requestPermission()
        },
        handleWarehouseOperationalNotification(detail) {
            this.playWarehouseNotificationSound(detail.soundUrl)

            if (document.hidden && this.notificationPermission === 'granted') {
                new Notification(detail.title, {
                    body: detail.body,
                    tag: `${detail.type}-${detail.eventId}`,
                })
            }
        },
    }"
    @warehouse-operational-notification.window="handleWarehouseOperationalNotification($event.detail)"
>
    <div
        x-cloak
        x-show="@js($warehouseId !== null) && notificationPermission === 'default'"
        class="fixed bottom-4 right-4 z-50"
    >
        <x-filament::button
            type="button"
            color="gray"
            size="sm"
            icon="heroicon-o-bell"
            x-on:click="enableWarehouseNotifications()"
        >
            Activar notificaciones
        </x-filament::button>
    </div>
</div>