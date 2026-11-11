<?php

use Webkul\Core\SystemConfig;
use Webkul\Prospeccao\Services\GlobalCommunicationConfigService;

function globalCommunicationConfig(array $overrides = []): GlobalCommunicationConfigService
{
    $defaults = [
        'max_first_contacts_per_day' => 5,
    ];

    $config = array_merge($defaults, $overrides);

    $systemConfig = Mockery::mock(SystemConfig::class);

    $systemConfig->shouldReceive('getConfigData')
        ->with('prospeccao.settings.communication.max_first_contacts_per_day')
        ->andReturn($config['max_first_contacts_per_day']);

    return new GlobalCommunicationConfigService($systemConfig);
}

it('returns the default daily first-contact limit', function () {
    expect(globalCommunicationConfig()->get())
        ->toBe(['max_first_contacts_per_day' => 5]);
});

it('converts the daily limit to an integer', function () {
    expect(globalCommunicationConfig([
        'max_first_contacts_per_day' => '8',
    ])->get())
        ->toBe(['max_first_contacts_per_day' => 8]);
});

it('accepts zero to disable the daily limit', function () {
    expect(globalCommunicationConfig([
        'max_first_contacts_per_day' => 0,
    ])->get())
        ->toBe(['max_first_contacts_per_day' => 0]);
});

it('rejects a negative daily limit', function () {
    expect(fn () => globalCommunicationConfig([
        'max_first_contacts_per_day' => -1,
    ])->get())->toThrow(\InvalidArgumentException::class);
});

it('rejects a daily limit above 100', function () {
    expect(fn () => globalCommunicationConfig([
        'max_first_contacts_per_day' => 101,
    ])->get())->toThrow(\InvalidArgumentException::class);
});
