<?php

return [
    [
        'key'   => 'fsbo',
        'name'  => 'FSBO',
        'route' => 'admin.fsbo.index',
        'sort'  => 1,
    ], [
        'key'   => 'fsbo.create',
        'name'  => 'admin::app.acl.create',
        'route' => ['admin.fsbo.create', 'admin.fsbo.store'],
        'sort'  => 1,
    ],
];
