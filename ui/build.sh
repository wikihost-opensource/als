#!/bin/bash
BASEDIR=$(dirname "$0")
cd $BASEDIR
npm run build
rm -rf ../scripts/app/webspaces/assets
rm -rf ../scripts/app/webspaces/{index.html,favicon.ico,speedtest_worker.min.js}

cp -r dist/* ../scripts/app/webspaces/