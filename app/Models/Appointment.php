<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    public const STATUS_SCHEDULED = 'programada';
    public const STATUS_COMPLETED = 'completada';
    public const STATUS_CANCELLED_CLIENT = 'cancelada_cliente';
    public const STATUS_CANCELLED_VET = 'cancelada_veterinario';
    public const STATUS_NO_SHOW = 'no_asistio';

    protected $fillable = [
        'client_id', 'veterinarian_id', 'patient_name', 'species', 'symptoms', 'scheduled_at',
        'status', 'consultation_fee', 'diagnosis', 'prescription', 'veterinarian_notes', 'cancellation_reason',
        'clinical_data', 'clinical_record_hash', 'clinical_recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'consultation_fee' => 'decimal:2',
            'clinical_data' => 'array',
            'clinical_recorded_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function veterinarian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'veterinarian_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(SimulatedPayment::class);
    }
}
