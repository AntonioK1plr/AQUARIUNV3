param(
    [string]$OutputDirectory = (Join-Path $PSScriptRoot '..\public\images'),
    [string]$CreditsFile = (Join-Path $PSScriptRoot '..\public\images\catalog-image-credits.json'),
    [switch]$DownloadFiles
)

$ErrorActionPreference = 'Stop'
New-Item -ItemType Directory -Force -Path $OutputDirectory | Out-Null

$fish = @(
    @('Ancistrus cirrhosus','ancistrus'), @('Corydoras panda','corydoras-panda'), @('Corydoras sterbai','corydoras-sterbai'),
    @('Corydoras pygmaeus','corydoras-pygmaeus'), @('Otocinclus macrospilus','otocinclus-macrospilus'),
    @('Mikrogeophagus ramirezi','mikrogeophagus-ramirezi'), @('Mikrogeophagus altispinosus','mikrogeophagus-altispinosus'),
    @('Apistogramma cacatuoides','apistogramma-cacatuoides'), @('Apistogramma borellii','apistogramma-borellii'),
    @('Pelvicachromis pulcher','pelvicachromis-pulcher'), @('Trichogaster lalius','trichogaster-lalius'),
    @('Trichopodus trichopterus','trichopodus-trichopterus'), @('Macropodus opercularis','macropodus-opercularis'),
    @('Pseudomugil furcatus','pseudomugil-furcatus'), @('Melanotaenia praecox','melanotaenia-praecox'),
    @('Tanichthys albonubes','tanichthys-albonubes'), @('Hyphessobrycon amandae','hyphessobrycon-amandae'),
    @('Hyphessobrycon herbertaxelrodi','hyphessobrycon-herbertaxelrodi'), @('Hemigrammus bleheri','hemigrammus-bleheri'),
    @('Hyphessobrycon pulchripinnis','hyphessobrycon-pulchripinnis'), @('Paracheirodon axelrodi','paracheirodon-axelrodi'),
    @('Nematobrycon palmeri','nematobrycon-palmeri'), @('Trigonostigma espei','trigonostigma-espei'),
    @('Puntigrus tetrazona','puntigrus-tetrazona'), @('Danio margaritatus','danio-margaritatus'),
    @('Trichopsis pumila','trichopsis-pumila'), @('Poecilia wingei','poecilia-wingei'),
    @('Xiphophorus variatus','xiphophorus-variatus'), @('Poecilia latipinna','poecilia-latipinna'),
    @('Heterandria formosa','heterandria-formosa'), @('Betta imbellis','betta-imbellis'), @('Pangio kuhlii','pangio-kuhlii'),
    @('Botia striata','botia-striata'), @('Sewellia lineolata','sewellia-lineolata'),
    @('Crossocheilus oblongus','crossocheilus-oblongus'), @('Nannostomus beckfordi','nannostomus-beckfordi'),
    @('Carnegiella strigata','carnegiella-strigata'), @('Aplocheilus lineatus','aplocheilus-lineatus'),
    @('Tateurndina ocellicauda','tateurndina-ocellicauda'), @('Erpetoichthys calabaricus','erpetoichthys-calabaricus'),
    @('Pterapogon kauderni','pterapogon-kauderni'), @('Gramma loreto','gramma-loreto'),
    @('Gobiodon okinawae','gobiodon-okinawae'), @('Nemateleotris magnifica','nemateleotris-magnifica'),
    @('Chrysiptera parasema','chrysiptera-parasema'), @('Chromis viridis','chromis-viridis'),
    @('Ecsenius bicolor','ecsenius-bicolor'), @('Salarias fasciatus','salarias-fasciatus'),
    @('Zebrasoma flavescens','zebrasoma-flavescens'), @('Centropyge bispinosa','centropyge-bispinosa'),
    @('Poecilia reticulata','poecilia-reticulata'), @('Xiphophorus maculatus','xiphophorus-maculatus'),
    @('Poecilia sphenops','poecilia-sphenops'), @('Xiphophorus hellerii','xiphophorus-hellerii'),
    @('Danio rerio','danio-rerio'), @('Gymnocorymbus ternetzi','gymnocorymbus-ternetzi'),
    @('Puntius titteya','puntius-titteya'), @('Betta splendens','betta-splendens'),
    @('Corydoras paleatus','corydoras-paleatus'), @('Pterophyllum scalare','pterophyllum-scalare'),
    @('Trigonostigma heteromorpha','trigonostigma-heteromorpha'), @('Pristella maxillaris','pristella-maxillaris')
)

