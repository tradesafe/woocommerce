#!/usr/bin/env bash

if [[ -z "$VERSION" ]]; then
  # Get version from tag name
  VERSION="${GITHUB_REF#refs/tags/}"

  # remove the leading v
  VERSION="${VERSION#v}"
fi

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

rsync -rc --exclude-from="$GITHUB_WORKSPACE/.exclude" "$GITHUB_WORKSPACE/" trunk/ --delete --delete-excluded

svn add . --force > /dev/null
svn status | grep '^\!' | sed 's/! *//' | xargs -I% svn rm %@ > /dev/null
svn cp "trunk" "tags/$VERSION"

svn update

svn status
