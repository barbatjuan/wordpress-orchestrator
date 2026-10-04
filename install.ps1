# WordPress Orchestrator framework installer (Windows / PowerShell)
# Copies agents/ and skills/ into the user's ~/.claude so Claude Code can load them.
#
#   ./install.ps1            overwrite in place (a file deleted upstream is NOT removed)
#   ./install.ps1 --clean    first remove, from the destination, every top-level entry THIS repo
#                            ships (each skill folder, each top-level file under skills/, each file
#                            under agents/) and copy the repo's version fresh, so a file retired
#                            inside a framework skill stops lingering. Anything the repo does not
#                            name is left untouched. A whole skill or agent the repo retired by
#                            name is NOT removed: no manifest is kept, so delete it by hand.
#
# $env:INSTALL_DEST overrides the destination (default ~/.claude); it exists so the installer can be
# tested against a temp directory. Set to blank it is an error, never "use the default".
#
# ASCII only, deliberately. Windows PowerShell 5.1 reads a BOM-less .ps1 as the system ANSI
# codepage, so a UTF-8 em-dash arrives as three bytes whose last one PowerShell treats as a closing
# quote -- the string terminates early and the whole script fails to parse. Measured: this file
# refused to run under 5.1 while working under 7. Keep every character in this file ASCII.
$ErrorActionPreference = 'Stop'
$src = $PSScriptRoot

$clean = $false
foreach ($arg in $args) {
    if ($arg -eq '--clean') { $clean = $true }
    else {
        [Console]::Error.WriteLine("install.ps1: unknown argument '$arg' (the only option is --clean)")
        exit 2
    }
}

if ($null -eq $env:INSTALL_DEST) { $dest = Join-Path $HOME '.claude' }
elseif ($env:INSTALL_DEST.Trim() -eq '') {
    [Console]::Error.WriteLine('install.ps1: INSTALL_DEST is set but blank; refusing to guess a destination')
    exit 2
}
else { $dest = $env:INSTALL_DEST }

# Copying a checkout onto itself is never meant, and --clean would delete the repo's own skills/ and
# agents/. GetFullPath folds trailing slashes and .. ; Windows paths compare case-insensitively.
foreach ($sub in 'skills', 'agents') {
    $a = [System.IO.Path]::GetFullPath((Join-Path $dest $sub)).TrimEnd('\', '/')
    $b = [System.IO.Path]::GetFullPath((Join-Path $src $sub)).TrimEnd('\', '/')
    if ($a -eq $b) {
        [Console]::Error.WriteLine("install.ps1: INSTALL_DEST resolves to this checkout ($sub/ is the source itself); refusing, nothing changed")
        exit 2
    }
}

if ($clean) {
    $replaced = @()
    foreach ($sub in 'skills', 'agents') {
        foreach ($entry in Get-ChildItem -LiteralPath (Join-Path $src $sub) -Force) {
            $target = Join-Path (Join-Path $dest $sub) $entry.Name
            if (Test-Path -LiteralPath $target) { Remove-Item -LiteralPath $target -Recurse -Force }
            $replaced += "$sub/$($entry.Name)"
        }
    }
    Write-Host ("--clean: replaced only what this repo ships: " + ($replaced -join ', ')) -ForegroundColor Yellow
}

New-Item -ItemType Directory -Force -Path (Join-Path $dest 'agents') | Out-Null
New-Item -ItemType Directory -Force -Path (Join-Path $dest 'skills') | Out-Null

Copy-Item -Path (Join-Path $src 'agents\*')  -Destination (Join-Path $dest 'agents')  -Recurse -Force
Copy-Item -Path (Join-Path $src 'skills\*')  -Destination (Join-Path $dest 'skills')  -Recurse -Force

# Report exactly what is on disk, so this message can't drift from the repo.
$agents = Get-ChildItem -Path (Join-Path $src 'agents') -Filter '*.md' -File |
          Sort-Object Name | ForEach-Object { $_.BaseName }
$skills = Get-ChildItem -Path (Join-Path $src 'skills') -Directory |
          Sort-Object Name | ForEach-Object { $_.Name }

Write-Host "WordPress Orchestrator framework installed into $dest" -ForegroundColor Green
Write-Host ("Agents ({0}): {1}" -f $agents.Count, ($agents -join ', '))
Write-Host ("Skills ({0}): {1}" -f $skills.Count, ($skills -join ', '))
Write-Host ""
if (-not $clean) {
    Write-Host "NOTE: this is an overwrite-in-place install. Files with the same name are replaced," -ForegroundColor Yellow
    Write-Host "but files deleted upstream are NOT removed from $dest -- re-run with --clean to drop files" -ForegroundColor Yellow
    Write-Host "retired inside a framework skill; a whole retired skill or agent has to be deleted by hand." -ForegroundColor Yellow
}
