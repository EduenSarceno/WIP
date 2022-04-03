@echo off
where python >NUL 2>&1
if "x-%ErrorLevel%" neq "x-0" echo.Error: Python no se ha encontrado en %%PATH%%, asegurate de haberlo instalado y configurado en el equipo &&exit /b 1
python app %*

