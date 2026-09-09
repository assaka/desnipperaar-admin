<?php

namespace App\Providers;

use App\Listeners\AddPlainTextAlternative;
use App\Listeners\LogSentMessage;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(MessageSending::class, AddPlainTextAlternative::class);
        Event::listen(MessageSent::class, LogSentMessage::class);
    }
}
