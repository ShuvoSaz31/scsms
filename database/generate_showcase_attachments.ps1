param(
    [string] $OutputDirectory = (Join-Path $PSScriptRoot "..\uploads\showcase")
)

Add-Type -AssemblyName System.Drawing
$OutputDirectory = [System.IO.Path]::GetFullPath($OutputDirectory)
New-Item -ItemType Directory -Path $OutputDirectory -Force | Out-Null

$evidenceTypes = @(
    "Network connectivity",
    "Classroom equipment",
    "Facilities maintenance",
    "Transport service",
    "Library access"
)

for ($index = 0; $index -lt $evidenceTypes.Count; $index++) {
    $bitmap = New-Object System.Drawing.Bitmap(900, 560)
    $graphics = [System.Drawing.Graphics]::FromImage($bitmap)
    $background = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(248, 249, 250))
    $charcoal = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(33, 37, 41))
    $orange = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(253, 126, 20))
    $muted = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(108, 117, 125))
    $headingFont = New-Object System.Drawing.Font("Arial", 25, [System.Drawing.FontStyle]::Bold)
    $bodyFont = New-Object System.Drawing.Font("Arial", 18, [System.Drawing.FontStyle]::Regular)
    $smallFont = New-Object System.Drawing.Font("Arial", 13, [System.Drawing.FontStyle]::Regular)

    try {
        $graphics.Clear([System.Drawing.Color]::FromArgb(248, 249, 250))
        $graphics.FillRectangle($charcoal, 0, 0, 900, 92)
        $graphics.FillRectangle($orange, 0, 92, 900, 7)
        $graphics.DrawString("SCSMS SHOWCASE EVIDENCE", $headingFont, [System.Drawing.Brushes]::White, 34, 28)
        $graphics.DrawString("Sample attachment: $($evidenceTypes[$index])", $bodyFont, $charcoal, 42, 150)
        $graphics.DrawString("Generated demo file $($index + 1) of $($evidenceTypes.Count)", $smallFont, $muted, 42, 198)
        $graphics.DrawRectangle([System.Drawing.Pens]::LightGray, 40, 246, 820, 240)
        $graphics.FillRectangle($orange, 42, 248, 12, 236)
        $graphics.DrawString("This image is synthetic showcase data.", $bodyFont, $charcoal, 82, 280)
        $graphics.DrawString("Replace with real evidence only when appropriate.", $smallFont, $muted, 82, 326)
        $bitmap.Save((Join-Path $OutputDirectory ("showcase-evidence-{0:D2}.png" -f ($index + 1))), [System.Drawing.Imaging.ImageFormat]::Png)
    }
    finally {
        $graphics.Dispose()
        $bitmap.Dispose()
        $background.Dispose()
        $charcoal.Dispose()
        $orange.Dispose()
        $muted.Dispose()
        $headingFont.Dispose()
        $bodyFont.Dispose()
        $smallFont.Dispose()
    }
}

$avatarDirectory = Join-Path (Split-Path $OutputDirectory -Parent) "avatars"
New-Item -ItemType Directory -Path $avatarDirectory -Force | Out-Null
$avatarColors = @(
    [System.Drawing.Color]::FromArgb(253, 126, 20),
    [System.Drawing.Color]::FromArgb(52, 58, 64),
    [System.Drawing.Color]::FromArgb(200, 90, 8),
    [System.Drawing.Color]::FromArgb(50, 132, 91),
    [System.Drawing.Color]::FromArgb(108, 117, 125)
)

for ($index = 1; $index -le 105; $index++) {
    $bitmap = New-Object System.Drawing.Bitmap 256, 256
    $graphics = [System.Drawing.Graphics]::FromImage($bitmap)
    $background = New-Object System.Drawing.SolidBrush($avatarColors[($index - 1) % $avatarColors.Count])
    $textColor = if (($index - 1) % $avatarColors.Count -eq 0) {
        [System.Drawing.Brushes]::Black
    } else {
        [System.Drawing.Brushes]::White
    }
    $avatarFont = New-Object System.Drawing.Font("Arial", 54, [System.Drawing.FontStyle]::Bold)
    $avatarLabel = if ($index -le 50) { "S" } elseif ($index -le 100) { "F" } else { "H" }
    $avatarLabel += "{0:D2}" -f (($index - 1) % 50 + 1)

    try {
        $graphics.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
        $graphics.Clear([System.Drawing.Color]::Transparent)
        $graphics.FillEllipse($background, 5, 5, 246, 246)
        $textSize = $graphics.MeasureString($avatarLabel, $avatarFont)
        $graphics.DrawString($avatarLabel, $avatarFont, $textColor, (256 - $textSize.Width) / 2, (256 - $textSize.Height) / 2)
        $avatarName = "showcase-avatar-{0:D3}.png" -f $index
        $bitmap.Save((Join-Path $avatarDirectory $avatarName), [System.Drawing.Imaging.ImageFormat]::Png)
    }
    finally {
        $graphics.Dispose()
        $bitmap.Dispose()
        $background.Dispose()
        $avatarFont.Dispose()
    }
}

Write-Output "Created $($evidenceTypes.Count) evidence PNGs and 105 showcase avatars."
