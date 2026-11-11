<?php

use Webkul\Core\SystemConfig;
use Webkul\Prospeccao\Services\CommunicationConfigService;

beforeEach(function () {
    securityTestPipeline();
});

function communicationConfigDefaults(): array
{
    return [
        'enabled' => 1,
        'mode' => 'manual',
        'approval_required' => 1,
        'max_first_contacts_per_day' => 5,
        'min_interval_hours' => 24,
        'follow_up_interval_days' => 4,
        'max_follow_ups' => 3,
        'start_time' => '09:00',
        'end_time' => '19:00',
    ];
}

function communicationConfigService(array $overrides = []): CommunicationConfigService
{
    $settings = array_merge(communicationConfigDefaults(), $overrides);

    $systemConfig = Mockery::mock(SystemConfig::class);

    $systemConfig->shouldReceive('getConfigData')
        ->andReturnUsing(function (string $key) use ($settings) {
            $field = substr($key, strrpos($key, '.') + 1);

            return $settings[$field] ?? null;
        });

    return new CommunicationConfigService($systemConfig);
}

it('returns the default settings for WhatsApp', function () {
    $settings = communicationConfigService()->get('whatsapp');

    expect($settings)
        ->toBe([
            'enabled' => true,
            'mode' => 'manual',
            'approval_required' => true,
            'max_first_contacts_per_day' => 5,
            'min_interval_hours' => 24,
            'follow_up_interval_days' => 4,
            'max_follow_ups' => 3,
            'start_time' => '09:00',
            'end_time' => '19:00',
        ]);
});

it('returns the default settings for email', function () {
    $settings = communicationConfigService()->get('email');

    expect($settings['start_time'])->toBe('09:00')
        ->and($settings['end_time'])->toBe('19:00')
        ->and($settings['mode'])->toBe('manual');
});

it('converts boolean and numeric settings to the correct types', function () {
    $settings = communicationConfigService([
        'enabled' => '0',
        'approval_required' => '0',
        'max_first_contacts_per_day' => '8',
        'min_interval_hours' => '12',
    ])->get('whatsapp');

    expect($settings['enabled'])->toBeFalse()
        ->and($settings['approval_required'])->toBeFalse()
        ->and($settings['max_first_contacts_per_day'])->toBe(8)
        ->and($settings['min_interval_hours'])->toBe(12);
});

it('rejects an invalid channel', function () {
    expect(fn () => communicationConfigService()->get('telegram'))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects an invalid communication mode', function () {
    expect(fn () => communicationConfigService([
        'mode' => 'unknown',
    ])->get('whatsapp'))->toThrow(InvalidArgumentException::class);
});

it('rejects invalid frequency settings', function (string $field, int $value) {
    expect(fn () => communicationConfigService([
        $field => $value,
    ])->get('whatsapp'))->toThrow(InvalidArgumentException::class);
})->with([
    ['max_first_contacts_per_day', -1],
    ['min_interval_hours', 0],
    ['follow_up_interval_days', 0],
    ['max_follow_ups', -1],
]);

it('rejects invalid time formats', function (string $field, string $value) {
    expect(fn () => communicationConfigService([
        $field => $value,
    ])->get('whatsapp'))->toThrow(InvalidArgumentException::class);
})->with([
    ['start_time', '9:00'],
    ['start_time', '24:00'],
    ['start_time', '09:60'],
    ['end_time', '19:0'],
    ['end_time', '25:00'],
    ['end_time', '19:60'],
]);

it('rejects an end time equal to the start time', function () {
    expect(fn () => communicationConfigService([
        'start_time' => '09:00',
        'end_time' => '09:00',
    ])->get('whatsapp'))->toThrow(InvalidArgumentException::class);
});

it('rejects an end time earlier than the start time', function () {
    expect(fn () => communicationConfigService([
        'start_time' => '19:00',
        'end_time' => '09:00',
    ])->get('whatsapp'))->toThrow(InvalidArgumentException::class);
});
