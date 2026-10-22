<?php

return [
    [
        'key' => 'prospeccao',
        'name' => 'Prospeccao',
        'route' => 'admin.prospeccao.index',
        'sort' => 1,
    ], [
        'key' => 'prospeccao.create',
        'name' => 'admin::app.acl.create',
        'route' => ['admin.prospeccao.create', 'admin.prospeccao.store'],
        'sort' => 1,
    ], [
        'key' => 'prospeccao.templates',
        'name' => 'Modelos de comunicação',
        'route' => 'admin.prospeccao.templates.index',
        'sort' => 2,
    ],
];
