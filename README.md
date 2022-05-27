# smoltemp
Simple temporary file upload handler in PHP 7.4, no database / SQL

## How?

1. Extract the release into a working web server running PHP 7.4. 
2. Set file permission for the directory to **777**. The files will be uploaded into the main directory.
3. Set file permission for *files.json* to **777**. 
4. For automatic time-outs / deletion of files, read the **Cronjob** section. 

## Cronjob
For automatic time-outs and deletion of files, create a *cronjob* in a Linux server that will execute every 10 minutes or however often you'd like to execute the script.
The script my cronjob executes every 10 minutes is:
```
curl https://example.com/temp/timeout.php?key=CHANGEME
```
The *?key=* is configured in *timeout.php*. This prevents randoms from executing the script. 

## Need help?
Join **#dulm** on Rizon IRC.
