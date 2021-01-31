# Monito GitHub plugin pour Jeedom

<p align="center">
  <img width="100" src="/plugin_info/MonitoGitHub_icon.png">
</p>

Permet de retrouver des informations sur des repos github, que ce soit sur le git en entier, un sous dossier ou encore un fichier spécifique

# |Elements monitorable|

### Pour tous les types :
| Commit |
| --- |
| la date du dernier commit |
| l'utilisateur qui a créé le dernier commit |
| le nbre de commentaire sur le dernier commit |


### Pour tous les repo :
| PR | Forks | Release | Issues |
| --- | --- | --- | --- |
| le nombre de PR ouverts  | le nombre de Fork | le nombre de Release | nbre d'issues ouverte |
| la date du dernier PR ouvert | le nom du dernier Fork | l'utilisateur qui a créé la dernière Release | date de création de la dernière issue ouverte |
| l'utilisateur qui a ouvert le dernier PR | l'utilisateur qui a créé le dernier Fork | la date de création de la dernière release | utilisateur de la dernière issue ouverte |
| le titre du dernier PR ouvert | la date du dernier Fork | la date de publication de la dernière release | titre de la dernière issue ouverte |
| le nombre de PR fermés |  |  | type de la dernière issue ouverte |
| la date du dernier PR fermé |  |   | nbre d'issues fermées |
| l'auteur du dernier PR fermé |  |  |  date de fermeture de l'issue |
| le titre du dernier PR fermé |  |   | utilisateur de la dernière issues fermée |
|  |  |  |  titre de la dernière issue fermée |
|  |  |  |  type de la dernière issue fermée |

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
 * __Path__ : -optionnel- Le chemin vers la source à monitorer, relatif à la racine du git (ici /core/class/the.class.php).
 
      > si vide l'équipement sera de type repos global sera surveillé (cf Elements monitorable)
      > si fini par '/' l'équipement sera de type folder (pour le moment même surveillance que type file)
      > si fini par autre chose, l'équipement sera de type file
      
 * __Branche__ : -optionnel- la branche a monitorer (si vide prendra par défaut la branche par défaut du git)
 
 ## Identification 
 
 Permet de renseigner des identifiants. 
 l'utilisation d'un token est recommandé : 
 * pour augmenter le nombre de requêtes par heure possible (de 50 à 5000)
 * pour accéder au git privé
 
  * __Utilisateur__ : Le nom de l'utilisateur - non utilisé, mais peut être utile pour de futurs développements
  * __token__ : Le token généré pour identifier l'utilisateur, il lui faut les droits en lecture
  
   
 ## Actualisation
 
 Pour définir la fréquence d'actualisation des informations de l'équipement
 
 Fréquence d'actualisation : 
 * __Manuelle__ : ne s'autoactualise pas => nécessite d'appeler la commande 'Update' de l'équipement
 * __CRON XXX__ : s'actualiser tous les 'XXX' (minutes, heures, jour)
 * __programmé__ : permet de définir un cron spécifique 
 
 
 # Exemple de bloc code pour tester l'arrivée d'un nouveau commit
 Merci [Jeandhom](https://community.jeedom.com/t/obtenir-lavant-derniere-valeur-laststate/36412/2)!
 
 A mettre en déclencheur d'un scénario : la commande *'Date dern commit'*
 Il faut historiser cette valeur
 
> Ce bloc permet de mettre plusieurs dates en surveillance, l'historique est récupéré à partir du déclencheur

 en bloc code : 
 
```php
$cmdId = str_replace("#","",$scenario->getRealTrigger()); // récupère l'id de la commande
$scenario->setlog("id : ".$cmdId);

$debut = date("Y-m-d H:i:s", strtotime("2 months ago"));
$fin = date("Y-m-d H:i:s", strtotime("now"));  
$all = history::all($cmdId, $debut, $fin);
$derniereValeur = count($all) ? $all[count($all) - 1]->getValue() : null;
$avantDerniereValeur = count($all) >=2 ? $all[count($all) - 2]->getValue() : null;

$scenario->setlog("dernière valeur : $derniereValeur");
$scenario->setlog("Avant dernière valeur : $avantDerniereValeur");

$od=DateTime::createFromFormat("Y-m-d G:i:s", $avantDerniereValeur);
$nd=DateTime::createFromFormat("Y-m-d G:i:s", $derniereValeur);

$interval = $nd->getTimestamp()-$od->getTimestamp(); // calcul du delta entre les 2 dates, en ms

$tags['#delta#'] = $interval; // on met dans un tag delta pour utilisation dans un scnéario
$scenario->setlog("delta  : $interval");
$scenario->setTags($tags);
```
Puis dans un bloc SI/Alors/Sinon

`Si tag(delta)>0`

et vous pouvez envoyer un message du type :
```
Nouveau commit sur trigger()
en date de triggerValue()
```


 
