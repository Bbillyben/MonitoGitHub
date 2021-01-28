<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';
require_once __DIR__  . '/MGH_GHAPI.class.php';

class MonitoGitHub extends eqLogic {

   // définition des commande par type d'équipement
   const logID_common = array(
      'updateMGH'=>array('name'=>'Update','type'=>'action', 'subtype'=>'other'),
      'last_commit_date'=>array('name'=>'Date dern commit','type'=>'info', 'subtype'=>'string'),
      'last_commit_user'=>array('name'=>'Utilisateur dern commit','type'=>'info', 'subtype'=>'string'),
      'last_commit_comment_cnt'=>array('name'=>'Nbre de commentaire dern commit','type'=>'info', 'subtype'=>'numeric')
   );


   const logID_repos = array(
      'pr_open_count'=>array('name'=>'Nbre de PR ouverts','type'=>'info', 'subtype'=>'numeric'),
      'pr_open_user'=>array('name'=>'Dernier PR Utilsateur','type'=>'info', 'subtype'=>'string'),
      'pr_open_date'=>array('name'=>'Dernier PR date','type'=>'info', 'subtype'=>'string'),
      'pr_open_title'=>array('name'=>'Dernier PR Title','type'=>'info', 'subtype'=>'string'),
      'pr_closed_count'=>array('name'=>'Nbre de PR fermés','type'=>'info', 'subtype'=>'numeric'),
      'pr_closed_user'=>array('name'=>'Dernier PR fermé Utilsateur','type'=>'info', 'subtype'=>'string'),
      'pr_closed_date'=>array('name'=>'Dernier PR fermé date','type'=>'info', 'subtype'=>'string'),
      'pr_closed_title'=>array('name'=>'Dernier PR fermé Title','type'=>'info', 'subtype'=>'string'),
      'fork_count'=>array('name'=>'Nbre de Fork','type'=>'info', 'subtype'=>'numeric'),
      'fork_name'=>array('name'=>'Dernier Fork Name','type'=>'info', 'subtype'=>'string'),
      'fork_owner'=>array('name'=>'Dernier Fork Owner','type'=>'info', 'subtype'=>'string'),
      'fork_date'=>array('name'=>'Dernier Fork date','type'=>'info', 'subtype'=>'string'),
     
      'release_count'=>array('name'=>'Nbre de Release','type'=>'info', 'subtype'=>'numeric'),
      'release_owner'=>array('name'=>'Dernière Release Owner','type'=>'info', 'subtype'=>'string'),
      'release_created_date'=>array('name'=>'Dernier Release date creation','type'=>'info', 'subtype'=>'string'),
     'release_published_date'=>array('name'=>'Dernier Release date publication','type'=>'info', 'subtype'=>'string')
     
   );
    /*     * *************************Attributs****************************** */
    
  /*
   * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
   * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
	public static $_widgetPossibility = array();
   */
    
    /*     * ***********************Methode static*************************** */
    public static function cron() {
      foreach (eqLogic::byType(__CLASS__, true) as $eqLogic) {
         $freq = $eqLogic->getConfiguration('freq', '');
         if($freq == 'prog')$freq=$eqLogic->getConfiguration('autorefresh', '');

         if ($freq == '' || $freq=='manual')  continue;
         try {
            $cron = new Cron\CronExpression($freq, new Cron\FieldFactory);
            if ($cron->isDue()) {
               log::add('MonitoGitHub','debug', "╔═══════════════════════ Start Cron $freq :".$eqLogic->getHumanName());
               $eqLogic->refreshData();
               log::add('MonitoGitHub','debug', "╚═════════════════════════════════════════ fin du cron ");
            }
         } catch (Exception $e) {
            log::add(__CLASS__, 'error', __('Expression cron non valide pour ', __FILE__) . $eqLogic->getHumanName() . ' : ' . $autorefresh);
         }
      }
   }


    /*     * *********************Méthodes d'instance************************* */

