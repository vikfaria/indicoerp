<?php

return [
    'saft' => [
        /*
        |--------------------------------------------------------------------------
        | SAF-T MZ XSD validation
        |--------------------------------------------------------------------------
        |
        | Set SAFT_MZ_REQUIRE_XSD_VALIDATION=true in production to enforce
        | validation against the official Mozambican schema before export.
        | Provide the absolute path to the official XSD via SAFT_MZ_XSD_PATH.
        |
        */
        'require_xsd_validation' => env('SAFT_MZ_REQUIRE_XSD_VALIDATION', false),
        'xsd_path' => env('SAFT_MZ_XSD_PATH', ''),
    ],
    'vat' => [
        /*
        |--------------------------------------------------------------------------
        | Input VAT deductibility enforcement
        |--------------------------------------------------------------------------
        |
        | warn  => keeps posting and stores warning for non-deductible scenarios
        | block => blocks posting when deductibility prerequisites fail
        |
        */
        'deductibility_enforcement' => env('SCE_VAT_DEDUCTIBILITY_ENFORCEMENT', 'warn'),

        /*
        |--------------------------------------------------------------------------
        | Default import VAT rate
        |--------------------------------------------------------------------------
        |
        | Fallback used only when the legal VAT table does not have an active
        | import VAT code configured. Keep this aligned with MzVatCode::IMP.
        |
        */
        'default_import_rate' => env('SCE_DEFAULT_IMPORT_VAT_RATE', 16.00),
    ],
    'gifim' => [
        /*
        |--------------------------------------------------------------------------
        | GIFiM compliance thresholds
        |--------------------------------------------------------------------------
        |
        | These thresholds are used by the backend to classify high-value
        | payments for GIFiM communication and approval workflows.
        |
        */
        'cash_threshold_mzn' => env('SCE_GIFIM_CASH_THRESHOLD_MZN', 250000),
        'electronic_threshold_mzn' => env('SCE_GIFIM_ELECTRONIC_THRESHOLD_MZN', 750000),
        'electronic_payment_methods' => [
            'bank_transfer',
            'cheque',
            'card',
            'mobile_money',
            'other',
        ],
    ],
];
