# pihole-sync

client cron job:

```
*/30 * * * * /usr/bin/php /root/sync.client.php > /dev/null 2>&1
```
