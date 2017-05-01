#!/bin/bash
echo 0 
echo "Launch install of speedtest dependancy"
echo 30 
sudo apt-get install -y python-pip python-dev
echo 75 
sudo pip install speedtest-cli
echo 100 
echo "Everything is successfully installed!"