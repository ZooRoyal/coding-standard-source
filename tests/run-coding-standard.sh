#!/bin/bash

set -e;

function cleanup {
  echo removing container and image
  docker kill cs-container && sleep 2 && docker rmi "$IMAGE_ID"
}

trap cleanup EXIT

SCRIPT_DIR=$( cd -- "$(dirname -- "$( dirname -- "${BASH_SOURCE[0]}" )")" &> /dev/null && pwd )

echo start container
IMAGE_ID=$(docker build -q "$SCRIPT_DIR")
CONTAINER_ID=$(docker run --rm -d --name cs-container --entrypoint "/bin/sleep" "$IMAGE_ID" infinity)

echo copy files into container
docker cp "$(pwd)"/. cs-container:/app/

if [ ! -z "$(git status -s)" ]; then
  echo save state in container
  docker exec cs-container git add .
  docker exec cs-container git commit -m "Before coding standard"
fi

echo execute coding-standard
set +e

#docker exec -it cs-container /bin/bash
docker exec -t cs-container /coding-standard/src/bin/coding-standard $@
CS_EXIT_CODE=$?

set -e

CHANGED_FILES=$(docker exec cs-container git diff --name-only HEAD)
if [ ! -z "$CHANGED_FILES" ]; then
  echo gather changed files
  for FILENAME in $CHANGED_FILES; do
      echo Sync changes to $FILENAME
      docker cp cs-container:/app/"$FILENAME" "$(pwd)"/"$FILENAME"
  done
fi

if [ -d "/app/tmp" ]; then
    echo copy back tmp folder
    docker cp cs-container:/app/tmp "$(pwd)"
fi

exit $CS_EXIT_CODE
