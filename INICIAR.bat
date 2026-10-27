@echo off
cd /d "%~dp0"
echo Abre http://127.0.0.1:8000 en tu navegador.
C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000
pause
