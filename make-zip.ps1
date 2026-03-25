$src  = "C:\Users\User\Downloads\plugin-mcp-antigravity\antigravity-pages.php"
$dir  = "C:\Users\User\Downloads\plugin-mcp-antigravity\antigravity-pages"
$zip  = "C:\Users\User\Downloads\plugin-mcp-antigravity\antigravity-pages.zip"

# Remove artefatos anteriores
if (Test-Path $zip) { Remove-Item $zip -Force }
if (Test-Path $dir) { Remove-Item $dir -Recurse -Force }

# Cria a pasta e copia o PHP
New-Item -ItemType Directory -Path $dir | Out-Null
Copy-Item $src "$dir\antigravity-pages.php"

# Cria o ZIP usando .NET com forward slashes
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$zipStream   = [System.IO.File]::Open($zip, [System.IO.FileMode]::Create)
$archive     = New-Object System.IO.Compression.ZipArchive($zipStream, [System.IO.Compression.ZipArchiveMode]::Create, $false)

$entryName   = "antigravity-pages/antigravity-pages.php"
$entry       = $archive.CreateEntry($entryName, [System.IO.Compression.CompressionLevel]::Optimal)
$entryStream = $entry.Open()
$fileStream  = [System.IO.File]::OpenRead("$dir\antigravity-pages.php")
$fileStream.CopyTo($entryStream)
$fileStream.Close()
$entryStream.Close()

$archive.Dispose()
$zipStream.Close()

# Verifica
Write-Host "ZIP gerado com sucesso!"
$check = [System.IO.Compression.ZipFile]::OpenRead($zip)
$check.Entries | ForEach-Object { Write-Host " -> $($_.FullName)" }
$check.Dispose()
