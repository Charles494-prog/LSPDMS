<?php

namespace App\Providers;

use App\Broadcasting\ConversationChannel;
use App\Models\Record;
use App\Observers\RecordObserver;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Record::observe(RecordObserver::class);
        Broadcast::channel('conversation.{conversationId}', [ConversationChannel::class, 'join']);
    }
}
