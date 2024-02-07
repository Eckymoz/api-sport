Fonctionnalité de l’API :

- Ajout d’un sport
- Modification d’un sport
- Suppression d’un sport
- Lister les sports

Pour intialiser les conteneurs docker :

docker-compose up --build -d

Générer les clés pour JWT token :

php bin/console lexik:jwt:generate-keypair  

Créer la base données :

php bin/console doctrine:database:create

Créer les tables : 

php bin/console doctrine:migrations:migrate 

Les commandes pour l'environnement de test : 

php bin/console doctrine:database:create --env=test
php bin/console make:migration --env=test
php bin/console doctrine:migrations:migrate --env=test

Exécuter les tests : 

php bin/phpunit

Ajouter dans le fichier .env.local :  

DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:53364/app?serverVersion=16&charset=utf8"
 
