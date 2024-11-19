#Requires -Version 5.1
#Requires -RunAsAdministrator
#Requires -Modules @{ ModuleName='Microsoft.PowerShell.Archive'; ModuleVersion='1.0.0.0' }

<#
.SYNOPSIS
    Builds a production-ready WordPress plugin package.
    
.DESCRIPTION
    Creates a minified, optimized deployment package for WordPress plugins.
    Handles asset optimization, file exclusion, and package creation.
    
.PARAMETER PluginName
    The name of the plugin. Defaults to "wp-post-to-pdf"
    
.PARAMETER Environment
    Build environment. Can be 'production' or 'development'. Defaults to 'production'
    
.PARAMETER SkipMinification
    Skip the minification of CSS and JS files
    
.PARAMETER OutputPath
    Custom output path for the build. Defaults to ".\dist"
    
.PARAMETER Verbose
    Show detailed progress information
    
.EXAMPLE
    .\build.ps1 -PluginName "my-plugin" -Environment "development"
    
.EXAMPLE
    .\build.ps1 -SkipMinification -OutputPath "C:\builds"
    
.NOTES
    Author: Your Name
    Version: 1.0.0
    Requires PowerShell 5.1 or later
#>

[CmdletBinding()]
param(
    [Parameter(Position = 0)]
    [string]$PluginName = "wp-post-to-pdf",
    
    [Parameter()]
    [ValidateSet('production', 'development')]
    [string]$Environment = 'production',
    
    [Parameter()]
    [switch]$SkipMinification,
    
    [Parameter()]
    [string]$OutputPath = ".\dist",
    
    [Parameter()]
    [string]$BuildDir = ".\build"
)

# Set strict mode for better error handling
Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

# Script Variables
$script:startTime = Get-Date
$script:sourceDir = $PSScriptRoot

# Initialize logging
function Write-BuildLog {
    param(
        [Parameter(Mandatory)]
        [string]$Message,
        
        [Parameter()]
        [ValidateSet('Info', 'Warning', 'Error', 'Success')]
        [string]$Level = 'Info'
    )
    
    $colors = @{
        'Info' = 'White'
        'Warning' = 'Yellow'
        'Error' = 'Red'
        'Success' = 'Green'
    }
    
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    Write-Host "[$timestamp] " -NoNewline
    Write-Host $Message -ForegroundColor $colors[$Level]
    
    # Log to file if needed
    if ($Environment -eq 'development') {
        $logMessage = "[$timestamp] [$Level] $Message"
        Add-Content -Path ".\build.log" -Value $logMessage
    }
}

# Add these helper functions at the top after the existing Write-BuildLog function

function Write-ProgressHeader {
    param([string]$Title)
    
    Write-Host "`n========================================" -ForegroundColor Cyan
    Write-Host "  $Title" -ForegroundColor Cyan
    Write-Host "========================================" -ForegroundColor Cyan
}

function Write-StepProgress {
    param(
        [string]$Step,
        [int]$Current,
        [int]$Total
    )
    
    $percentage = [math]::Round(($Current / $Total) * 100)
    $progressBar = "[" + ("=" * [math]::Floor($percentage / 2)) + (" " * [math]::Ceiling((100 - $percentage) / 2)) + "]"
    
    Write-Host "`r$Step $progressBar $percentage%" -NoNewline
    if ($Current -eq $Total) {
        Write-Host ""
    }
}

# Version detection function
function Get-PluginVersion {
    $pluginFile = Join-Path $script:sourceDir "$PluginName.php"
    
    if (-not (Test-Path $pluginFile)) {
        throw "Plugin file not found at: $pluginFile"
    }

    try {
        $content = Get-Content -Path $pluginFile -Raw
        $versionPattern = '(?<=Version:\s+)((?:\d+\.?)+)(?:\s|$)'
        $versionMatch = [regex]::Match($content, $versionPattern)
        
        if (-not $versionMatch.Success) {
            throw "Version not found in plugin file"
        }

        $version = $versionMatch.Value.Trim()
        
        if (-not ($version -match '^\d+\.\d+\.\d+$')) {
            throw "Invalid version format. Expected x.x.x, got: $version"
        }

        Write-BuildLog "Detected plugin version: $version" -Level Success
        return $version
    }
    catch {
        throw "Error reading plugin version: $_"
    }
}

