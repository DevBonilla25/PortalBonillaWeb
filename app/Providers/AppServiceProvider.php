<?php

namespace App\Providers;

use App\Models\PickupOrder;
use App\Observers\PickupOrderObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Keep browser uploads on Laravel; only the final file is sent to R2.
        config()->set('livewire.temporary_file_upload.disk', 'local');
    }

    public function boot(): void
    {
        PickupOrder::observe(PickupOrderObserver::class);
    }
}
