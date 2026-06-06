#!/usr/bin/env bash
# Keluar dari script jika ada error terjadi
set -o errexit

# 1. Instalasi dependensi backend dan frontend
composer install --no-dev --optimize-autoloader
npm install

# 2. Kompilasi/Bundle aset Vue 3 + Tailwind menjadi file siap saji
npm run build

# 3. Jalankan optimasi cache Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache