param(
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]] $PhpArguments
)

$phpExe = (Get-Command php -ErrorAction Stop).Source
$requiredExtensions = @(
    'php_pdo_pgsql.dll',
    'php_pgsql.dll',
    'php_gd.dll',
    'php_zip.dll'
)

$extensionArguments = @()
foreach ($extension in $requiredExtensions) {
    $extensionArguments += @('-d', "extension=$extension")
}

& $phpExe @extensionArguments @PhpArguments
exit $LASTEXITCODE
