$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

if (-not (Test-Path .env)) {
    Copy-Item .env.example .env
    Write-Host '.env created from .env.example'
}

$envFile = Get-Content .env
if ($envFile -match '^APP_KEY=$') {
    $bytes = New-Object byte[] 32
    [System.Security.Cryptography.RandomNumberGenerator]::Fill($bytes)
    $key = 'base64:' + [Convert]::ToBase64String($bytes)
    ($envFile -replace '^APP_KEY=$', "APP_KEY=$key") | Set-Content .env
    Write-Host 'APP_KEY generated'
}

docker compose up -d --build --wait
if ($LASTEXITCODE -ne 0) { throw 'docker compose failed' }

$port = ((Get-Content .env) -match '^APP_PORT=' | Select-Object -First 1) -replace '^APP_PORT=', ''
if (-not $port) { $port = '8080' }
Write-Host ''
Write-Host "  App:      http://localhost:$port  (admin@example.com / password)"
Write-Host "  Swagger:  http://localhost:$port/docs"
Write-Host '  RabbitMQ: http://localhost:15672  (guest / guest)'
