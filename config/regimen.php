<?php

return [
    'routing' => [
        'mode' => env('REGIMEN_MODE', 'plan'),
        'prefix' => 'regimen',
    ],

    'guard' => 'web',

    'navigation' => [
        'route' => 'regimen.dashboard',
        'icon'  => 'heroicon-o-academic-cap',
        'order' => 95,
    ],

    'sidebar' => [
        [
            'group' => 'Übersicht',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'route' => 'regimen.dashboard',
                    'icon'  => 'heroicon-o-home',
                ],
                [
                    'label' => 'Kurse (geführt)',
                    'route' => 'regimen.plans.index',
                    'icon'  => 'heroicon-o-rectangle-stack',
                ],
                [
                    'label' => 'Bibliothek (frei)',
                    'route' => 'regimen.topics.index',
                    'icon'  => 'heroicon-o-book-open',
                ],
            ],
        ],
    ],
];
