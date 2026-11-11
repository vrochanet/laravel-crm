<?php

namespace Webkul\Prospeccao\Services;

use InvalidArgumentException;
use Webkul\Core\SystemConfig;

class CommunicationConfigService
{
    private const CHANNELS = ['whatsapp', 'email'];

    public function __construct(
        protected SystemConfig $systemConfig
    ) {}

    public function get(string $channel): array
    {
        if (! in_array($channel, self::CHANNELS, true)) {
            throw new InvalidArgumentException('Invalid communication channel.');
        }

        $prefix = "prospeccao.settings.{$channel}.";

        $settings = [
            'enabled' => (bool) $this->value($prefix.'enabled'),
            'mode' => (string) $this->value($prefix.'mode'),
            'approval_required' => (bool) $this->value($prefix.'approval_required'),
            'min_interval_hours' => (int) $this->value($prefix.'min_interval_hours'),
            'follow_up_interval_days' => (int) $this->value($prefix.'follow_up_interval_days'),
            'max_follow_ups' => (int) $this->value($prefix.'max_follow_ups'),
            'start_time' => (string) $this->value($prefix.'start_time'),
            'end_time' => (string) $this->value($prefix.'end_time'),
        ];

        $this->validate($settings);

        return $settings;
    }

    protected function value(string $key): mixed
    {
        return $this->systemConfig->getConfigData($key);
    }

    protected function validate(array $settings): void
    {
        if (! in_array($settings['mode'], ['manual', 'automatic'], true)) {
            throw new InvalidArgumentException('Invalid communication mode.');
        }

        if (
            $settings['min_interval_hours'] < 1
            || $settings['follow_up_interval_days'] < 1
            || $settings['max_follow_ups'] < 0
        ) {
            throw new InvalidArgumentException('Invalid communication frequency settings.');
        }

        foreach (['start_time', 'end_time'] as $field) {
            if (! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $settings[$field])) {
                throw new InvalidArgumentException("Invalid {$field} format. Expected HH:mm.");
            }
        }

        if ($settings['end_time'] <= $settings['start_time']) {
            throw new InvalidArgumentException('The end time must be later than the start time.');
        }
    }
}
