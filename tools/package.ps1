# Build installable ZIP files. Run with Windows PowerShell 5.1 or PowerShell 7.
[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$projectDirectory = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$outputDirectory = Join-Path $projectDirectory 'dist'
$packages = @(
    @{ Slug = 'ai-web'; Kind = 'theme'; Name = 'web-svepomoci-sablona'; RelativeSource = 'theme/ai-web'; RequiredFile = 'style.css' },
    @{ Slug = 'ai-web-studio'; Kind = 'plugin'; Name = 'web-svepomoci-plugin'; RelativeSource = 'plugin/ai-web-studio'; RequiredFile = 'ai-web-studio.php' }
)

$components = [ordered]@{}
$releaseVersion = $null
foreach ($package in $packages) {
    $sourceDirectory = Join-Path $projectDirectory $package.RelativeSource
    $requiredFile = Join-Path $sourceDirectory $package.RequiredFile
    if (-not (Test-Path -LiteralPath $requiredFile -PathType Leaf)) {
        throw ('Missing package source: ' + $requiredFile)
    }
    $headers = Get-Content -LiteralPath $requiredFile -Raw -Encoding UTF8
    $component = [ordered]@{}
    foreach ($field in @(@('version', 'Version'), @('requires', 'Requires at least'), @('requires_php', 'Requires PHP'))) {
        $match = [regex]::Match($headers, '(?m)^\s*\*?\s*' + [regex]::Escape($field[1]) + ':\s*(\d+\.\d+(?:\.\d+)?)\s*$')
        if (-not $match.Success) { throw ('Missing version header: ' + $field[1]) }
        $component[$field[0]] = $match.Groups[1].Value
    }
    if ($component.version -notmatch '^\d+\.\d+\.\d+$') { throw 'Use a stable three-part release version.' }
    if ($null -ne $releaseVersion -and $releaseVersion -ne $component.version) { throw 'Plugin and theme versions must match.' }
    $releaseVersion = $component.version
    $components[$package.Kind] = $component
}

$pluginUpdater = Get-Content -LiteralPath (Join-Path $projectDirectory 'plugin/ai-web-studio/includes/github-updates.php') -Raw -Encoding UTF8
$themeUpdater = Get-Content -LiteralPath (Join-Path $projectDirectory 'theme/ai-web/includes/github-updates.php') -Raw -Encoding UTF8
if ($pluginUpdater -cne $themeUpdater) { throw 'The two updater copies must be identical.' }

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Directory]::CreateDirectory($outputDirectory) | Out-Null

foreach ($package in $packages) {
    $sourceDirectory = Join-Path $projectDirectory $package.RelativeSource
    $archivePath = Join-Path $outputDirectory ($package.Name + '.zip')
    $temporaryArchive = Join-Path $outputDirectory ($package.Name + '.building.' + [guid]::NewGuid().ToString('N') + '.zip')
    try {
        # Windows PowerShell's CreateFromDirectory may store backslashes in ZIP names.
        # Explicit POSIX entry paths are portable to Linux WordPress hosting.
        $archive = [System.IO.Compression.ZipFile]::Open($temporaryArchive, [System.IO.Compression.ZipArchiveMode]::Create)
        try {
            foreach ($sourceFile in (Get-ChildItem -LiteralPath $sourceDirectory -Recurse -File)) {
                $relativePath = $sourceFile.FullName.Substring($sourceDirectory.Length).TrimStart([char[]]@('\', '/')).Replace('\', '/')
                $entryName = $package.Slug + '/' + $relativePath
                [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($archive, $sourceFile.FullName, $entryName, [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
            }
        }
        finally { $archive.Dispose() }
        Move-Item -LiteralPath $temporaryArchive -Destination $archivePath -Force
        Write-Host ('Created ' + $archivePath)
    }
    finally {
        if (Test-Path -LiteralPath $temporaryArchive -PathType Leaf) {
            Remove-Item -LiteralPath $temporaryArchive -Force
        }
    }
}

$manifest = [ordered]@{ schema = 1; version = $releaseVersion; components = $components } | ConvertTo-Json -Depth 5
[System.IO.File]::WriteAllText((Join-Path $outputDirectory 'updates.json'), $manifest, [System.Text.UTF8Encoding]::new($false))
foreach ($legacyZip in @('ai-web.zip', 'ai-web-studio.zip')) {
    $legacyPath = Join-Path $outputDirectory $legacyZip
    if (Test-Path -LiteralPath $legacyPath -PathType Leaf) { Remove-Item -LiteralPath $legacyPath }
}
Write-Host ('Created updates.json for release v' + $releaseVersion)
