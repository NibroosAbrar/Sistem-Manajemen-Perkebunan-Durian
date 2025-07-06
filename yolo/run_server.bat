@echo off
echo Menjalankan server YOLO...
cd "C:\laragon\www\laravel11\yolo"
python run_yolo_server.py 5000 > "C:\laragon\www\laravel11\storage\logs/yolo_server.log" 2>&1
