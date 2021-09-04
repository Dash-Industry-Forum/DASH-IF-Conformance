#!/bin/bash
sudo rm master.zip*
wget https://github.com/Dash-Industry-Forum/DASH-IF-Conformance/archive/master.zip
unzip master.zip
sudo chown -R www-data DASH-IF-Conformance-master
sudo chmod -R 777 DASH-IF-Conformance-master
sudo rm -r temp_backup
sudo mv current/ temp_backup
sudo mv DASH-IF-Conformance-master current

sudo chown -R www-data current
sudo chmod -R 777 current

#Download CMAF submodule and set permissions
sudo rm master.zip*
wget https://github.com/Dash-Industry-Forum/CMAF/archive/master.zip
unzip master.zip
sudo chown -R www-data CMAF-master
sudo chmod -R 777 CMAF-master
sudo mv CMAF-master/* current/CMAF/
sudo rm -r CMAF-master

#Download Conformance-Frontend submodule and set permissions
sudo rm master.zip*
wget https://github.com/Dash-Industry-Forum/Conformance-Frontend/archive/master.zip
unzip master.zip
sudo chown -R www-data Conformance-Frontend-master
sudo chmod -R 777 Conformance-Frontend-master
sudo mv Conformance-Frontend-master/* current/Conformance-Frontend/
sudo rm -r Conformance-Frontend-master
sudo mv temp_backup/Conformance-Frontend/temp current/Conformance-Frontend/
sudo rm current/Conformance-Frontend/visitorLogs/README.txt
sudo cp temp_backup/Conformance-Frontend/visitorLogs/* current/Conformance-Frontend/visitorLogs/

if [ -f "temp_backup/Conformance-Frontend/counter.txt" ]
then
sudo cp temp_backup/Conformance-Frontend/counter.txt current/Conformance-Frontend/
fi

#Download Conformance-Frontend-HLS submodule and set permissions
sudo rm master.zip*
wget https://github.com/Dash-Industry-Forum/Conformance-Frontend-HLS/archive/master.zip
unzip master.zip
sudo chown -R www-data Conformance-Frontend-HLS-master
sudo chmod -R 777 Conformance-Frontend-HLS-master
sudo mv Conformance-Frontend-HLS-master/* current/Conformance-Frontend-HLS/
sudo rm -r Conformance-Frontend-HLS-master
sudo mv temp_backup/Conformance-Frontend-HLS/temp current/Conformance-Frontend-HLS/
sudo rm current/Conformance-Frontend-HLS/visitorLogs/README.txt
sudo cp temp_backup/Conformance-Frontend-HLS/visitorLogs/* current/Conformance-Frontend-HLS/visitorLogs/

#Download CTAWAVE submodule and set permissions
sudo rm master.zip*
wget https://github.com/Dash-Industry-Forum/CTAWAVE/archive/master.zip
unzip master.zip
sudo chown -R www-data CTAWAVE-master
sudo chmod -R 777 CTAWAVE-master
sudo mv CTAWAVE-master/* current/CTAWAVE/
sudo rm -r CTAWAVE-master

#Download DASH submodule and set permissions
sudo rm master.zip*
wget https://github.com/Dash-Industry-Forum/DASH/archive/master.zip
unzip master.zip
sudo chown -R www-data DASH-master
sudo chmod -R 777 DASH-master
sudo mv DASH-master/* current/DASH/
sudo rm -r DASH-master

#Download DynamicServiceValidator submodule and set permissions
sudo rm master.zip*
wget https://github.com/Dash-Industry-Forum/DynamicServiceValidator/archive/master.zip
unzip master.zip
sudo chown -R www-data DynamicServiceValidator-master
sudo chmod -R 777 DynamicServiceValidator-master
sudo mv DynamicServiceValidator-master/* current/DynamicServiceValidator/
sudo rm -r DynamicServiceValidator-master

#Download HbbTV_DVB submodule and set permissions
sudo rm master.zip*
wget https://github.com/Dash-Industry-Forum/HbbTV_DVB/archive/master.zip
unzip master.zip
sudo chown -R www-data HbbTV_DVB-master
sudo chmod -R 777 HbbTV_DVB-master
sudo mv HbbTV_DVB-master/* current/HbbTV_DVB/
sudo rm -r HbbTV_DVB-master

#Download HLS submodule and set permissions
sudo rm master.zip*
wget https://github.com/Dash-Industry-Forum/HLS/archive/master.zip
unzip master.zip
sudo chown -R www-data HLS-master
sudo chmod -R 777 HLS-master
sudo mv HLS-master/* current/HLS/
sudo rm -r HLS-master

#Download ISOSegmentValidator submodule and set permissions
sudo rm master.zip*
wget https://github.com/Dash-Industry-Forum/ISOSegmentValidator/archive/master.zip
unzip master.zip
sudo chown -R www-data ISOSegmentValidator-master
sudo chmod -R 777 ISOSegmentValidator-master
sudo mv ISOSegmentValidator-master/* current/ISOSegmentValidator/
sudo rm -r ISOSegmentValidator-master