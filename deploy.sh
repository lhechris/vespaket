#!/usr/bin/env bash
set -euo pipefail

# === PARAMÈTRES ===
USER_HOME="${HOME}"
BUILD_DIR="${USER_HOME}/builds/vespaket"
RELEASES_BUILD="${BUILD_DIR}/releases"

WEB_ROOT="/var/www/vespaket"
RELEASES_WEB="${WEB_ROOT}"
CURRENT_LINK="${WEB_ROOT}/site"
SHARED="${WEB_ROOT}/data"


REPO="git@github.com:lhechris/vespaket.git"
#BRANCH_OR_TAG="main"

#PHP_VERSION="8.4"
#PHP_FPM_SERVICE="php${PHP_VERSION}-fpm"
#WEB_SERVICE="apache2"

TS=$(date +"%Y-%m-%d-%H%M%S")
NEW_BUILD="${RELEASES_BUILD}/${TS}"
NEW_WEB_RELEASE="${RELEASES_WEB}/${TS}"

echo "=== Build dans ${NEW_BUILD}"
mkdir -p "${NEW_BUILD}"
echo ">>> Récupération du code"
cd ${NEW_BUILD}
git clone "${REPO}" .
#git checkout "${BRANCH_OR_TAG}"
#WORK_DIR="${USER_HOME}/workspace/basket"
#cp -r ${WORK_DIR}/* ${NEW_BUILD}

echo ">>> Installation Composer"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo ">>> Installation js"
cd ${NEW_BUILD}
npm install --silent
npm run build

# === SHARED ===
sudo mkdir -p "${SHARED}/uploads"

# === COPIE VERS /var/www ===
echo "=== Copie vers ${NEW_WEB_RELEASE}"
sudo mkdir -p "${NEW_WEB_RELEASE}"
sudo cp -r ${NEW_BUILD}/app ${NEW_WEB_RELEASE}
sudo cp -r ${NEW_BUILD}/bootstrap ${NEW_WEB_RELEASE}
sudo cp -r ${NEW_BUILD}/artisan ${NEW_WEB_RELEASE}
sudo cp -r ${NEW_BUILD}/config ${NEW_WEB_RELEASE}
sudo cp -r ${NEW_BUILD}/database ${NEW_WEB_RELEASE}
#sudo cp -r ${NEW_BUILD}/lang ${NEW_WEB_RELEASE}
sudo cp -r ${NEW_BUILD}/public ${NEW_WEB_RELEASE}
sudo cp -r ${NEW_BUILD}/resources ${NEW_WEB_RELEASE}
sudo cp -r ${NEW_BUILD}/routes ${NEW_WEB_RELEASE}
sudo cp -r ${NEW_BUILD}/storage ${NEW_WEB_RELEASE}
sudo cp -r ${NEW_BUILD}/vendor ${NEW_WEB_RELEASE}
sudo cp -r ${NEW_BUILD}/composer.* ${NEW_WEB_RELEASE}

# === SYMLINK des éléments partagés ===
echo "=== Symlink shared ==="
sudo rm -f "${NEW_WEB_RELEASE}/database/database.sqlite"
sudo ln -s "${SHARED}/database.sqlite" "${NEW_WEB_RELEASE}/database/database.sqlite"

sudo rm -rf "${NEW_WEB_RELEASE}/storage/app/public"
sudo ln -s "${SHARED}/app/public" "${NEW_WEB_RELEASE}/storage/app/public"

sudo rm -rf "${NEW_WEB_RELEASE}/storage/app/templates"
sudo ln -s "${SHARED}/app/templates" "${NEW_WEB_RELEASE}/storage/app/templates"

sudo rm -f "${NEW_WEB_RELEASE}/.env"
sudo ln -s "${SHARED}/.env" "${NEW_WEB_RELEASE}/.env"

sudo rm -rf "${NEW_WEB_RELEASE}/database/seeders"
sudo ln -s "${SHARED}/seeders" "${NEW_WEB_RELEASE}/database/seeders"


# === PERMISSIONS ===
echo "=== Permissions ==="
sudo chown -R www-data:www-data "${NEW_WEB_RELEASE}"
sudo chmod -R 755 "${NEW_WEB_RELEASE}"

#sudo chown www-data:www-data "${SHARED}/database.db"
#sudo chmod 664 "${SHARED}/database.db"

echo ">>> Optimisation laraval"
cd "${NEW_WEB_RELEASE}"
sudo -u www-data php artisan down || true
sudo -u www-data php artisan migrate || true
sudo -u www-data php artisan storage:link
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan optimize
sudo -u www-data php artisan up || true

# === Activation atomique ===
echo "=== Activation de la nouvelle release ==="
sudo ln -sfn "${TS}" "${CURRENT_LINK}"

# === Reload services ===
#echo "=== Reload PHP-FPM & apache ==="
#sudo systemctl reload "${PHP_FPM_SERVICE}"
#sudo systemctl reload "${WEB_SERVICE}"

echo "=== Déploiement terminé ==="
echo "Release active : ${TS}"
