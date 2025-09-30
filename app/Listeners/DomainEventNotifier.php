<?php

namespace App\Listeners;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class DomainEventNotifier
{
    public function handle(object $event): void
    {
        $type = class_basename($event::class);
        $payload = $this->serializeEvent($event);

        Log::info('DomainEvent', ['type' => $type, 'payload' => $payload]);

        $url = config('services.domain_events.webhook');
        if ($url) {
            try {
                Http::timeout(3)->post($url, [
                    'type' => $type,
                    'timestamp' => now()->toIso8601String(),
                    'payload' => $payload,
                ]);
            } catch (\Throwable $e) {
                Log::warning('DomainEvent webhook failed', ['type' => $type, 'error' => $e->getMessage()]);
            }
        }
    }

    private function serializeEvent(object $event): array
    {
        $data = [];
        foreach (get_object_vars($event) as $key => $value) {
            if (is_object($value) && method_exists($value, 'toArray')) {
                $data[$key] = $value->toArray();
            } else {
                $data[$key] = $value;
            }
        }
        return $data;
    }
}