    // ########## Mise à jour des données de surveillance
   // fonction générale
    public function refreshData(){
      log::add('MonitoGitHub', 'debug', '║ ╔═══════════════════════ start refresh datas ══════════════════════');
      $typeSurv=$this->getConfiguration('typesurvey');
      

      
      $owner=$this->getConfiguration('owner');
      $repo=$this->getConfiguration('repo');
      $path=$this->getConfiguration('path');
      $branch=$this->getConfiguration('branch');
      $token=$this->getConfiguration('token');
      $user=$this->getConfiguration('user');
      log::add('MonitoGitHub', 'debug', '║ ║ ╔══════════ Paramètres ════════');
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─── Type equipement : '.$typeSurv);
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─── owner : '.$owner);
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─── repo : '.$repo);
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─── path : '.$path);
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─── branch : '.$branch);
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─── user : '.$user);
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─── token : '.$token);
      log::add('MonitoGitHub', 'debug', '║ ║ ╚═════════════════════════════');

      // ########## update des éléménts communs
      // Commits
      log::add('MonitoGitHub', 'debug', '║ ║ ╔══════════ Updates Commits');
      $data=MGH_GHAPI::getCOMMIT_infos($owner, $repo, $path, $branch, $user, $token);
      if(!$this->statusHandler($data['status'])){
         log::add('MonitoGitHub', 'debug', '║ ║ ╚═══════════ ######## Request Status Error BREAK');
         return;
      }
      $this->updateCMDfromArray($data);
      log::add('MonitoGitHub', 'debug', '║ ║ ╚══════════ End Updates Commits');
      


      // update des éléments spécifiques
      switch($typeSurv){
         case 'repos':
            log::add('MonitoGitHub', 'debug', '║ ║ ╔══════════ Updates Pull Request');
            $data=MGH_GHAPI::getPR_infos($owner, $repo, $branch, $user, $token);
            if(!$this->statusHandler($data['status'])){
               log::add('MonitoGitHub', 'debug', '║ ║ ╚═══════════ ######## Request Status Error BREAK');
               return;
            }
            $this->updateCMDfromArray($data);
            log::add('MonitoGitHub', 'debug', '║ ║ ╚══════════ End Updates PR');

            log::add('MonitoGitHub', 'debug', '║ ║ ╔══════════ Updates Fork');
            $data=MGH_GHAPI::getFORK_infos($owner, $repo, $branch, $user, $token);
            if(!$this->statusHandler($data['status'])){
               log::add('MonitoGitHub', 'debug', '║ ║ ╚═══════════ ######## Request Status Error BREAK');
               return;
            }
            $this->updateCMDfromArray($data);
            log::add('MonitoGitHub', 'debug', '║ ║ ╚══════════ End Updates Fork');
          
          log::add('MonitoGitHub', 'debug', '║ ║ ╔══════════ Updates Releases');
            $data=MGH_GHAPI::getRELEASE_infos($owner, $repo, $branch, $user, $token);
            if(!$this->statusHandler($data['status'])){
               log::add('MonitoGitHub', 'debug', '║ ║ ╚═══════════ ######## Request Status Error BREAK');
               return;
            }
            $this->updateCMDfromArray($data);
            log::add('MonitoGitHub', 'debug', '║ ║ ╚══════════ End Updates Release');
         
            break;
         case 'folder':
            break;
         case 'file':
            break;
            
         Default:
         log::add('MonitoGitHub','debug', '╠════ type survey not found ('.$typeSurv.')');

      }

      log::add('MonitoGitHub', 'debug', '║ ╚═══════════════════════ end refresh datas ══════════════════════');

    }

     // status handler pour prendre les retours des requetes
     public function statusHandler($status){
      switch(strtoupper($status)){
         case "200 OK":
            return true;
            break;
         case "404 NOT FOUND":
            log::add('MonitoGitHub', 'error', '### Erreur, repo non trouvé ou privé ###');
            return false;
            break;  
         case '401 UNAUTHORIZED':
            log::add('MonitoGitHub', 'error', '### Utilisateur non enregistré - vérifiez le token ###');
            return false;
            break; 
         case '403 FORBIDDEN':
            log::add('MonitoGitHub', 'error', '### Accès non authorisé ###');
            return false;
            break; 

         default:
            log::add('MonitoGitHub', 'error', '### Erreur non référencée : '.$status.' ###');
            return false;
            break; 
            
      }
      return true;

   }


