<?php

declare(strict_types=1);

return [
    'enabled' => (bool) env('BODYM_ENABLED', false),
    'contract_version' => 'bodym.v1',
    'response_contract_version' => 'bodym-response.v1',
    'model_version' => env('BODYM_MODEL_VERSION', 'untrained'),
    'legacy_method' => 'multiview_cv',
    'method' => 'bodym_ml',

    'measurements' => [
        'ankle_girth' => [
            'label' => 'Lingkar pergelangan kaki',
            'type' => 'circumference',
            'unit' => 'cm',
        ],
        'arm_length' => [
            'label' => 'Panjang lengan',
            'type' => 'length',
            'unit' => 'cm',
        ],
        'bicep_girth' => [
            'label' => 'Lingkar lengan atas',
            'type' => 'circumference',
            'unit' => 'cm',
        ],
        'calf_girth' => [
            'label' => 'Lingkar betis',
            'type' => 'circumference',
            'unit' => 'cm',
        ],
        'chest_girth' => [
            'label' => 'Lingkar dada',
            'type' => 'circumference',
            'unit' => 'cm',
        ],
        'forearm_girth' => [
            'label' => 'Lingkar lengan bawah',
            'type' => 'circumference',
            'unit' => 'cm',
        ],
        'height' => [
            'label' => 'Tinggi badan',
            'type' => 'height',
            'unit' => 'cm',
        ],
        'hip_girth' => [
            'label' => 'Lingkar pinggul',
            'type' => 'circumference',
            'unit' => 'cm',
        ],
        'leg_length' => [
            'label' => 'Panjang kaki',
            'type' => 'length',
            'unit' => 'cm',
        ],
        'shoulder_breadth' => [
            'label' => 'Lebar bahu',
            'type' => 'breadth',
            'unit' => 'cm',
        ],
        'shoulder_to_crotch' => [
            'label' => 'Panjang bahu ke pesak',
            'type' => 'length',
            'unit' => 'cm',
        ],
        'thigh_girth' => [
            'label' => 'Lingkar paha',
            'type' => 'circumference',
            'unit' => 'cm',
        ],
        'waist_girth' => [
            'label' => 'Lingkar pinggang',
            'type' => 'circumference',
            'unit' => 'cm',
        ],
        'wrist_girth' => [
            'label' => 'Lingkar pergelangan tangan',
            'type' => 'circumference',
            'unit' => 'cm',
        ],
    ],
];
