<?php

return [
    [
        'key' => 'prospeccao',
        'name' => 'prospeccao::app.configuration.title',
        'info' => 'prospeccao::app.configuration.info',
        'sort' => 8,
    ],
    [
        'key' => 'prospeccao.settings',
        'name' => 'prospeccao::app.configuration.settings.title',
        'info' => 'prospeccao::app.configuration.settings.info',
        'icon' => 'icon-setting',
        'sort' => 1,
    ],
    [
        'key' => 'prospeccao.settings.communication',
        'name' => 'prospeccao::app.configuration.settings.title',
        'info' => 'prospeccao::app.configuration.settings.info',
        'sort' => 1,
        'fields' => [
            [
                'name' => 'max_first_contacts_per_day',
                'title' => 'prospeccao::app.configuration.fields.max_first_contacts',
                'type' => 'number',
                'default' => 5,
                'validation' => 'required|integer|min:0|max:100',
            ],
        ],
    ],
    [
        'key' => 'prospeccao.settings.whatsapp',
        'name' => 'prospeccao::app.configuration.whatsapp.title',
        'info' => 'prospeccao::app.configuration.whatsapp.info',
        'sort' => 1,
        'fields' => [
            [
                'name' => 'enabled',
                'title' => 'prospeccao::app.configuration.fields.enabled',
                'type' => 'boolean',
                'default' => 1,
            ],
            [
                'name' => 'mode',
                'title' => 'prospeccao::app.configuration.fields.mode',
                'type' => 'select',
                'default' => 'manual',
                'options' => [
                    [
                        'title' => 'prospeccao::app.configuration.fields.modes.manual',
                        'value' => 'manual',
                    ],
                    [
                        'title' => 'prospeccao::app.configuration.fields.modes.automatic',
                        'value' => 'automatic',
                    ],
                ],
            ],
            [
                'name' => 'approval_required',
                'title' => 'prospeccao::app.configuration.fields.approval_required',
                'type' => 'boolean',
                'default' => 1,
            ],
            [
                'name' => 'min_interval_hours',
                'title' => 'prospeccao::app.configuration.fields.min_interval',
                'type' => 'number',
                'default' => 24,
                'validation' => 'required|integer|min:1|max:720',
            ],
            [
                'name' => 'follow_up_interval_days',
                'title' => 'prospeccao::app.configuration.fields.follow_up_interval',
                'type' => 'number',
                'default' => 4,
                'validation' => 'required|integer|min:1|max:90',
            ],
            [
                'name' => 'max_follow_ups',
                'title' => 'prospeccao::app.configuration.fields.max_follow_ups',
                'type' => 'number',
                'default' => 3,
                'validation' => 'required|integer|min:0|max:20',
            ],
            [
                'name' => 'start_time',
                'title' => 'prospeccao::app.configuration.fields.start_time',
                'type' => 'text',
                'default' => '09:00',
                'validation' => 'required',
            ],
            [
                'name' => 'end_time',
                'title' => 'prospeccao::app.configuration.fields.end_time',
                'type' => 'text',
                'default' => '19:00',
                'validation' => 'required',
            ],
        ],
    ],
    [
        'key' => 'prospeccao.settings.email',
        'name' => 'prospeccao::app.configuration.email.title',
        'info' => 'prospeccao::app.configuration.email.info',
        'sort' => 2,
        'fields' => [
            [
                'name' => 'enabled',
                'title' => 'prospeccao::app.configuration.fields.enabled',
                'type' => 'boolean',
                'default' => 1,
            ],
            [
                'name' => 'mode',
                'title' => 'prospeccao::app.configuration.fields.mode',
                'type' => 'select',
                'default' => 'manual',
                'options' => [
                    [
                        'title' => 'prospeccao::app.configuration.fields.modes.manual',
                        'value' => 'manual',
                    ],
                    [
                        'title' => 'prospeccao::app.configuration.fields.modes.automatic',
                        'value' => 'automatic',
                    ],
                ],
            ],
            [
                'name' => 'approval_required',
                'title' => 'prospeccao::app.configuration.fields.approval_required',
                'type' => 'boolean',
                'default' => 1,
            ],
            [
                'name' => 'min_interval_hours',
                'title' => 'prospeccao::app.configuration.fields.min_interval',
                'type' => 'number',
                'default' => 24,
                'validation' => 'required|integer|min:1|max:720',
            ],
            [
                'name' => 'follow_up_interval_days',
                'title' => 'prospeccao::app.configuration.fields.follow_up_interval',
                'type' => 'number',
                'default' => 4,
                'validation' => 'required|integer|min:1|max:90',
            ],
            [
                'name' => 'max_follow_ups',
                'title' => 'prospeccao::app.configuration.fields.max_follow_ups',
                'type' => 'number',
                'default' => 3,
                'validation' => 'required|integer|min:0|max:20',
            ],
            [
                'name' => 'start_time',
                'title' => 'prospeccao::app.configuration.fields.start_time',
                'type' => 'text',
                'default' => '09:00',
                'validation' => 'required',
            ],
            [
                'name' => 'end_time',
                'title' => 'prospeccao::app.configuration.fields.end_time',
                'type' => 'text',
                'default' => '19:00',
                'validation' => 'required',
            ],
        ],
    ],
];

