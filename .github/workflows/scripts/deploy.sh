#!/usr/bin/env bash

if [[ -z "$VERSION" ]]; then
  # Get version from tag name
  VERSION="${GITHUB_REF#refs/tags/}"
fi

# remove the leading v
VERSION="${VERSION#v}"

PLUGIN="tradesafe-payment-gateway"
SVN_URL="https://plugins.svn.wordpress.org/${PLUGIN}/"
SVN_DIR="${HOME}/.svn-${PLUGIN}"

echo "Checking out wordpress.org repository..."
svn checkout --depth immediates "$SVN_URL" "$SVN_DIR"
cd "$SVN_DIR"
svn update --set-depth infinity assets
svn update --set-depth infinity trunk
svn update --set-depth immediates tags

# Check if version was already deployed
if [[ -d "tags/$VERSION" ]]; then
	echo "Version $VERSION of plugin $PLUGIN was already published";
	exit
fi

echo "Copy files from Git to SVN..."
rsync -rc --exclude-from="$GITHUB_WORKSPACE/.exclude" "$GITHUB_WORKSPACE/" trunk/ --delete --delete-excluded

echo "Add files to SVN..."
svn add . --force > /dev/null

echo "Removed deleted files from SVN..."
svn status | grep '^\!' | sed 's/! *//' | xargs -I% svn rm %@ > /dev/null

echo "Create tag for version..."
svn cp "trunk" "tags/$VERSION"

echo "Check that SVN repo is up-to-date..."
svn update

echo "Check SVN repo status..."
svn status
