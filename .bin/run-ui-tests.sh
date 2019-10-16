#!/bin/bash
set -e # Exit with nonzero exit code if anything fails

# download and install ngrok
curl -s https://bin.equinox.io/c/4VmDzA7iaHb/ngrok-stable-linux-amd64.zip > ngrok.zip
unzip -q ngrok.zip
chmod +x $PWD/ngrok
# Download json parser for determining ngrok tunnel
curl -sO http://stedolan.github.io/jq/download/linux64/jq
chmod +x $PWD/jq

# Open ngrok tunnel
$PWD/ngrok authtoken $NGROK_TOKEN
TIMESTAMP=$(date +%s)
$PWD/ngrok http 9090 -subdomain="${TIMESTAMP}-woo-${GATEWAY}-${WOOCOMMERCE_NUMBER}" > /dev/null &


# extract the ngrok url
NGROK_URL=$(curl -s localhost:4040/api/tunnels/command_line | jq --raw-output .public_url)
# allow ngrok to initialize
while [ ! ${NGROK_URL} ] || [ ${NGROK_URL} = 'null' ];  do
    echo "Waiting for ngrok to initialize"
    export NGROK_URL=$(curl -s localhost:4040/api/tunnels/command_line | jq --raw-output .public_url)
    sleep 1
done

# start shopsystem and demoshop
bash .bin/start-shopsystem.sh

export GIT_BRANCH=${TRAVIS_BRANCH}

# if tests triggered by PR, use different Travis variable to get branch name
if [ ${TRAVIS_PULL_REQUEST} != "false" ]; then
    export $GIT_BRANCH="${TRAVIS_PULL_REQUEST_BRANCH}"

# find out test group to be run
if [[ $GIT_BRANCH =~ "${PATCH_RELEASE}" ]]; then
   TEST_GROUP="${PATCH_RELEASE}"
elif [[ $GIT_BRANCH =~ "${MINOR_RELEASE}" ]]; then
   TEST_GROUP="${MINOR_RELEASE}"
# run all tests in nothing else specified
else
   TEST_GROUP="${MAJOR_RELEASE}"
fi

# run tests
cd wirecard-woocommerce-extension && vendor/bin/codecept run acceptance -g "${TEST_GROUP}" --html --xml
