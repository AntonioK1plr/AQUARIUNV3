<?php

return [
    // Local Stripe test amount. Confirm the business price before production.
    'consultation_fee' => (float) env('AQUARIUM_CONSULTATION_FEE', env('AQUARIUM_SIMULATED_CONSULTATION_FEE', 500)),
    'demo_password' => env('AQUARIUM_DEMO_PASSWORD'),
    'audit_log_channel' => env('AQUARIUM_AUDIT_LOG_CHANNEL', 'daily'),
];
