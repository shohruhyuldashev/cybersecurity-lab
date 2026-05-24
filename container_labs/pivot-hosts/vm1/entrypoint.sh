#!/bin/bash
service cron start
apache2ctl -D FOREGROUND
