#!/bin/bash
while [ true ]
do
   # 1秒钟执行一次
    n=`ps -ef | grep think:qss_send_edm_task | grep -v grep | wc -l`
    if [ $n -lt 1 ]
    then
        # 改成自己的目录
        cd /var/www/vhosts/qestsoln.com/qss.qestsoln.com/
        nohup /opt/plesk/php/7.3/bin/php think think:qss_send_edm_task >> /var/www/vhosts/qestsoln.com/qss.qestsoln.com/qss_mail_task.log 2>&1 &
    fi

    sleep 1
done
