<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServerMetricsUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Datos de las métricas del servidor.
     */
    public array $data;

    /**
     * Crea una nueva instancia del evento.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Canal sobre el cual se transmitirá el evento.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('server-metrics'),
        ];
    }

    /**
     * Nombre del evento al ser transmitido.
     */
    public function broadcastAs(): string
    {
        return 'ServerMetricsUpdated';
    }

    /**
     * Datos que se envían a través del WebSocket.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'data' => $this->data,
        ];
    }
}
