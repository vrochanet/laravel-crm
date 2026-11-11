<?php

return [
    'configuration' => [
        'title' => 'Prospeção',
        'info' => 'Configurações do assistente de prospeção imobiliária',

        'settings' => [
            'title' => 'Comunicação',
            'info' => 'Configuração dos canais e regras de comunicação',
        ],

        'whatsapp' => [
            'title' => 'WhatsApp',
            'info' => 'Configurações de comunicação através do WhatsApp',
        ],

        'email' => [
            'title' => 'Email',
            'info' => 'Configurações de comunicação através de email',
        ],

        'fields' => [
            'enabled' => 'Canal ativo',
            'mode' => 'Modo de funcionamento',
            'approval_required' => 'Exigir aprovação antes do envio',
            'max_first_contacts' => 'Máximo de primeiros contactos por dia',
            'min_interval' => 'Intervalo mínimo entre mensagens (horas)',
            'follow_up_interval' => 'Intervalo entre seguimentos (dias)',
            'max_follow_ups' => 'Máximo de seguimentos sem resposta',
            'start_time' => 'Hora inicial permitida',
            'end_time' => 'Hora final permitida',

            'modes' => [
                'manual' => 'Manual',
                'automatic' => 'Automático',
            ],
        ],
    ],
];
