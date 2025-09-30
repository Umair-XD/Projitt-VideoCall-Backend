<?php

namespace App\Providers;

use App\Events\AiNotesGenerated;
use App\Events\InvitationResponded;
use App\Events\InvitationSent;
use App\Events\MeetingEnded;
use App\Events\MeetingScheduled;
use App\Events\MeetingStarted;
use App\Events\MeetingUpdated;
use App\Events\RecordingEnded;
use App\Events\RecordingStarted;
use App\Listeners\DomainEventNotifier;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        MeetingScheduled::class => [DomainEventNotifier::class],
        MeetingUpdated::class => [DomainEventNotifier::class],
        MeetingStarted::class => [DomainEventNotifier::class],
        MeetingEnded::class => [DomainEventNotifier::class],
        InvitationSent::class => [DomainEventNotifier::class],
        InvitationResponded::class => [DomainEventNotifier::class],
        RecordingStarted::class => [DomainEventNotifier::class],
        RecordingEnded::class => [DomainEventNotifier::class],
        AiNotesGenerated::class => [DomainEventNotifier::class],
    ];
}
