param(
    [string]$CreditsFile = (Join-Path $PSScriptRoot '..\public\images\catalog-image-credits.json'),
    [string]$OutputDirectory = (Join-Path $PSScriptRoot '..\public\images'),
    [int]$Quality = 88
)

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Drawing
$records = Get-Content -LiteralPath $CreditsFile -Raw | ConvertFrom-Json
$fishPhotos = @($records | Where-Object { $_.localFile -like '/images/fish-*' })
$saved = 0
$skipped = 0

foreach ($photo in $fishPhotos) {
    $destination = Join-Path $OutputDirectory ([IO.Path]::GetFileName($photo.localFile))
    if (Test-Path -LiteralPath $destination) { $skipped++; continue }
    $temporary = "$destination.download"

    try {
        Invoke-WebRequest -Uri $photo.imageUrl -OutFile $temporary -UserAgent 'AQUARIUMLocalCatalog/1.0 (local student project)' -TimeoutSec 60
        $sourceImage = [System.Drawing.Image]::FromFile($temporary)
        try {
            $bitmap = [System.Drawing.Bitmap]::new($sourceImage.Width, $sourceImage.Height, [System.Drawing.Imaging.PixelFormat]::Format24bppRgb)
            $graphics = [System.Drawing.Graphics]::FromImage($bitmap)
            try {
                $graphics.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
                $graphics.DrawImage($sourceImage, 0, 0, $sourceImage.Width, $sourceImage.Height)
                $encoder = [System.Drawing.Imaging.ImageCodecInfo]::GetImageEncoders() | Where-Object { $_.MimeType -eq 'image/jpeg' } | Select-Object -First 1
                $parameters = [System.Drawing.Imaging.EncoderParameters]::new(1)
                $parameters.Param[0] = [System.Drawing.Imaging.EncoderParameter]::new([System.Drawing.Imaging.Encoder]::Quality, [long]$Quality)
                $bitmap.Save($destination, $encoder, $parameters)
                $parameters.Dispose()
            } finally {
                $graphics.Dispose()
                $bitmap.Dispose()
            }
        } finally {
            $sourceImage.Dispose()
        }
        $saved++
        Write-Host "Guardada foto local $($photo.subject): $([IO.Path]::GetFileName($destination))"
    } catch {
        if (Test-Path -LiteralPath $temporary) { Remove-Item -LiteralPath $temporary -Force }
        if ($_.Exception.Message -match '429|Too many requests') {
            Write-Warning "Commons limitó las descargas tras $saved archivos; vuelve a ejecutar el script para continuar sin volver a descargar los archivos guardados."
            break
        }
        throw
    } finally {
        if ($sourceImage) { $sourceImage.Dispose(); Remove-Variable sourceImage -ErrorAction SilentlyContinue }
    }

    Remove-Item -LiteralPath $temporary -Force
    Start-Sleep -Milliseconds 6000
}

Write-Host "Descargadas: $saved; ya estaban en disco: $skipped."
