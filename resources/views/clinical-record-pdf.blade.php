<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Expediente veterinario AQUARIUM</title>
    <style>
        @page { margin: 34px 40px 82px; }
        body { color: #102238; font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.45; }
        .header { background: #0b192c; color: #fff; padding: 18px 20px; }
        .brand { color: #08c2cd; font-size: 9px; font-weight: bold; letter-spacing: 2px; }
        h1 { font-size: 20px; margin: 5px 0 0; }
        h2 { border-bottom: 1px solid #cbd5e1; color: #0b192c; font-size: 12px; margin: 19px 0 8px; padding-bottom: 5px; }
        .meta { color: #d1dce8; margin-top: 7px; }
        .grid { width: 100%; }
        .grid td { padding: 4px 8px 4px 0; vertical-align: top; width: 50%; }
        .label { color: #64748b; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .value { color: #102238; font-size: 10px; margin-top: 2px; }
        .block { background: #f1f6f8; border-radius: 5px; padding: 9px 11px; white-space: pre-wrap; }
        .hash { background: #f8fafc; border: 1px solid #dbe3ea; font-family: DejaVu Sans Mono, monospace; font-size: 7px; overflow-wrap: anywhere; padding: 7px; }
        .notice { color: #64748b; font-size: 8px; margin-top: 16px; }
        .footer { position: fixed; bottom: -58px; left: 0; right: 0; border-top: 1px solid #cbd5e1; color: #64748b; font-size: 8px; padding-top: 7px; }
        .seal { border: 1px solid #b45309; color: #92400e; font-size: 8px; font-weight: bold; letter-spacing: .6px; padding: 4px 7px; }
        .seal-note { color: #64748b; font-size: 7px; font-weight: normal; letter-spacing: 0; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">AQUARIUM · CLÍNICA VETERINARIA</div>
        <h1>Expediente de consulta</h1>
        <div class="meta">Folio de cita #{{ $appointment->id }} · {{ $appointment->scheduled_at->format('d/m/Y H:i') }}</div>
    </div>

    <h2>Paciente y responsables</h2>
    <table class="grid"><tr>
        <td><div class="label">Paciente</div><div class="value">{{ $appointment->patient_name }} · {{ $appointment->species }}</div></td>
        <td><div class="label">Cliente</div><div class="value">{{ $appointment->client?->name }} · {{ $appointment->client?->email }}</div></td>
    </tr><tr>
        <td><div class="label">Teléfono de contacto</div><div class="value">{{ $appointment->client?->phone ?: 'No proporcionado' }}</div></td>
        <td><div class="label">Veterinario asignado</div><div class="value">{{ $appointment->veterinarian?->name }}</div></td>
    </tr></table>

    <h2>Motivo y síntomas reportados</h2>
    <div class="block">{{ $appointment->symptoms }}</div>

    <h2>Signos clínicos y del agua</h2>
    @php($labels = ['temperature_c' => 'Temperatura (°C)', 'ph' => 'pH', 'ammonia_mg_l' => 'Amoniaco (mg/L)', 'nitrite_mg_l' => 'Nitrito (mg/L)', 'nitrate_mg_l' => 'Nitrato (mg/L)', 'weight_g' => 'Peso (g)'])
    <table class="grid"><tr>
        @foreach($labels as $key => $label)
            @if(!empty($appointment->clinical_data[$key]))
                <td><div class="label">{{ $label }}</div><div class="value">{{ $appointment->clinical_data[$key] }}</div></td>
                @if($loop->iteration % 2 === 0)</tr><tr>@endif
            @endif
        @endforeach
    </tr></table>

    <h2>Diagnóstico</h2>
    <div class="block">{{ $appointment->diagnosis }}</div>

    <h2>Prescripción estructurada e indicaciones</h2>
    <table class="grid"><tr>
        @foreach(['medication' => 'Medicamento', 'dose' => 'Dosis', 'route' => 'Vía', 'frequency' => 'Frecuencia', 'duration_days' => 'Duración (días)'] as $key => $label)
            @if(!empty($appointment->clinical_data[$key]))
                <td><div class="label">{{ $label }}</div><div class="value">{{ $appointment->clinical_data[$key] }}</div></td>
                @if($loop->iteration % 2 === 0)</tr><tr>@endif
            @endif
        @endforeach
    </tr></table>
    @if($appointment->prescription)<div class="block">{{ $appointment->prescription }}</div>@endif
    @if($appointment->veterinarian_notes)<h2>Notas clínicas</h2><div class="block">{{ $appointment->veterinarian_notes }}</div>@endif

    <h2>Huella de integridad local</h2>
    <div class="hash">SHA-256: {{ $appointment->clinical_record_hash ?: 'No disponible' }}</div>
    <p class="notice">Documento generado localmente el {{ $generatedAt->format('d/m/Y H:i:s') }}. La huella permite detectar cambios en el registro original; no constituye firma electrónica, sello de tiempo certificado ni acreditación NOM-151.</p>
    <div class="footer">
        <span class="seal">SELLO NOM-151 · SIMULACIÓN LOCAL</span>
        <div class="seal-note">Representación para pruebas. No es un sello oficial ni acredita certificación NOM-151, firma electrónica o sello de tiempo.</div>
        <div style="margin-top: 4px">AQUARIUM · Expediente clínico · Uso interno de atención veterinaria</div>
    </div>
</body>
</html>
