#Import
import os
import smtplib #for email sending
from email.message import EmailMessage
import difflib
from canvas_down_up import upload_file, COURSE_ID, FOLDER_NAME
from dotenv import load_dotenv
load_dotenv()
import time

import smtplib
from email.message import EmailMessage

#Import Canvas API related modules if needed
import requests
import sys

#Configurations of the two files to compare
#OLD_FILE_CS = "old_cs_criteria.txt"
#NEW_FILE_CS = "cs_criteria.txt"


SMTP_HOST = os.getenv("SMTP_HOST", "").strip()
SMTP_PORT = int(os.getenv("SMTP_PORT", "465").strip() or 465)                         
EMAIL_SENDER = os.getenv("EMAIL_SENDER", "").strip()
PASSWORD = os.getenv("PASSWORD", "").strip()   


def diff_files(old_file_path: str, new_file_path: str, content: str) -> None:
    time.sleep(1)
    #check files existencies
    if not os.path.exists(old_file_path):
        print(f"{old_file_path} does not exist. Creating a new one.")
        exit(1)

    if not os.path.exists(new_file_path):
        print(f"{new_file_path} does not exist. Creating a new one.")
        exit(1)

    with open(old_file_path, 'r') as old_file_cs, open(new_file_path, 'r') as new_file_cs:
        old_data_cs = old_file_cs.readlines()
        new_data_cs = new_file_cs.readlines()

    # Generate diff
    diff_cs = difflib.unified_diff(old_data_cs, new_data_cs, fromfile='Old CS Criteria', tofile='New CS Criteria', lineterm='')
    diff_list = list(diff_cs)

    if diff_list:
        #demo print diff
        diff_text = '\n'.join(diff_list)
        print("Differences found:")
        print(diff_text)
              

        msg = EmailMessage()
        msg['Subject'] = 'CS Datafile Changes Detected'
        msg['From'] = EMAIL_SENDER
        msg['To'] = "mgoisman@asu.edu" #thaituan@asu.edu
        msg.set_content(f"The following changes were detected between the old and new CS data files:\n\n{diff_text}" + __import__('datetime').datetime.now().isoformat())
        
        
        try:
            upload_file(COURSE_ID, FOLDER_NAME, new_file_path)
            print("Upload succeeded.")
        except Exception as e:
            print("Upload failed:", e)
        
    
        try:
            with smtplib.SMTP_SSL(SMTP_HOST, SMTP_PORT, timeout=30) as smtp:
                smtp.login(EMAIL_SENDER, PASSWORD)
                smtp.send_message(msg)
            print("Email sent to thaituan@asu.edu")
        except Exception as e:
            print(f"Error sending email: {e}")
            
        with open(old_file_path, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Text saved to {old_file_path}")    
            
        
        return
            
def runprog(old, new, text):
    
    
    old_file = old
    new_file = new
    #diff_files(OLD_FILE_CS, NEW_FILE_CS)
    diff_files(old_file, new_file, text)

#main is here if you want to test just this file
#if __name__ == '__main__':

 #   runprog()


#How to use:
#Set up .env file with necessary info (see .env file for reference)
#Create SendGrid account and get API key

#What to do:
#Update the contect of the old_datafile.txt with the content of current CS_datafile.txt after verifying the changes
#The new CS_datafile.txt should from the most recent data (run ABET_CS.ipynb) while the oldCS_datafile.txt should be downloaded from the Canvas and should be replaced if there is difference detected
#Automate this script to run at desired intervals - currently manual run but desired to be weekly or monthly