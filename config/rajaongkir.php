<?php

return [
    'api_key' => env('RAJAONGKIR_API_KEY', ''),
    
    // Default to starter tier base URL
    'base_url' => env('RAJAONGKIR_BASE_URL', 'https://api.rajaongkir.com/starter'),
    
    // Supported couriers in Starter tier: jne, pos, tiki
    'supported_couriers' => ['jne', 'pos', 'tiki'],
];
