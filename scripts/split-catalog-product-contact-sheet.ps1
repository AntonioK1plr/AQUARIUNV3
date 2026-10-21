param(
    [string]$Source = (Join-Path $PSScriptRoot '..\resources\images\catalog-products-contact-sheet.png'),
    [string]$OutputDirectory = (Join-Path $PSScriptRoot '..\public\images')
)

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Drawing
$sourcePath = (Resolve-Path -LiteralPath $Source).Path
$sourceImage = [System.Drawing.Bitmap]::new($sourcePath)
$gutter = 8
$columns = 4
$rows = 4
$cellWidth = ($sourceImage.Width - (($columns - 1) * $gutter)) / $columns
$cellHeight = ($sourceImage.Height - (($rows - 1) * $gutter)) / $rows
$names = @(
    'filter-hob.jpg', 'filter-canister.jpg', 'filter-sponge.jpg', 'filter-internal.jpg',
    'filter-sump.jpg', 'tank-nano.jpg', 'tank-planted.jpg', 'tank-marine.jpg',
    'substrate-sand.jpg', 'substrate-gravel.jpg', 'substrate-planted.jpg', 'food-fish.jpg',
    'decor-dragonstone.jpg', 'decor-lava.jpg', 'decor-pebbles.jpg', 'aquarium-accessories.jpg'
)

try {
    for ($index = 0; $index -lt $names.Count; $index++) {
        $column = $index % $columns
        $row = [math]::Floor($index / $columns)
        $left = [int][math]::Round($column * ($cellWidth + $gutter))
        $top = [int][math]::Round($row * ($cellHeight + $gutter))
        $width = [math]::Min([int][math]::Floor($cellWidth), $sourceImage.Width - $left)
        $height = [math]::Min([int][math]::Floor($cellHeight), $sourceImage.Height - $top)
        $target = [System.Drawing.Bitmap]::new($width, $height, [System.Drawing.Imaging.PixelFormat]::Format24bppRgb)
        $graphics = [System.Drawing.Graphics]::FromImage($target)
        try {
            $graphics.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
            $sourceRect = [System.Drawing.Rectangle]::new($left, $top, $width, $height)
            $targetRect = [System.Drawing.Rectangle]::new(0, 0, $width, $height)
            $graphics.DrawImage($sourceImage, $targetRect, $sourceRect, [System.Drawing.GraphicsUnit]::Pixel)
            $destination = Join-Path $OutputDirectory $names[$index]
            $target.Save($destination, [System.Drawing.Imaging.ImageFormat]::Jpeg)
        } finally {
            $graphics.Dispose()
            $target.Dispose()
        }
    }
} finally {
    $sourceImage.Dispose()
}

Get-ChildItem -LiteralPath $OutputDirectory -Filter '*.jpg' | Where-Object { $_.Name -in $names } | Select-Object Name,Length
