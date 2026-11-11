<?php

namespace Webkul\Prospeccao\Services;

use InvalidArgumentException;
use Webkul\Core\SystemConfig;

class GlobalCommunicationConfigService
{
    public function __construct(
        protected SystemConfig $systemConfig
    ) {}

    public function get(): array
    {
        $settings = [
            'max_first_contacts_per_day' => (int) (
                $this->systemConfig->getConfigData(
                    'prospeccao.settings.communication.max_first_contacts_per_day'
                ) ?? 5
            ),
        ];

        $this->validate($settings);

        return $settings;
    }

    protected function validate(array $settings): void
    {
        if (
            $settings['max_first_contacts_per_day'] < 0
            || $settings['max_first_contacts_per_day'] > 100
        ) {
            throw new InvalidArgumentException(
                'O limite diário de primeiros contactos deve estar entre 0 e 100.'
            );
        }
    }
}
