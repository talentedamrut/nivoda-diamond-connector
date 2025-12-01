# Package Nivoda Diamond Connector Plugin
# This script creates a ZIP file ready for WordPress installation

$pluginName = "nivoda-diamond-connector"
$version = "1.0.0"
$outputFile = "$pluginName-$version.zip"

Write-Host "Packaging Nivoda Diamond Connector Plugin..." -ForegroundColor Green
Write-Host ""

# Get the plugin directory
$pluginDir = Join-Path $PSScriptRoot $pluginName

if (-not (Test-Path $pluginDir)) {
    Write-Host "Error: Plugin directory not found at $pluginDir" -ForegroundColor Red
    exit 1
}

# Files and directories to include
$include = @(
    "nivoda-diamond-connector.php",
    "readme.txt",
    "README.md",
    "includes/*",
    "assets/*",
    "templates/*",
    "languages/*"
)

# Files and directories to exclude
$exclude = @(
    "*.log",
    "*.tmp",
    ".git*",
    ".DS_Store",
    "Thumbs.db",
    "node_modules",
    ".env"
)

# Remove old ZIP if exists
if (Test-Path $outputFile) {
    Remove-Item $outputFile -Force
    Write-Host "Removed existing ZIP file" -ForegroundColor Yellow
}

# Create temporary directory
$tempDir = Join-Path $env:TEMP "ndc-package-temp"
if (Test-Path $tempDir) {
    Remove-Item $tempDir -Recurse -Force
}
New-Item -ItemType Directory -Path $tempDir | Out-Null

$destDir = Join-Path $tempDir $pluginName
New-Item -ItemType Directory -Path $destDir | Out-Null

Write-Host "Copying plugin files..." -ForegroundColor Cyan

# Copy main plugin file
Copy-Item "$pluginDir\nivoda-diamond-connector.php" -Destination $destDir
Copy-Item "$pluginDir\readme.txt" -Destination $destDir
Copy-Item "$pluginDir\README.md" -Destination $destDir

# Copy directories
foreach ($dir in @("includes", "assets", "templates")) {
    $sourcePath = Join-Path $pluginDir $dir
    $destPath = Join-Path $destDir $dir
    
    if (Test-Path $sourcePath) {
        Copy-Item $sourcePath -Destination $destPath -Recurse
        Write-Host "  ✓ Copied $dir/" -ForegroundColor Gray
    }
}

# Create languages directory
$langDir = Join-Path $destDir "languages"
New-Item -ItemType Directory -Path $langDir -Force | Out-Null
New-Item -ItemType File -Path "$langDir\.gitkeep" -Force | Out-Null
Write-Host "  ✓ Created languages/" -ForegroundColor Gray

Write-Host ""
Write-Host "Creating ZIP archive..." -ForegroundColor Cyan

# Create ZIP file
Compress-Archive -Path $destDir -DestinationPath $outputFile -CompressionLevel Optimal

# Clean up temp directory
Remove-Item $tempDir -Recurse -Force

# Get file size
$fileSize = (Get-Item $outputFile).Length
$fileSizeMB = [math]::Round($fileSize / 1MB, 2)

Write-Host ""
Write-Host "✓ Package created successfully!" -ForegroundColor Green
Write-Host ""
Write-Host "Output file: $outputFile" -ForegroundColor White
Write-Host "File size: $fileSizeMB MB" -ForegroundColor White
Write-Host ""
Write-Host "Installation Instructions:" -ForegroundColor Yellow
Write-Host "1. Go to WordPress Admin → Plugins → Add New" -ForegroundColor Gray
Write-Host "2. Click 'Upload Plugin' and select: $outputFile" -ForegroundColor Gray
Write-Host "3. Click 'Install Now' and then 'Activate'" -ForegroundColor Gray
Write-Host "4. Configure your Nivoda API key in Nivoda Diamonds → Settings" -ForegroundColor Gray
Write-Host ""
Write-Host "Done! 🎉" -ForegroundColor Green
