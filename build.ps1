#Requires -Version 5.1
#Requires -RunAsAdministrator
#Requires -Modules @{ ModuleName='Microsoft.PowerShell.Archive'; ModuleVersion='1.0.0.0' }

<#
.SYNOPSIS
    Builds a production-ready WordPress plugin package.
    
.DESCRIPTION
    Creates an optimized deployment package for WordPress plugins.
    Handles file exclusion and package creation.
    
.PARAMETER PluginName
    The name of the plugin. Defaults to "wp-post-to-pdf"
    
.PARAMETER Environment
    Build environment. Can be 'production' or 'development'. Defaults to 'production'
    
.PARAMETER OutputPath
    Custom output path for the build. Defaults to ".\dist"
    
.PARAMETER Verbose
    Show detailed progress information
    
.EXAMPLE
    .\build.ps1 -PluginName "my-plugin" -Environment "development"
    
.EXAMPLE
    .\build.ps1 -OutputPath "C:\builds"
    
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
$script:TempPath = Join-Path $BuildDir "temp"

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

# Main build process
function Start-Build {
    try {
        Write-ProgressHeader "WordPress Plugin Build Process"
        Write-BuildLog "Starting build process at $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -Level Info
        Write-BuildLog "Plugin: $PluginName" -Level Info
        Write-BuildLog "Environment: $Environment" -Level Info
        Write-BuildLog "Output Path: $OutputPath" -Level Info
        
        # Install Composer dependencies
        Write-BuildLog "Installing Composer dependencies..." -Level Info
        if (!(Test-Path (Join-Path $script:sourceDir "vendor"))) {
            $composerResult = Invoke-Expression "composer install --no-dev --optimize-autoloader"
            if ($LASTEXITCODE -ne 0) {
                throw "Composer install failed: $composerResult"
            }
            Write-BuildLog "Composer dependencies installed successfully" -Level Success
        } else {
            Write-BuildLog "Vendor directory already exists, skipping Composer install" -Level Info
        }
        
        Initialize-BuildEnvironment
        $buildPath = Copy-ProjectFiles -Destination $script:TempPath
        
        # Create the distribution package
        $distPath = New-Item -ItemType Directory -Path $OutputPath -Force
        $version = Get-PluginVersion
        $zipPath = Join-Path $distPath "$PluginName-$version.zip"
        
        if (Test-Path $zipPath) {
            Remove-Item $zipPath -Force
        }
        
        Compress-Archive -Path "$buildPath\*" -DestinationPath $zipPath
        Write-BuildLog "Created plugin package at: $zipPath" -Level Success
        
        # Cleanup
        if (Test-Path $script:TempPath) {
            Remove-Item $script:TempPath -Recurse -Force
        }
        
        Write-BuildLog "Build completed successfully!" -Level Success
    }
    catch {
        Write-BuildLog "Build failed: $_" -Level Error
        throw $_
    }
}

function Initialize-BuildEnvironment {
    Write-ProgressHeader "Initializing Build Environment"
    $directories = @($script:TempPath, $OutputPath)
    foreach ($dir in $directories) {
        Write-BuildLog "Creating directory: $dir" -Level Info
        if (Test-Path $dir) {
            Remove-Item $dir -Recurse -Force
            Write-BuildLog "Cleaned existing directory" -Level Info
        }
        New-Item -ItemType Directory -Path $dir | Out-Null
    }
}

# Helper function to copy project files
function Copy-ProjectFiles {
    param([string]$Destination)
    
    Write-BuildLog "Scanning source directory for files..." -Level Info
    
    $excludePatterns = @(
        '\.git',
        'node_modules',
        '\.buildignore',
        'build\.ps1',
        'build',
        'dist',
        'tests',
        '.*\.log$'
    )

    # Get all files first
    $allFiles = Get-ChildItem -Path $script:sourceDir -Recurse -File
    $filesToCopy = [System.Collections.ArrayList]::new()
    $totalScanned = 0
    $totalIncluded = 0
    
    Write-BuildLog "Analyzing files for inclusion..." -Level Info
    foreach ($file in $allFiles) {
        $totalScanned++
        Write-Progress -Activity "Analyzing Files" -Status "Scanned $totalScanned files" -PercentComplete (($totalScanned / $allFiles.Count) * 100)
        
        $relativePath = $file.FullName.Substring($script:sourceDir.Length).TrimStart('\')
        $exclude = $false

        # Special handling for vendor files
        if ($relativePath -like "vendor*") {
            # Always include these vendor paths
            if (($relativePath -like "vendor\dompdf\*") -or
                ($relativePath -like "vendor\phenx\*") -or
                ($relativePath -like "vendor\sabberworm\*") -or
                ($relativePath -like "vendor\masterminds\*") -or
                ($relativePath -like "vendor\autoload.php") -or
                ($relativePath -like "vendor\composer\*")) {
                $exclude = $false
                Write-BuildLog "Including vendor file: $relativePath" -Level Info
            } else {
                $exclude = $true
                Write-BuildLog "Excluding vendor file: $relativePath" -Level Info
            }
        } else {
            # Check other exclusion patterns
            foreach ($pattern in $excludePatterns) {
                if ($relativePath -match $pattern) {
                    $exclude = $true
                    Write-BuildLog "Excluded: $relativePath (matched pattern: $pattern)" -Level Info
                    break
                }
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
        $relativePath = $file.FullName.Substring($script:sourceDir.Length)
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
    
    return $Destination
}

# Start the build process
Start-Build

# Plugin metadata
$metadata = @"
=== WP Post to PDF ===
Contributors: pimzino
Tags: pdf, export, posts, print
Requires at least: 5.2
Tested up to: 6.4
Requires PHP: 7.2
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Version: 1.1.0

A powerful WordPress plugin that enables users to export blog posts to beautifully formatted, printable PDFs with extensive customization options.
"@
