#!/usr/bin/env bash
set -euxo pipefail

(
	cd neon || exit
	./tool/setup.sh

	cd packages/neon_framework/example || exit
	fvm flutter build web --no-web-resources-cdn
)

rm -rf static
mkdir static
cp -r neon/packages/neon_framework/example/build/web/* static

cp neon/assets/logo_inverted.svg img/app.svg
sed -i "s/<path fill=\"[^\"]*\" /<path fill=\"white\" /g" img/app.svg

composer i --no-dev

echo "<?php

return '$(git ls-files -s neon | cut -d " " -f 2)';" > lib/etag.php

tar -czvf neon_web.tar.gz \
	-C .. \
	neon_web/appinfo \
	neon_web/img \
	neon_web/lib \
	neon_web/static \
	neon_web/vendor/bamarni \
	neon_web/vendor/composer \
	neon_web/vendor/autoload.php \
	neon_web/CHANGELOG.md \
	neon_web/LICENSE

key_file="$HOME/.nextcloud/certificates/neon_web.key"
if [ -f "$key_file" ]; then
	openssl dgst -sha512 -sign "$key_file" neon_web.tar.gz | openssl base64
fi
