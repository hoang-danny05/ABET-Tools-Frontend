import requests
import os
from dotenv import load_dotenv
load_dotenv()


CANVAS_BASE = os.getenv("CANVAS_BASE")
API_TOKEN = os.getenv("API_TOKEN")
COURSE_ID = os.getenv("COURSE_ID")
FOLDER_NAME = os.getenv("FOLDER_NAME")
FILE_NAME = os.getenv("FILE_NAME")
DESTINATION_PATH = os.getenv("DESTINATION_PATH")

if not all([CANVAS_BASE, API_TOKEN, COURSE_ID, FOLDER_NAME, FILE_NAME]):
    print("One or more required environment variables are missing.")
    exit(1)

headers = {"Authorization": f"Bearer {API_TOKEN}"}

def find_folder_id(course_id: str, folder_name: str) -> str:
    url = f"{CANVAS_BASE}/api/v1/courses/{course_id}/folders"
    response = requests.get(url, headers=headers)
    response.raise_for_status()
    folders = response.json()
    for folder in folders:
        if folder['name'] == folder_name:
            return folder['id']
    print(f"Folder '{folder_name}' not found in course {course_id}.")
    exit(1)

def find_file_id(folder_id: str, file_name: str) -> str:
    url = f"{CANVAS_BASE}/api/v1/folders/{folder_id}/files"
    response = requests.get(url, headers=headers)
    response.raise_for_status()
    files = response.json()
    for file in files:
        if file['display_name'] == file_name:
            return file['id']
    print(f"File '{file_name}' not found in folder {folder_id}.")
    exit(1)

def download_file(course_id: str, folder_name: str, file_name: str, file_path: str) -> None:
    folder_id = find_folder_id(course_id, folder_name)
    file_id = find_file_id(str(folder_id), file_name)
    url = f"{CANVAS_BASE}/api/v1/files/{file_id}"
    meta_resp = requests.get(url, headers=headers)
    meta_resp.raise_for_status()
    file_info = meta_resp.json()
    download_url = file_info['url']
    response = requests.get(download_url, headers=headers)
    response.raise_for_status()

    with open(file_path, 'wb') as file:
        file.write(response.content)


    print(f"Folder ID for '{FOLDER_NAME}': {folder_id}")
    print(f"File ID for '{FILE_NAME}': {file_id}")
    print(f"File downloaded to {file_path}")

def upload_file(course_id: str, folder_name: str, file_path: str) -> None:
    folder_id = find_folder_id(course_id, folder_name)
    url = f"{CANVAS_BASE}/api/v1/folders/{folder_id}/files"
    file_name = os.path.basename(file_path)
    file_size = os.path.getsize(file_path)

    metadata = {
        "name": file_name,
        "size": str(file_size),
        "content_type": "text/plain",
        "on_duplicate": "overwrite"
    }

    meta_resp = requests.post(url, headers=headers, data=metadata)
    meta_resp.raise_for_status()
    upload_info = meta_resp.json()
    upload_url = upload_info['upload_url']
    upload_params = upload_info['upload_params']

    with open(file_path, 'rb') as file_data:
        files = {'file': (file_name, file_data, "text/plain")}
        response = requests.post(upload_url, data = upload_params, headers=headers, files=files)
    response.raise_for_status()
    
    print(f"File '{file_path}' uploaded to folder '{folder_name}' in course {course_id}.")
    #print(file_size)

if __name__ == '__main__':
    """
    Downloading the file from Canvas
    """
    download_file(COURSE_ID, FOLDER_NAME, FILE_NAME, DESTINATION_PATH)