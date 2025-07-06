import sys
import os
import subprocess
import traceback

def check_dependency(module_name):
    """Memeriksa apakah modul dapat diimpor"""
    try:
        __import__(module_name)
        return True, None
    except ImportError as e:
        return False, str(e)
    except Exception as e:
        return False, str(e)

def run_command(command):
    """Menjalankan perintah shell dan mengembalikan output"""
    try:
        result = subprocess.run(command, shell=True, text=True, capture_output=True)
        return result.returncode == 0, result.stdout, result.stderr
    except Exception as e:
        return False, "", str(e)

def main():
    print(f"=== DIAGNOSA SERVER YOLO ===")
    print(f"Python version: {sys.version}")
    print(f"Python executable: {sys.executable}")
    print(f"Current directory: {os.getcwd()}")

    # Cek keberadaan file-file penting
    script_dir = os.path.dirname(os.path.abspath(__file__))
    print(f"\n=== FILE PENTING ===")
    for file_name in ["run_yolo_server.py", "NEW inferensi model dari gpt.py", "best14.pt"]:
        file_path = os.path.join(script_dir, file_name)
        if os.path.exists(file_path):
            size = os.path.getsize(file_path)
            print(f"✅ {file_name} ada ({size} bytes)")
        else:
            print(f"❌ {file_name} tidak ditemukan!")

    # Cek dependensi
    print(f"\n=== DEPENDENSI ===")
    dependencies = ["flask", "ultralytics", "rasterio", "shapely", "geopandas", "numpy", "cv2", "PIL", "matplotlib"]

    for dep in dependencies:
        success, error = check_dependency(dep)
        if success:
            print(f"✅ {dep} terinstal")
        else:
            print(f"❌ {dep} tidak terinstal: {error}")

    # Coba jalankan server dengan timeout
    print(f"\n=== COBA JALANKAN SERVER ===")
    python_exec = sys.executable
    server_script = os.path.join(script_dir, "run_yolo_server.py")

    try:
        print(f"Mencoba menjalankan: {python_exec} {server_script}")
        # Jalankan dengan timeout 10 detik
        process = subprocess.Popen(
            [python_exec, server_script],
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True
        )

        try:
            stdout, stderr = process.communicate(timeout=10)
            print(f"Server berhenti dengan kode: {process.returncode}")
            print(f"Output:")
            print(stdout)
            if stderr:
                print(f"Error:")
                print(stderr)
        except subprocess.TimeoutExpired:
            process.kill()
            print("✅ Server berjalan lebih dari 10 detik (kemungkinan besar berhasil start)")
            stdout, stderr = process.communicate()
            print(f"Output awal:")
            print(stdout[:1000])  # Batasi output
    except Exception as e:
        print(f"❌ Gagal menjalankan server: {str(e)}")
        traceback.print_exc()

if __name__ == "__main__":
    main()
