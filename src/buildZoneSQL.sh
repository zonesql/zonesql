#!/bin/sh

# Returns the path that this script lives in
SRC_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)

echo "Beginning Zone SQL Build"

cd $SRC_DIR/../

if [ ! -d "./dist" ]; then
  echo "Creating release dir: dist"
  mkdir ./dist
fi

# if [ ! -d "./dist/fonts" ]; then
#   mkdir ./dist/fonts
# fi

# echo "Copying Fonts"
# cp -a ./src/fonts/ ./dist/fonts/

echo "Building dojo"

$SRC_DIR/util/buildscripts/build.sh --profile $SRC_DIR/zonesql.profile.js

echo "Copying ACE Editor"

if [ ! -d "./dist/ace/src-min-noconflict" ]; then
  mkdir -p ./dist/ace/src-min-noconflict
fi

cp -a ./src/ace/src-min-noconflict ./dist/ace

echo "Build Complete"
