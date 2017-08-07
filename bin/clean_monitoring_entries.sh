#!/bin/bash
# cleans up monitoring_history items when the ip is nolonger monitored

#this is for removing all monitoring entries for yahoo clients since im blocked from there
#disabled this for now as itts not really meant to be run, just the code minus the yahoo part is reussable
if [ 1 -eq 0 ]; then
	for id in $(echo " select monitoring_id from monitoring left join accounts on account_id=monitoring_custid WHERE account_lid like '%@yahoo.%';" |mysql -s my); do
		echo "delete from monitoring where monitoring_id='$id';";
	done |mysql my
fi

for ip in $(echo "select history_type from monitoring_history left join monitoring on history_type=monitoring_ip where monitoring_id is null group by history_type;"| mysql -s my); do
	echo "delete from monitoring_history where history_type='$ip';";
done | mysql my

