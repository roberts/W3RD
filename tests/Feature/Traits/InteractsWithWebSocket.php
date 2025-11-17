<?php

namespace Tests\Feature\Traits;

use Illuminate\Support\Facades\Event;

trait InteractsWithWebSocket
{
    /**
     * Assert that an event was broadcast
     */
    protected function assertEventBroadcast(string $eventClass): void
    {
        Event::assertDispatched($eventClass);
    }

    /**
     * Fake event broadcasting for testing
     */
    protected function fakeEventBroadcasting(array $events = []): void
    {
        if (empty($events)) {
            Event::fake();
        } else {
            Event::fake($events);
        }
    }
}
