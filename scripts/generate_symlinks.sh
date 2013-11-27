#!/bin/bash
# extension symlinks
FILES=extensions/*
for f in $FILES
do
  echo "Processing $f ..."
  rm -rf application/$f
  ln -s ../../$f application/$f
done

# theme symlink
rm -rf application/extensions/themes/dispedia
ln -s ../../../themes/dispedia application/extensions/themes/dispedia

# site extension symlink
rm -rf application/htdocs
ln -s ../htdocs application/htdocs
