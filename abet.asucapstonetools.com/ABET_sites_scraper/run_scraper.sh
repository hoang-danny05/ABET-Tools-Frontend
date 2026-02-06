#!/bin/bash
#Path to the project
cd /home/osburn/public_html/abet.asucapstonetools.com/ABET_sites_scraper/ || exit

#Time stamp for each log entry
echo "========== $(date '+%Y-%m-%d %H:%M:%S') ==========" >> cron_run.log

#Run the abet_scraper.py
/usr/bin/python3 abet_scraper.py .. cron_run.log 2>&1
if [ $? -ne 0 ]; then
    echo "abet_scraper.py failed" >> cron_run.log
fi   

#Run the canvas_down_up.py
/usr/bin/python3 canvas_down_up.py .. cron_run.log 2>&1
if [ $? -ne 0 ]; then
    echo "canvas_down_up.py failed" >> cron_run.log
fi    

#Run the email_auto.py
/usr/bin/python3 email_auto.py .. cron_run.log 2>&1
if [ $? -ne 0 ]; then
    echo "email_auto.py failed" >> cron_run.log
fi

echo "All scripts completed" >> cron_run.log