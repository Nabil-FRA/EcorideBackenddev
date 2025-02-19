Description :

Dans le cadre de ma formation Graduate devloppeur full stack, j'ai réalisé cette une application web conçu pour encourage les déplaçements écologique par la mise en relation entre conducteurs et passagers qui souhaite faire un trajet avec des voiture éléctriques. 

Prérequis :
- PHP : version 8.2
- Composer: Gestionnaire de dépendances PHP
- Symfony CLI : Outil en ligne de commande pour Symfony
- XAMPP: Serveur local incluant Apache, Mysql, PHP
- MongoDB : Base de données NoSQL pour stocker les confirmations de participation et l'historique des covoiturages


Installation:
1. **Installer XAMPP**

   Téléchargez et installez XAMPP depuis le site officiel : [https://www.apachefriends.org/fr/index.html](https://www.apachefriends.org/fr/index.html). Assurez-vous que les modules Apache et MySQL sont en cours d'exécution via le panneau de contrôle XAMPP.
  
2. **Cloner le dépôt**

   git clone https:/
   cd ecoride
   
3. Installer les dépendances back-end:
   excuter la commande : composer install
2. **Configurer les variables d'environement**

   DATABASE_URL="mysql://utilisateur:motdepasse@127.0.0.1:3306/ecoride_db"
   MONGODB_URL="mongodb://127.0.0.1:27017"
   MONGODB_DB="ecoride_mongodb"


4.Créer la base de données MySQL :

 Lancez les serveurs MySQL et Apache via XAMPP, puis exécutez : php bin/console doctrine:database:create 
 créer une classe de Migration avec la commande : " php bin/console make:migration" ensuite la commande "php bin/console doctrine:migrations:migrate"

5.Lancer le serveur de développement Symfony :
symfony server:start

6-Installer MongoDB Compass : EcoRide utilise MongoDB pour gérer les confirmations de participation et l'historique des covoiturages. Suivez les instructions officielles pour installer MongoDB sur votre système.

7-Configurer MongoDB avec Symfony
Ce projet utilise Doctrine MongoDB ODM pour interagir avec MongoDB. Assurez-vous que l'extension MongoDB pour PHP est installée et active. Vous pouvez consulter la documentation officielle pour plus de détails :
 https://www.mongodb.com/docs/drivers/php-frameworks/symfony/
