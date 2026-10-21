<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClinicalRecordController extends Controller
{
    public function download(Request $request, Appointment $appointment): Response
    {
        $user = $request->user();
        $isAssignedVeterinarian = $user->role === User::ROLE_VETERINARIO && $appointment->veterinarian_id === $user->id;
        $isPatientOwner = $user->role === User::ROLE_CLIENTE && $appointment->client_id === $user->id;

        abort_unless($isAssignedVeterinarian || $isPatientOwner, 403);
        abort_unless($appointment->status === Appointment::STATUS_COMPLETED, 404);

        $appointment->load(['client:id,name,email,phone', 'veterinarian:id,name,email']);
        $pdf = Pdf::loadView('clinical-record-pdf', [
            'appointment' => $appointment,
            'generatedAt' => now(),
        ])->setPaper('letter');

        return $pdf->download("expediente-aquarium-{$appointment->id}.pdf");
    }
}
