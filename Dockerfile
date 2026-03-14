# ─── ÉTAPE 1 : image de base PHP ─────────────────────────────────────────────
FROM php:8.2-fpm-alpine
# php:8.2-fpm  = PHP avec FPM (FastCGI Process Manager) pour servir Laravel
# alpine       = version légère de Linux (~5MB au lieu de ~200MB)

# ─── ÉTAPE 2 : installer les dépendances système ──────────────────────────────
RUN apk add --no-cache \
    nginx \
    supervisor \
    nodejs \
    npm \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    curl
# nginx      : le serveur web qui reçoit les requêtes HTTP
# supervisor : garde nginx et php-fpm en vie en même temps
# libpng-dev, libzip-dev : nécessaires pour compiler les extensions PHP

# ─── ÉTAPE 3 : installer les extensions PHP ───────────────────────────────────
RUN docker-php-ext-install pdo pdo_mysql zip gd
# pdo + pdo_mysql : connexion à MySQL
# zip            : manipulation de fichiers zip
# gd             : génération d'images (QR codes, PDF...)

# ─── ÉTAPE 4 : installer Composer ─────────────────────────────────────────────
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
# On copie l'exécutable Composer depuis son image officielle

# ─── ÉTAPE 5 : définir le dossier de travail ──────────────────────────────────
WORKDIR /var/www/html

# ─── ÉTAPE 6 : copier et installer les dépendances PHP ────────────────────────
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --optimize-autoloader
# On copie d'abord SEULEMENT composer.json et composer.lock
# Docker met en cache cette couche → si vos dépendances n'ont pas changé,
# il ne refait pas composer install à chaque build (gain de temps !)
# --no-dev : on n'installe pas les packages de développement (PHPUnit, etc.)

# ─── ÉTAPE 7 : copier le reste du code ────────────────────────────────────────
COPY . .

# ─── ÉTAPE 8 : compiler les assets frontend ───────────────────────────────────
RUN npm install && npm run build && rm -rf node_modules
# On compile les CSS/JS avec Vite puis on supprime node_modules (inutile en prod)

# ─── ÉTAPE 9 : permissions sur les dossiers Laravel ───────────────────────────
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache
# Laravel a besoin d'écrire dans ces dossiers (logs, cache, sessions...)

# ─── ÉTAPE 10 : copier les configs nginx et supervisor ────────────────────────
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ─── ÉTAPE 11 : exposer le port ───────────────────────────────────────────────
EXPOSE 80

# ─── ÉTAPE 12 : démarrer nginx + php-fpm via supervisor ───────────────────────
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
