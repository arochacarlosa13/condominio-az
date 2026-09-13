@echo off
title Servidor Laravel - Condominio SaaS
set PATH=C:\php;C:\Program Files\nodejs;C:\Program Files\PostgreSQL\17\bin;%PATH%
echo Iniciando Servidor Laravel en http://127.0.0.1:8000 ...
php artisan serve
pause