$aliases = @{
    'Ancistrus cirrhosus'='bristlenose pleco'; 'Corydoras panda'='panda corydoras'; 'Corydoras sterbai'='Sterba cory';
    'Corydoras pygmaeus'='pygmy corydoras'; 'Otocinclus macrospilus'='oto catfish'; 'Mikrogeophagus ramirezi'='ram cichlid';
    'Mikrogeophagus altispinosus'='Bolivian ram'; 'Apistogramma cacatuoides'='cockatoo dwarf cichlid';
    'Apistogramma borellii'='Borellis dwarf cichlid'; 'Pelvicachromis pulcher'='kribensis'; 'Trichogaster lalius'='dwarf gourami';
    'Trichopodus trichopterus'='three spot gourami'; 'Macropodus opercularis'='paradise fish'; 'Pseudomugil furcatus'='forktail blue eye';
    'Melanotaenia praecox'='dwarf neon rainbowfish'; 'Tanichthys albonubes'='white cloud mountain minnow';
    'Hyphessobrycon amandae'='ember tetra'; 'Hyphessobrycon herbertaxelrodi'='black neon tetra';
    'Hemigrammus bleheri'='rummy nose tetra'; 'Hyphessobrycon pulchripinnis'='lemon tetra'; 'Paracheirodon axelrodi'='cardinal tetra';
    'Nematobrycon palmeri'='emperor tetra'; 'Trigonostigma espei'='lambchop rasbora'; 'Puntigrus tetrazona'='tiger barb';
    'Danio margaritatus'='celestial pearl danio'; 'Trichopsis pumila'='sparkling gourami'; 'Poecilia wingei'='Endlers livebearer';
    'Xiphophorus variatus'='variable platy'; 'Poecilia latipinna'='sailfin molly'; 'Heterandria formosa'='least killifish';
    'Betta imbellis'='peaceful betta'; 'Pangio kuhlii'='kuhli loach'; 'Botia striata'='zebra loach';
    'Sewellia lineolata'='reticulated hillstream loach'; 'Crossocheilus oblongus'='Siamese algae eater';
    'Nannostomus beckfordi'='Beckford''s pencilfish'; 'Carnegiella strigata'='marbled hatchetfish';
    'Aplocheilus lineatus'='striped panchax'; 'Tateurndina ocellicauda'='peacock gudgeon';
    'Erpetoichthys calabaricus'='reedfish'; 'Pterapogon kauderni'='Banggai cardinalfish'; 'Gramma loreto'='royal gramma';
    'Gobiodon okinawae'='yellow clown goby'; 'Nemateleotris magnifica'='firefish goby';
    'Chrysiptera parasema'='yellowtail damselfish'; 'Chromis viridis'='blue green chromis'; 'Ecsenius bicolor'='bicolor blenny';
    'Salarias fasciatus'='lawnmower blenny'; 'Zebrasoma flavescens'='yellow tang'; 'Centropyge bispinosa'='coral beauty angelfish';
    'Poecilia reticulata'='guppy'; 'Xiphophorus maculatus'='platy fish'; 'Poecilia sphenops'='common molly';
    'Xiphophorus hellerii'='swordtail fish'; 'Danio rerio'='zebrafish'; 'Gymnocorymbus ternetzi'='black skirt tetra';
    'Puntius titteya'='cherry barb'; 'Betta splendens'='Siamese fighting fish'; 'Corydoras paleatus'='peppered corydoras';
    'Pterophyllum scalare'='freshwater angelfish'; 'Trigonostigma heteromorpha'='harlequin rasbora'; 'Pristella maxillaris'='X-ray tetra'
}

$credits = [System.Collections.Generic.List[object]]::new()
if (Test-Path -LiteralPath $CreditsFile) {
    try {
        $prior = Get-Content -LiteralPath $CreditsFile -Raw | ConvertFrom-Json
        foreach ($record in $prior) { $credits.Add($record) }
    } catch { Write-Warning 'Se regenerará un manifiesto de atribución que no se pudo leer.' }
}
$script:binaryDownloadsBlocked = $false

function Get-ImageBatch([string[]]$terms) {
    $query = (($terms | Select-Object -Unique | ForEach-Object { '"' + $_ + '"' }) -join ' OR ')
    $encoded = [uri]::EscapeDataString($query)
    $uri = "https://commons.wikimedia.org/w/api.php?action=query&generator=search&gsrsearch=$encoded&gsrnamespace=6&gsrlimit=100&prop=imageinfo&iiprop=url%7Cextmetadata%7Csize&iiurlwidth=1400&format=json"
    $response = Invoke-RestMethod -Uri $uri -UserAgent 'AQUARIUMLocalCatalog/1.0 (local student project)' -TimeoutSec 45
    return @($response.query.pages.PSObject.Properties | ForEach-Object { $_.Value })
}

