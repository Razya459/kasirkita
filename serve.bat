@echo off
echo ====================================================
echo  RestoKita - POS Kasir Restoran
echo  Server berjalan di: http://localhost:8000
echo  Tekan Ctrl+C untuk menghentikan server
echo ====================================================
php -S localhost:8000 -t public
pause
