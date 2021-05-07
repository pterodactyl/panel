---
name: "\U0001F41B Bug Report"
about: For reporting code or design bugs with the software. DO NOT REPORT APACHE/NGINX/PHP CONFIGURATION ISSUES.
---

DO NOT REPORT ISSUES CONFIGURING: SSL, PHP, APACHE, NGINX, YOUR MACHINE, SSH, SFTP, ETC. ON THIS GITHUB TRACKER.

For assistance installing this software, as well as debugging issues with dependencies, please use our discord server: https://discord.gg/pterodactyl

You MUST complete all of the below information when reporting a bug, failure to do so will result in the closure of your issue. PLEASE stop spamming our tracker with "bugs" that are not related to this project.

To obtain logs for the panel and wings the below commands should help with the retrieval of them.
Panel: tail -n 100 /var/www/pterodactyl/storage/logs/laravel-$(date +%F).log | nc bin.ptdl.co 99
Wings: sudo wings diagnostics

**STOP: READ FIRST, AND THEN DELETE THE ABOVE LINES**

**Background (please complete the following information):**

* Panel or Wings:
* Version of Panel/Wings:
* Panel Logs:
* Wings Logs:
* Server's OS:
* Your Computer's OS & Browser:

**Describe the bug**
A clear and concise description of what the bug is.
Please provide additional information too, depending on what you have issues with:
Panel: `php -v` (the php version in use).
Wings: `uname -a` and `docker info` (your kernel version and information regarding docker)

**To Reproduce**
Steps to reproduce this behavior:

1. Go to '...'
2. Click on '....'
3. Scroll down to '....'
4. See error

**Expected behavior**
A clear and concise description of what you expected to happen. If applicable, add screenshots or a recording to help explain your problem.