function Choose-Image($pages, [string]$subject, [string]$alias, [switch]$AllowClosest, [hashtable]$ExcludeTitles = @{}) {
    $needle = $subject.ToLowerInvariant()
    $aliasNeedle = $alias.ToLowerInvariant()
    $usable = foreach ($page in $pages) {
        if ($ExcludeTitles.ContainsKey([string]$page.title)) { continue }
        if (-not $page.imageinfo -or -not $page.imageinfo[0].thumburl) { continue }
        $info = $page.imageinfo[0]
        $minimumWidth = if ($subject -eq 'Nannostomus beckfordi') { 220 } else { 600 }
        $minimumHeight = if ($subject -eq 'Nannostomus beckfordi') { 160 } else { 350 }
        if ($info.width -lt $minimumWidth -or $info.height -lt $minimumHeight -or [string]$page.title -notmatch '\.(jpg|jpeg|png)$') { continue }
        $license = [string]$info.extmetadata.LicenseShortName.value
        if ([string]::IsNullOrWhiteSpace($license) -or $license -match 'Non.?commercial|NC\b|NoDerivatives|ND\b|Fair use|All rights reserved') { continue }

        $description = [string]$info.extmetadata.ImageDescription.value
        $categories = [string]$info.extmetadata.Categories.value
        $haystack = ("$($page.title) $description").ToLowerInvariant()
        $exact = if ($haystack.Contains($needle)) { 1000 } elseif ($aliasNeedle -and $haystack.Contains($aliasNeedle)) { 800 } else { 0 }
        $penalty = if (("$($page.title) $description") -match 'diagram|drawing|illustration|logo|map|painting|embryo|skeleton|natural history|histoire naturelle|engraving|fishbowl|corte|tel[eé]ncefalo|histolog|anatomy') { 600 } else { 0 }
        if ($exact -eq 0 -and -not $AllowClosest) { continue }
        $format = if ([string]$page.title -match '\.(jpg|jpeg|png)$') { 40 } else { 0 }
        [pscustomobject]@{ Page = $page; Info = $info; Score = $exact + $format + [math]::Min([int]$info.width, 5000) / 100 - $penalty }
    }
    return $usable | Sort-Object Score -Descending | Select-Object -First 1
}

function Save-ImageCredit($candidate, [string]$fileName, [string]$subject) {
    if (-not $candidate) {
        Write-Warning "Commons no devolvió una foto identificable para $subject; la tarjeta conservará la imagen ilustrativa del catálogo."
        return
    }
    $page = $candidate.Page
    $info = $candidate.Info
    $destination = Join-Path $OutputDirectory $fileName
    $localFileAvailable = Test-Path -LiteralPath $destination
    if ($DownloadFiles -and -not $localFileAvailable -and -not $script:binaryDownloadsBlocked) {
        try {
            Invoke-WebRequest -Uri $info.thumburl -OutFile $destination -UserAgent 'AQUARIUMLocalCatalog/1.0 (local student project)' -TimeoutSec 45
            $localFileAvailable = $true
        } catch {
            if ($_.Exception.Message -match '429|Too many requests') {
                $script:binaryDownloadsBlocked = $true
                Write-Warning 'El servidor de imágenes limitó temporalmente las descargas; usaré la foto con atribución desde su URL de Commons.'
            } else { throw }
        }
    }
    $artist = ([string]$info.extmetadata.Artist.value -replace '<[^>]+>', ' ' -replace '\s+', ' ').Trim()
    $credits.Add([pscustomobject]@{
        localFile = "/images/$fileName"
        imageUrl = [string]$info.thumburl
        localFileAvailable = $localFileAvailable
        subject = $subject
        commonsFile = $page.title
        source = $info.descriptionurl
        author = $artist
        imageDescription = ([string]$info.extmetadata.ImageDescription.value -replace '<[^>]+>', ' ' -replace '\s+', ' ').Trim()
        license = [string]$info.extmetadata.LicenseShortName.value
        licenseUrl = [string]$info.extmetadata.LicenseUrl.value
        sourceWidth = [int]$info.width
        sourceHeight = [int]$info.height
        downloadedWidth = [int]$info.thumbwidth
        downloadedHeight = [int]$info.thumbheight
    })
    Write-Host "Foto seleccionada: $subject — $($page.title) ($($info.thumbwidth)x$($info.thumbheight))"
}

for ($offset = 0; $offset -lt $fish.Count; $offset += 2) {
    $batch = @($fish | Select-Object -Skip $offset -First 2)
    $forceRefresh = @('Trichopodus trichopterus', 'Pangio kuhlii', 'Centropyge bispinosa', 'Nannostomus beckfordi', 'Puntius titteya')
    $batch = @($batch | Where-Object { $path = "/images/fish-$($_[1]).jpg"; $forceRefresh -contains $_[0] -or -not ($credits | Where-Object localFile -eq $path) })
    $batchSubjects = @($batch | ForEach-Object { $_[0] })
    foreach ($record in @($credits | Where-Object { $batchSubjects -contains $_.subject })) { [void]$credits.Remove($record) }
    if ($batch.Count -eq 0) { continue }
    $terms = foreach ($entry in $batch) { $entry[0]; $aliases[$entry[0]] }
    $pages = Get-ImageBatch -terms $terms
    foreach ($entry in $batch) {
        $candidate = Choose-Image -pages $pages -subject $entry[0] -alias $aliases[$entry[0]]
        Save-ImageCredit -candidate $candidate -fileName "fish-$($entry[1]).jpg" -subject $entry[0]
    }
    $credits | ConvertTo-Json -Depth 5 | Set-Content -LiteralPath $CreditsFile -Encoding utf8
    Start-Sleep -Seconds 7
}

$credits | ConvertTo-Json -Depth 5 | Set-Content -LiteralPath $CreditsFile -Encoding utf8
Write-Host "Manifiesto de autoría y licencias: $CreditsFile"
