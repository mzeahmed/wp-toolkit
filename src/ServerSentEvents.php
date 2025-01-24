<?php

declare(strict_types=1);

namespace MzeAhmed\WpToolKit;

/**
 * This class handles the sending of Server-Sent Events (SSE).
 *
 * It allows adding listeners for specific events, triggering those events,
 * and sending data via SSE to the client. This class is currently used
 * for managing SSE in the project.
 */
class ServerSentEvents
{
    /**
     * @var array $listeners Associative array containing listeners for different events.
     */
    private array $listeners;

    public function __construct()
    {
        $this->listeners = [];
    }

    /**
     * Adds a listener for a specific event.
     *
     * Allows binding an event to a callback function. When the event is triggered,
     * all associated callback functions will be executed.
     *
     * @param string $event The name of the event.
     * @param callable $callback The callback function to execute when the event is triggered.
     *
     * @return void
     */
    public function addListener(string $event, callable $callback): void
    {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }

        $this->listeners[$event][] = $callback;
    }

    /**
     * Executes the callback functions associated with a specific event.
     *
     * When the event is triggered via this method, all callback functions
     * associated with the event are executed with the provided data.
     *
     * @param string $event The name of the event.
     * @param mixed $data The data to pass to the callback functions.
     *
     * @return void
     */
    public function dispatchEvent(string $event, mixed $data): void
    {
        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $callback) {
                $callback($data);
            }
        }
    }

    /**
     * Sends an SSE event with data.
     *
     * This method sends an SSE message to the client with an event ID, event name,
     * and data in JSON format. The event will be transmitted to the client and
     * processed as defined in the SSE client.
     *
     * @param string $event The name of the event to send.
     * @param mixed $data The data to send with the event, encoded in JSON.
     * @param int|string|null $id A unique identifier for the event (optional). If no ID is provided, the ID is based
     *     on the current timestamp.
     *
     * @return void
     * @throws \JsonException
     */
    public function send(string $event, mixed $data, int|string $id = null): void
    {
        $id = $id ?? time();

        echo 'id: ' . $id . PHP_EOL;
        echo 'event: ' . $event . PHP_EOL;
        echo 'data: ' . json_encode($data, JSON_THROW_ON_ERROR) . PHP_EOL;
        echo PHP_EOL;

        ob_flush();
        flush();
    }
}