# Asset optimization functions
function Optimize-CSS {
    param([string]$Content)
    
    if ($SkipMinification) { return $Content }
    
    try {
        $Content = $Content -replace "/\*[\s\S]*?\*/|//.*", ""
        $Content = $Content -replace "\s+", " "
        $Content = $Content -replace "\s*{\s*", "{"
        $Content = $Content -replace "\s*}\s*", "}"
        $Content = $Content -replace "\s*:\s*", ":"
        $Content = $Content -replace "\s*;\s*", ";"
        $Content = $Content -replace "\s*,\s*", ","
        return $Content.Trim()
    }
    catch {
        Write-BuildLog "CSS optimization failed: $_" -Level Warning
        return $Content
    }
}

function Optimize-JavaScript {
    param([string]$Content)
    
    if ($SkipMinification) { return $Content }
    
    try {
        $Content = $Content -replace "/\*[\s\S]*?\*/|//.*", ""
        $Content = $Content -replace "\s+", " "
        $Content = $Content -replace "\s*{\s*", "{"
        $Content = $Content -replace "\s*}\s*", "}"
        return $Content.Trim()
    }
    catch {
        Write-BuildLog "JavaScript optimization failed: $_" -Level Warning
        return $Content
    }
}

# Main build process
function Start-Build {
    try {
        Write-ProgressHeader "WordPress Plugin Build Process"
        Write-BuildLog "Starting build process at $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -Level Info
        Write-BuildLog "Plugin: $PluginName" -Level Info
        Write-BuildLog "Environment: $Environment" -Level Info
        Write-BuildLog "Minification: $(-not $SkipMinification)" -Level Info
        Write-BuildLog "Source Directory: $script:sourceDir" -Level Info
        Write-BuildLog "Build Directory: $BuildDir" -Level Info
        Write-BuildLog "Output Path: $OutputPath" -Level Info
        
        # Create necessary directories
        Write-ProgressHeader "Initializing Build Environment"
        $directories = @($BuildDir, $OutputPath)
        foreach ($dir in $directories) {
            Write-BuildLog "Creating directory: $dir" -Level Info
            if (Test-Path $dir) {
                Remove-Item $dir -Recurse -Force
                Write-BuildLog "Cleaned existing directory" -Level Info
            }
            New-Item -ItemType Directory -Path $dir | Out-Null
        }
        
        # Get plugin version
        Write-ProgressHeader "Detecting Plugin Version"
        $version = Get-PluginVersion
        Write-BuildLog "Building version: $version" -Level Success
        
        # Copy files
        Write-ProgressHeader "Copying Project Files"
        $tempDir = Join-Path $BuildDir "temp"
        Copy-ProjectFiles -Destination $tempDir
        
        # Optimize assets if not skipped
        if (-not $SkipMinification) {
            Write-ProgressHeader "Optimizing Assets"
            $assetTypes = @{
                "CSS" = "*.css"
                "JavaScript" = "*.js"
            }
            
            foreach ($type in $assetTypes.Keys) {
                $files = Get-ChildItem -Path $tempDir -Filter $assetTypes[$type] -Recurse
                $total = @($files).Count
                
                if ($total -gt 0) {
                    Write-BuildLog "Processing $total $type files..." -Level Info
                    $current = 0
                    
                    foreach ($file in $files) {
                        $current++
                        Write-StepProgress -Step "Optimizing $type" -Current $current -Total $total
                        
                        $content = Get-Content $file.FullName -Raw
                        if ($type -eq "CSS") {
                            $optimized = Optimize-CSS -Content $content
                        } else {
                            $optimized = Optimize-JavaScript -Content $content
                        }
                        
                        $minPath = $file.FullName -replace "\.$type$", ".min.$type"
                        Set-Content -Path $minPath -Value $optimized -NoNewline
                        Remove-Item $file.FullName
                    }
                    Write-BuildLog "Completed $type optimization" -Level Success
                }
            }
        }
        
        # Create distribution package
        Write-ProgressHeader "Creating Distribution Package"
        $zipFile = Join-Path $OutputPath "$PluginName-$version.zip"
        Write-BuildLog "Creating zip archive..." -Level Info
        Compress-Archive -Path "$tempDir\*" -DestinationPath $zipFile -Force
        
        # Cleanup
        Write-ProgressHeader "Cleaning Up"
        Write-BuildLog "Removing temporary files..." -Level Info
        if (Test-Path $BuildDir) {
            $tempFiles = Get-ChildItem -Path $BuildDir -Recurse
            $tempFileCount = @($tempFiles).Count
            Write-BuildLog "Cleaning up $tempFileCount temporary items..." -Level Info
            Remove-Item $BuildDir -Recurse -Force
            Write-BuildLog "Cleanup completed" -Level Success
        }
        
        # Build Summary
        $duration = (Get-Date) - $script:startTime
        Write-ProgressHeader "Build Summary"
        Write-BuildLog "Build completed successfully!" -Level Success
        Write-BuildLog "Duration: $($duration.TotalSeconds.ToString('0.00')) seconds" -Level Success
        Write-BuildLog "Output: $zipFile" -Level Success
        Write-BuildLog "Plugin version: $version" -Level Success
        Write-BuildLog "Environment: $Environment" -Level Success
        
        if (-not $SkipMinification) {
            Write-BuildLog "Assets optimized: CSS and JavaScript" -Level Success
        }
        
        Write-Host "`n========================================`n" -ForegroundColor Cyan
    }
    catch {
        Write-ProgressHeader "Build Failed"
        Write-BuildLog $_.Exception.Message -Level Error
        Write-BuildLog "Stack trace:" -Level Error
        Write-BuildLog $_.ScriptStackTrace -Level Error
        exit 1
    }
}

