#!/usr/bin/env bash
set -euxo pipefail

(
	cd neon || exit
	./tool/setup.sh

	cd packages/app || exit
	fvm flutter build web --no-web-resources-cdn
)

rm -rf static
mkdir static
cp -r neon/packages/app/build/web/* static

cp neon/assets/logo.svg img/app.svg
sed -i "s/<path fill=\"[^\"]*\" /<path fill=\"white\" /g" img/app.svg

composer i --no-dev

tar -czvf neon_web.tar.gz \
	appinfo \
	img \
	lib \
	static \
	templates \
	vendor/bamarni \
	vendor/composer \
	vendor/autoload.php \
	CHANGELOG.md \
	LICENSE

key_file="$HOME/.nextcloud/certificates/neon_web.key"
if [ -f "$key_file" ]; then
	openssl dgst -sha512 -sign "$key_file" neon_web.tar.gz | openssl base64
fi
