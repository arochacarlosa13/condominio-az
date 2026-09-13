@echo off
title Iniciar Condominio SaaS
echo ==========================================
echo  Iniciando Condominio SaaS Completo...
echo ==========================================
start "Backend Laravel" "%~dp0iniciar_servidor.bat"
start "Frontend Vite" "%~dp0iniciar_frontend.bat"
echo Servidores iniciados en ventanas separadas.
timeout /t 3
