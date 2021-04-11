PROGRESS_FILE=/tmp/dependancy_speedtest_in_progress
if [ ! -z $1 ]; then
	PROGRESS_FILE=$1
fi
touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}
echo "********************************************************"
echo "*             Installation des dépendances             *"
echo "********************************************************"
echo "***** Commande: sudo apt-get update **********"
sudo apt-get update
echo 50 > ${PROGRESS_FILE}
echo "***** Commande: install speedtest-cli **********"
pip install git+https://github.com/sivel/speedtest-cli.git
echo 100 > ${PROGRESS_FILE}
echo "********************************************************"
echo "*             Installation terminée                    *"
echo "********************************************************"
rm ${PROGRESS_FILE}