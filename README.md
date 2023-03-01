# pihole-sync

pihole-sync is a tool for synchronizing local dns and cname records from master-pinhole to slave-pinhole server.

**Installation**

For master-pinhole server:

*   Copy files from the folder named 'server' to the master-pinhole server.
*   Write the secret key in the file '/var/www/sync.server.php'.
*   Create a link for the file '/etc/lighttpd/20-pihole-sync.conf' with command:

```shell
cd /etc/lighttpd/conf-enabled && \
ln -s ../conf-available/20-pihole-sync.conf 20-pihole-sync.conf
```

*   Restart lighttpd with command (Debian example):

```shell
systemctl restart lighttpd.service
```

*   Check server by opening the link 'http://{master-pihole-ip}/sync/{secret-key}/cname/hash' in the browser. The result should be 'ok:{sha256-hash}'

For slave-pinhole server:

*   Copy files from the folder named 'client' to the slave-pinhole server.
*   Write the secret key (same as master-pinhole) in the file '/root/sync.client.php'.
*   Check client by executing the command:

```shell
/usr/bin/php /root/sync.client.php
```

*   create cron job:

```
*/30 * * * * /usr/bin/php /root/sync.client.php > /dev/null 2>&1
```
