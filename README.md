# Monito GitHub plugin pour Jeedom

<p align="center">
  <img width="100" src="/plugin_info/MonitoGitHub_icon.png">
</p>

Permet de retrouver des information sur des repos github, que ce soit sur le git en entier, un sous dossier ou encore un fichier spécifique

# |Elements monitorable|

### Pour tous les types :

* la date du dernier commit
* l'utilisateur qui a créer le dernier commit
* le nbre de commentaire sur le dernier commit


### Pour tous les repo :
* le nombre de PR open 
* la date du dernier PR ouvert
* l'utilisateur qui a ouvert le dernier PR
* le titre du dernier PR ouvert

* le nombre de PR fermé 
* la date du dernier PR fermé
* l'utilisateur qui a fermé le dernier PR
* le titre du dernier PR

* le nombre de Fork
* le nom du dernier Fork
* l'utilisateur qui a créé le dernier Fork
* la date du dernier Fork

* le nombre de Release
* l'utilisateur qui a créé la dernière Release
* la date de création de la dernière release
* la date de publication de la dernière release

# |Configuration des Equipements|
 créer un équipement par source à monitorer
* __Nom de l'équipement__ 
 * __Objet parent__ 
 * __Catégorie__ 
 Comme tout équipement classique
 
## paramètres
ex : https/github.com/TheOwner/TheRepo/blob/master/core/class/the.class.php

 * __Owner__ : Le nom du propriétaire du Git (aka TheOwner)
 * __repo__ : Le nom du repo Git (aka TheRepo)
 * __Path__ : Le chemin vers la source à monitorer, relatif à la racine du git (ici /core/class/the.class.php)
 * __Branche__ : la branche à monitoré (prendra par défaut la branche par défaut du git)
 
 ## Identification 
 
 Permet de renseigner des identifiants. 
 l'utilisation d'un token est recommandé : 
 * pour augmenter le nombre de requêtes par heure possible (de 50 à 5000)
 * pour accéder au git privé
 
  * __Utilisateur__ : Le nom de l'utilisateur - non utilisé, mais peut être utile pour de futurs développements
  * __token__ : Le token généré pour identifier l'utilisateur, il lui faut les droits en lecture
  
   
 ## Actualisation
 
 Pour définir la fréquence d'actualisation des information de l'équipement
 
 Fréquence d'actualisation : 
 * __Manuelle__ : ne s'autoactualise pas => nécessité d'appeler la commande 'Update' de l'équipement
 * __CRON XXX__ : s'actualiser tous les 'XXX' (minutes, heures, jour)
 * __programmé__ : permet de définir un cron spécifique 
 

 
