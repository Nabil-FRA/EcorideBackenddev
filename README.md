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

------------------Déploiement
Prérequis

1**Compte Platform.sh :
Créez un compte sur Platform.sh si vous n’en avez pas déjà un.

2**Platform.sh CLI :
Installez l’outil en ligne de commande Platform.sh CLI pour interagir plus facilement avec votre projet depuis le terminal.

3**Symfony CLI et Composer:
Symfony CLI pour lancer des commandes et tester en local.
Composer pour gérer les dépendances PHP.

4**PHP 8.2 (ou version utilisée par votre projet)

Vérifiez que votre application fonctionne avec la même version de PHP que celle configurée sur Platform.sh.

5**Configuration Platform.sh : Configurer le fichier # .platform.app.yaml en ajoutant la relation avec le service Mysql et MongoDB.

6**Connecter votre dépôt Git à Platform.sh .

7**Configuration des variables d’environnement
Dans votre tableau de bord Platform.sh, définissez les variables d’environnement nécessaires (ex. APP_SECRET, JWT_PASSPHRASE, DATABASE_URL, etc.).

8**Lancer le déploiement :

  Via la CLI : via l'interface web ou bien si vous avez installé l’outil en ligne de commande, vous pouvez effectuer :

" platform login"
"platform project:set-remote <identifiant-du-projet>"
"git push platform <nom-de-branche>"

9**Vérifier le déploiement :
Connectez-vous en SSH sur l’environnement : exécuter cette commande : platform ssh



