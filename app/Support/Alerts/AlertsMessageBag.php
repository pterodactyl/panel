<?php

declare(strict_types=1);

namespace Pterodactyl\Support\Alerts;

use Illuminate\Contracts\Session\Session;

class AlertsMessageBag
{
    private const string SESSION_KEY = '_pterodactyl_alerts';

    private ?string $type = null;

    private ?string $message = null;

    public function __construct(private readonly Session $session) {}

    public function success(string $message): self
    {
        return $this->message('success', $message);
    }

    public function danger(string $message): self
    {
        return $this->message('danger', $message);
    }

    public function warning(string $message): self
    {
        return $this->message('warning', $message);
    }

    public function info(string $message): self
    {
        return $this->message('info', $message);
    }

    public function flash(): self
    {
        if ($this->type === null || $this->message === null) {
            return $this;
        }

        $messages = $this->getMessages();
        $messages[$this->type][] = $this->message;

        $this->session->flash(self::SESSION_KEY, $messages);

        return $this;
    }

    /**
     * @return array<string, list<string>>
     */
    public function getMessages(): array
    {
        $messages = $this->session->get(self::SESSION_KEY, []);
        if (! is_array($messages)) {
            return [];
        }

        $normalized = [];
        foreach ($messages as $type => $items) {
            if (! is_string($type) || ! is_array($items) || ! array_is_list($items)) {
                continue;
            }

            $normalized[$type] = array_values(array_filter($items, is_string(...)));
        }

        return $normalized;
    }

    private function message(string $type, string $message): self
    {
        $this->type = $type;
        $this->message = $message;

        return $this;
    }
}