# Helper function to copy project files
function Copy-ProjectFiles {
    param([string]$Destination)
    
    Write-BuildLog "Scanning source directory for files..." -Level Info
    
    $excludePatterns = Get-Content (Join-Path $PSScriptRoot ".buildignore") -ErrorAction SilentlyContinue
    if (-not $excludePatterns) {
        $excludePatterns = @(
            '\.git',
            'node_modules',
            'vendor[\\/]development',
            '\.buildignore',
            'build\.ps1',
            'tests',
            '.*\.log$'
        )
    }
    
    # Get all files first
    $allFiles = Get-ChildItem -Path $script:sourceDir -Recurse -File
    $filesToCopy = [System.Collections.ArrayList]::new()
    $totalScanned = 0
    $totalIncluded = 0
    
    Write-BuildLog "Analyzing files for inclusion..." -Level Info
    foreach ($file in $allFiles) {
        $totalScanned++
        Write-Progress -Activity "Analyzing Files" -Status "Scanned $totalScanned files" -PercentComplete (($totalScanned / $allFiles.Count) * 100)
        
        $relativePath = $_.FullName.Substring($script:sourceDir.Length + 1)
        $exclude = $false
        
        foreach ($pattern in $excludePatterns) {
            if ($relativePath -match $pattern) {
                $exclude = $true
                break
            }
        }
        
        if (-not $exclude) {
            $totalIncluded++
            $null = $filesToCopy.Add($file)
        }
    }
    Write-Progress -Activity "Analyzing Files" -Completed
    
    Write-BuildLog "Found $totalScanned files, including $totalIncluded for copying" -Level Success
    
    # Copy files with progress
    $current = 0
    foreach ($file in $filesToCopy) {
        $current++
        $relativePath = $file.FullName.Substring($script:sourceDir.Length + 1)
        $targetPath = Join-Path $Destination $relativePath
        $targetDir = Split-Path $targetPath -Parent
        
        $percentComplete = ($current / $filesToCopy.Count) * 100
        $progressBar = "[" + ("=" * [math]::Floor($percentComplete / 2)) + (" " * [math]::Ceiling((100 - $percentComplete) / 2)) + "]"
        
        Write-Host "`rCopying files $progressBar $($percentComplete.ToString('0.0'))% ($current of $($filesToCopy.Count))" -NoNewline
        
        if (!(Test-Path $targetDir)) {
            New-Item -ItemType Directory -Path $targetDir -Force | Out-Null
        }
        
        Copy-Item $file.FullName -Destination $targetPath -Force
        
        # Every 50 files, show detailed progress
        if ($current % 50 -eq 0) {
            Write-BuildLog "Copied $current of $($filesToCopy.Count) files..." -Level Info
        }
    }
    Write-Host "" # New line after progress bar
    Write-BuildLog "Completed copying $($filesToCopy.Count) files" -Level Success
}

# Start the build process
Start-Build
