<?php

return [
    // Local-only simulated consultation amount. This is not a gateway price or a real charge.
    'simulated_consultation_fee' => (float) env('AQUARIUM_SIMULATED_CONSULTATION_FEE', 500),
    'demo_password' => env('AQUARIUM_DEMO_PASSWORD'),
    'audit_log_channel' => env('AQUARIUM_AUDIT_LOG_CHANNEL', 'daily'),
];
