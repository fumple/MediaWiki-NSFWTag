#!/bin/bash
# Run phan in multiple docker containers
# Removes used docker images afterwards
VERSIONS="1.35.6 1.35.7 1.37.3 1.38.2"
TBOLD="\e[1m"
TINFO="$TBOLD\e[35m"
TRESET="\e[0m"
SCRIPTDIR=$(dirname "${BASH_SOURCE[0]}")
cd $SCRIPTDIR/..

composer update
for ver in $VERSIONS; do
    echo -e "$TINFO=== Pulling $ver ===$TRESET";
    docker image pull mediawiki:$ver

    echo -e "$TINFO=== Running phan for $ver ===$TRESET";
    docker run --rm \
    --env MW_INSTALL_PATH=/var/www/html \
    -v $PWD:/var/www/html/extensions/NSFWTag:ro \
    mediawiki:$ver \
    /var/www/html/extensions/NSFWTag/tests/dockerPrepAndRunPhan.sh

    echo -e "$TINFO=== Cleaning up after $ver ===$TRESET";
    docker image rm mediawiki:$ver
done
echo -e "$TINFO=== Finished ===$TRESET";