    /*    ----- fonction pour mettre à jour les valeurs à partir d'un array 
        * dont les clé sont les logicalId des commandes (cf les array de classe)
        * contenant la clé status => 200 Ok si on doit remplir les données
    */
    public function updateCMDfromArray($data){
      if($data['status']=='200 OK'){

         foreach($data as $logId => $val){
            if($logId=='status')continue;
            $monitoGHcmd = $this->getCmd(null, $logId);
            if (is_object($monitoGHcmd)) {
               log::add('MonitoGitHub', 'debug', "║ ║ ╟─ update commande $logId to $val");
               $monitoGHcmd->event($val);
               $monitoGHcmd->save();
            }

         }

      }

     
    }
   /*    ----- fonction pour créer les commande à partir des array de définition de la classe 
        * dont les clé sont les logicalId des commandes
        * contenant les données name, type et subtype
    */
    public function createCMDFromArray($arrayCMD){
      foreach($arrayCMD as $logId => $setting){
         $monitoGHcmd = $this->getCmd(null, $logId);
         if (!is_object($monitoGHcmd)) {
            $monitoGHcmd = new MonitoGitHubCmd();
            $monitoGHcmd->setLogicalId($logId);
            $monitoGHcmd->setIsVisible(1);
            $monitoGHcmd->setName(__($setting['name'], __FILE__));
            log::add('MonitoGitHub', 'debug', "╟─ creation de la commande : ".$setting['name']." - $logId  de type : ".$setting['type'].'|'.$setting['subtype']);
         }
         $monitoGHcmd->setType($setting['type']);
         $monitoGHcmd->setSubType($setting['subtype']);
         $monitoGHcmd->setEqLogic_id($this->getId());
         $monitoGHcmd->save();
      }
    }
    
 // Fonction exécutée automatiquement avant la création de l'équipement 
    public function preInsert() {
        
    }

 // Fonction exécutée automatiquement après la création de l'équipement 
    public function postInsert() {
        
    }

 // Fonction exécutée automatiquement avant la mise à jour de l'équipement 
    public function preUpdate() {
      if ($this->getConfiguration('owner') == '') {
         throw new Exception(__('Le propriétaire ne peut etre vide',__FILE__));
       }
       if ($this->getConfiguration('repo') == '') {
         throw new Exception(__('Le repo ne peut etre vide',__FILE__));
       }
        
    }

 // Fonction exécutée automatiquement après la mise à jour de l'équipement 
    public function postUpdate() {
        
    }

 // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement 
    public function preSave() {
      log::add('MonitoGitHub', 'debug', '╔═══════════════════════ begin save :'.$this->getHumanName().' ══════════════════════');
    }

 // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement 
    public function postSave() {
     
      $typeSurv=$this->getConfiguration('typesurvey', 'none');
      log::add('MonitoGitHub', 'debug', '╠════ type monito : '.$typeSurv);

      //     les commandes générales
      $this->createCMDFromArray(MonitoGitHub::logID_common);

      //     les commandes par type d'équipement
      switch($typeSurv){
         case 'repos':
            $this->createCMDFromArray(MonitoGitHub::logID_repos);
            break;
         case 'folder':
            break;
         case 'file':
            break;
            
         default:
         log::add('MonitoGitHub','debug', '╠════ type survey not found ('.$typeSurv.')');

      }
      
      log::add('MonitoGitHub', 'debug', '╚═══════════════════════ begin save ══════════════════════');

        
    }
    

 // Fonction exécutée automatiquement avant la suppression de l'équipement 
    public function preRemove() {
        
    }

 // Fonction exécutée automatiquement après la suppression de l'équipement 
    public function postRemove() {
        
    }

}

class MonitoGitHubCmd extends cmd {
    

  // Exécution d'une commande  
     public function execute($_options = array()) {
      log::add('MonitoGitHub','debug', "╔═══════════════════════ execute CMD : ".$this->getId()." | ".$this->getHumanName().", logical id : ".$this->getLogicalId() ."  options : ".print_r($_options));
      log::add('MonitoGitHub','debug', '╠════ Eq logic '.$this->getEqLogic()->getHumanName());
      
      switch($this->getLogicalId()){
         case 'updateMGH':
            $this->getEqLogic()->refreshData();
         	break;
         Default:
         log::add('MonitoGitHub','debug', '╠════ Default call');

      } 
      log::add('MonitoGitHub','debug', "╚═════════════════════════════════════════ END execute CMD ");
     }

    /*     * **********************Getteur Setteur*************************** */
}
