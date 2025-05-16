# Sistema de Correcció de Tasques

Aplicació PHP per generar informes d'avaluació de tasca en pdf

## Requisits
- Apache
- PHP 8.1 o superior
- Composer
- Extensió - Es requereix l'extensió mbstring de PHP per a la gestió de caràcters UTF-8


## Instal·lació

1. Clona el repositori:

git clone https://
cd nom-del-projecte

2. Instal·la les dependències
composer install

3. Configura els permisos d'escriptura per generar els PDFs
chmod 755 src/

4. Personalitza el JSON
rubrica.json
4. Accedeix a través del teu servidor web
http://localhost/ruta-al-teu-projecte/src/index.php
