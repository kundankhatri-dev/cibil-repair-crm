#!/bin/bash
cd /home/u929623538/domains/cibilrepair.in/public_html
git pull origin main
php bin/console deploy
