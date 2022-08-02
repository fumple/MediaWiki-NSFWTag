#!/bin/bash
# Run phan in multiple docker containers
# Removes used docker images afterwards
VERSIONS="1.35.6 1.35.7 1.37.3 1.38.2"
TBOLD="\e[1m"
TRESULT="$TBOLD\e[35m"
TINFO="$TBOLD\e[35m"
TERR="$TBOLD\e[31m"
TRESET="\e[0m"
SCRIPTDIR=$(dirname "${BASH_SOURCE[0]}")
cd $SCRIPTDIR/..

composer update
for ver in $VERSIONS; do
    echo -e "$TINFO=== Pulling $ver ===$TRESET";
    docker image pull mediawiki:$ver
    if [[ $? -gt 0 ]]; then
        echo -e "$TERR=== Failed to pull $ver, skipping... ===$TRESET";
        continue
    fi

    echo -e "$TINFO=== Running phan for $ver ===$TRESET";
    docker run --rm \
    --env MW_INSTALL_PATH=/var/www/html \
    -v $PWD:/var/www/html/extensions/NSFWTag:ro \
    mediawiki:$ver \
    /var/www/html/extensions/NSFWTag/tests/dockerPrepAndRunPhan.sh
    if [[ $? -gt 0 ]]; then
        echo -e "$TERR=== Phan found issues! ===$TRESET";
        continue
    fi

    echo -e "$TINFO=== Cleaning up after $ver ===$TRESET";
    docker image rm mediawiki:$ver > /dev/null
done
echo -e "$TINFO=== Finished ===$TRESET";