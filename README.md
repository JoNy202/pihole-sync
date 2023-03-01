# pihole-sync

pihole-sync is a tool for synchronizing local dns and cname records from master-pihole to slave-pihole server.

**Installation**

For master-pihole server:

1.  Copy files from the folder named 'server' to the master-pihole server.
2.   Write the secret key in the file '/var/www/sync.server.php'.
3.   Create a link for the file '/etc/lighttpd/20-pihole-sync.conf' with command:

```shell
cd /etc/lighttpd/conf-enabled && \
ln -s ../conf-available/20-pihole-sync.conf 20-pihole-sync.conf
```

4.   Restart lighttpd with command (Debian example):

```shell
systemctl restart lighttpd.service
```

5.   Check server by opening the link 'http://{master-pihole-ip}/sync/{secret-key}/cname/hash' in the browser. The result should be 'ok:{sha256-hash}'

For slave-pihole server:

1.   Copy files from the folder named 'client' to the slave-pihole server.
2.   Write the secret key (same as master-pihole) in the file '/root/sync.client.php'.
3.   Check client by executing the command:

```shell
/usr/bin/php /root/sync.client.php
```

4.   create cron job:

```
*/30 * * * * /usr/bin/php /root/sync.client.php > /dev/null 2>&1
```
