# My_Allocine

## Installation on Ubuntu 20.04

1- Symfony CLI : https://symfony.com/download

2- Docker-compose CLI : https://docs.docker.com/compose/install/

3- Composer : https://getcomposer.org/download/

4- Yarn : https://classic.yarnpkg.com/en/docs/install

5- PHP : https://www.php.net/manual/en/install.php

## Initialize the project

1. Composer install & create database

   ```bash
        symfony server:ca:install;
        sudo apt install php-xml;
        sudo apt install php-gd php-mysql;
        composer install;
        composer update;
        yarn install;
        yarn build;
        sudo docker-compose up -d;
        ## wait 1 minute
        symfony console doctrine:database:create; ##normally you will get an error but no worries if is => Can't create database 'db'; database exists
        symfony console doctrine:schema:update --force;
        
   ```

5. Enjoy :-)

## Usage

After initializing the project, run `symfony serve -d` to launch a web server in background and `symfony open:local` to open web browser

## Task List
- Une partie home présentation devra expliquer le projet de
  NeoMovie, tel que la page home présentation de Netflix
  (https://www.netflix.com/fr/). ✅
- Sur la page login, l'utilisateur pourra cocher une case
  remember me pour ne plus avoir besoin de se connecter
  pendant les cinq prochaines minutes. ✅
- Une fois que l'utilisateur s'authentifie, si c'est sa première
  connexion, l'utilisateur devra choisir son genre de film
  préféré. ✅
- À chaque nouvelle connexion, l'utilisateur devra être redirigé
  vers la page de l'application, ou une liste de films du genre
  choisi par l'utilisateur lors de sa première connexion, sera
  affichée. ✅
- Sur cette même page des filtres devront être disponibles
  (année de sortie, genre, langue). ✅
- Une barre de recherche permettra de rechercher des
  titres de films, à chaque lettre tapée, la recherche doit se
  mettre à jour. ✅
- En cliquant sur l'affiche d'un film, une nouvelle page s'ouvre
  ou la bande d'annonce et les détails du film seront affichés
  (synopsis, titre, genre, etc...). ✅
- L'utilisateur pourra aimer un film et retrouver la liste de ses
  films aimés. ✅
- L'utilisateur pourra créer des playlists et ajouter un film à
  sa playlist. ✅
- L'utilisateur pourra cliquer sur un bouton "j'ai regardé ce
  film" et retrouver la liste de ses films déjà regardés. ✅