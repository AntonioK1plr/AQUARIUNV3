$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'
$projectRoot = Split-Path -Parent $PSScriptRoot
$toolsRoot = Join-Path $projectRoot 'tools'
$jmeterRoot = Join-Path $toolsRoot 'jmeter'
$javaRoot = Join-Path $toolsRoot 'java'
New-Item -ItemType Directory -Path $jmeterRoot, $javaRoot -Force | Out-Null

@'
# Machine-local runtimes are intentionally excluded from Git.
*
!.gitignore
'@ | Set-Content -Encoding utf8 (Join-Path $toolsRoot '.gitignore')

function Install-VerifiedZip([string] $Url, [string] $ChecksumUrl, [string] $ArchivePath, [string] $Destination, [string] $Algorithm, [string] $ExpectedDirectory, [string] $FileName) {
    $executable = Join-Path (Join-Path $Destination $ExpectedDirectory) $FileName
    if (Test-Path -LiteralPath $executable) {
        Write-Output "Ya instalado: $ExpectedDirectory"
        return
    }

    $checksumPath = "$ArchivePath.checksum"
    Invoke-WebRequest -Uri $Url -OutFile $ArchivePath
    Invoke-WebRequest -Uri $ChecksumUrl -OutFile $checksumPath
    $expected = (Get-Content -LiteralPath $checksumPath -Raw).Trim().Split(' ')[0].ToLowerInvariant()
    $actual = (Get-FileHash -LiteralPath $ArchivePath -Algorithm $Algorithm).Hash.ToLowerInvariant()
    if ($actual -ne $expected) {
        throw "Checksum $Algorithm inválido para $([IO.Path]::GetFileName($ArchivePath)); se canceló la extracción."
    }

    Expand-Archive -LiteralPath $ArchivePath -DestinationPath $Destination -Force
    Remove-Item -LiteralPath $ArchivePath, $checksumPath -Force
    Write-Output "Instalado y verificado ($Algorithm): $ExpectedDirectory"
}

$jmeterArchive = Join-Path $jmeterRoot 'apache-jmeter-5.6.3.zip'
Install-VerifiedZip `
    'https://dlcdn.apache.org/jmeter/binaries/apache-jmeter-5.6.3.zip' `
    'https://downloads.apache.org/jmeter/binaries/apache-jmeter-5.6.3.zip.sha512' `
    $jmeterArchive $jmeterRoot 'SHA512' 'apache-jmeter-5.6.3' 'bin/jmeter.bat'

$temurinFile = 'OpenJDK21U-jdk_x64_windows_hotspot_21.0.12.1_1.zip'
$temurinBase = 'https://github.com/adoptium/temurin21-binaries/releases/download/jdk-21.0.12.1%2B1'
$temurinArchive = Join-Path $javaRoot $temurinFile
Install-VerifiedZip `
    "$temurinBase/$temurinFile" `
    "$temurinBase/$temurinFile.sha256.txt" `
    $temurinArchive $javaRoot 'SHA256' 'jdk-21.0.12.1+1' 'bin/java.exe'

$env:JAVA_HOME = Join-Path $javaRoot 'jdk-21.0.12.1+1'
$env:Path = "$($env:JAVA_HOME)\bin;$($env:Path)"
$jmeterCommand = 'call "{0}" -v' -f (Join-Path $jmeterRoot 'apache-jmeter-5.6.3/bin/jmeter.bat')
& $env:ComSpec /d /c $jmeterCommand
if ($LASTEXITCODE -ne 0) { throw 'JMeter no pudo iniciarse.' }
& (Join-Path $env:JAVA_HOME 'bin/java.exe') -version
