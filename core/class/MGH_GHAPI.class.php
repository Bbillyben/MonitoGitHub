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

class MGH_GHAPI {

   public static function getCOMMIT_infos($owner, $repo, $path, $branch, $user, $token){
      $data = array();
      $data['status']=0;

      // construction des headers
      $headers = MGH_GHAPI::getBaseHeader($user, $token);
      $headersA=[];

      //construction de l'url avec les query
      $url='https://api.github.com/repos/'.$owner.'/'.$repo.'/commits';
      $query='';
      if ($path<>''){
         $query .= 'path='.$path;
      }
      if ($branch<>''){
         $query .= (strlen($query)>0?'&':'').'sha='.$branch;
      }

      if(strlen($query)>0){
         $url.="?".$query;
      }

      log::add('MonitoGitHub', 'debug', '║ ║ ╟─── Commits URL :'.$url);
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─── Headers requete :'.implode(" | ",$headers));

      // construction de la requete
      $ch = curl_init();
      MGH_GHAPI::configureBasecURL($ch, $url, $headers, $headersA);

      /* execution de la requete */
      $result = curl_exec($ch);
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─ headers Answer :'.json_encode($headersA));
      
      /* gestion de l'erreur */
      if (curl_errno($ch)) {
         log::add('MonitoGitHub', 'error', 'Error:'.curl_errno($ch)." / ".curl_error($ch));
         log::add('MonitoGitHub', 'debug', '║ ║ ╟─ Error:'.curl_errno($ch)." / ".curl_error($ch));
      }

      log::add('MonitoGitHub', 'debug', '║ ║ ╟─ status:'.MGH_GHAPI::gvfa($headersA,'status')[0]);
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─ x-ratelimit-remaining :'.MGH_GHAPI::gvfa($headersA,'x-ratelimit-remaining')[0]);

      $dataJson=json_decode($result,true);
      $data['status']=MGH_GHAPI::gvfa($headersA,'status')[0];
      $data['last_commit_date']=MGH_GHAPI::formatDate(MGH_GHAPI::gvfaKR($dataJson[0],array('commit','author','date')));
      $data['last_commit_user']=MGH_GHAPI::gvfaKR($dataJson[0],array('commit','author','name'));
      $data['last_commit_comment_cnt']=MGH_GHAPI::gvfaKR($dataJson[0],array('commit','comment_count'));

      log::add('MonitoGitHub', 'debug', '║ ║ ╟─ last_commit_date:'.$data['last_commit_date']);
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─ last_commit_user:'.$data['last_commit_user']);
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─ last_commit_comment_cnt:'.$data['last_commit_comment_cnt']);

      return $data;
   }

   public static function getPR_infos($owner, $repo, $branch, $user, $token){
      $data = array();
      $data['status']=0;

      // construction des headers
      $headers = MGH_GHAPI::getBaseHeader($user, $token);

      //construction de l'url avec les query
      $url='https://api.github.com/repos/'.$owner.'/'.$repo.'/pulls';

      $query='?state=open&sort=created&direction=desc&per_page=100';
      
      if ($branch<>''){
         $query .= '&base='.$branch;
      }
      $url.=$query;
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─── PR Open URL :'.$url);
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─── Headers requete :'.implode(" | ",$headers));
      
       // construction de la requete
       $ch = curl_init();
       MGH_GHAPI::configureBasecURL($ch, $url, $headers, $headersA);
 
       /* execution de la requete */
       $result = curl_exec($ch);
       log::add('MonitoGitHub', 'debug', '║ ║ ╟─ headers Answer :'.json_encode($headersA));
       
       /* gestion de l'erreur */
       if (curl_errno($ch)) {
          log::add('MonitoGitHub', 'error', 'Error:'.curl_errno($ch)." / ".curl_error($ch));
          log::add('MonitoGitHub', 'debug', '║ ║ ╟─ Error:'.curl_errno($ch)." / ".curl_error($ch));
       }

      log::add('MonitoGitHub', 'debug', '║ ║ ╟─ status:'.MGH_GHAPI::gvfa($headersA,'status')[0]);
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─ x-ratelimit-remaining :'.MGH_GHAPI::gvfa($headersA,'x-ratelimit-remaining')[0]);

      $dataJson=json_decode($result,true);

      $data['status']=MGH_GHAPI::gvfa($headersA,'status')[0];
      $data['pr_open_count']=count($dataJson);
      $data['pr_open_user']=MGH_GHAPI::gvfaKR($dataJson[0],array('user','login'));
      $data['pr_open_date']=MGH_GHAPI::formatDate(MGH_GHAPI::gvfaKR($dataJson[0],array('created_at')));
      $data['pr_open_title']=MGH_GHAPI::gvfaKR($dataJson[0],array('title'));

      log::add('MonitoGitHub', 'debug', '║ ║ ╟─ pr_open_count:'.$data['pr_open_count']);
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─ pr_open_user:'.$data['pr_open_user']);
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─ pr_open_date:'.$data['pr_open_date']);
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─ pr_open_title:'.$data['pr_open_title']);


      // pour les closed
      //construction de l'url avec les query
      $url='https://api.github.com/repos/'.$owner.'/'.$repo.'/pulls';

      $query='?state=closed&sort=created&direction=desc&per_page=100';
      
      if ($branch<>''){
         $query .= '&base='.$branch;
      }
      $url.=$query;
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─── PR Closed URL :'.$url);
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─── Headers requete :'.implode(" | ",$headers));
      // construction de la requete
      $ch = curl_init();
      MGH_GHAPI::configureBasecURL($ch, $url, $headers, $headersA);

      /* execution de la requete */
      $result = curl_exec($ch);
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─ headers Answer :'.json_encode($headersA));

      log::add('MonitoGitHub', 'debug', '║ ║ ╟─ status:'.MGH_GHAPI::gvfa($headersA,'status')[0]);
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─ x-ratelimit-remaining :'.MGH_GHAPI::gvfa($headersA,'x-ratelimit-remaining')[0]);

      $dataJson=json_decode($result,true);

      $data['status']=MGH_GHAPI::gvfa($headersA,'status')[0];
      $data['pr_closed_count']=count($dataJson);
      $data['pr_closed_user']=MGH_GHAPI::gvfaKR($dataJson[0],array('user','login'));
      $data['pr_closed_date']=MGH_GHAPI::formatDate(MGH_GHAPI::gvfaKR($dataJson[0],array('closed_at')));
      $data['pr_closed_title']=MGH_GHAPI::gvfaKR($dataJson[0],array('title'));

      log::add('MonitoGitHub', 'debug', '║ ║ ╟─ pr_closed_count:'.$data['pr_closed_count']);
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─ pr_closed_user:'.$data['pr_closed_user']);
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─ pr_closed_date:'.$data['pr_closed_date']);
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─ pr_closed_title:'.$data['pr_closed_title']);



      return $data;

   }

   public static function getFORK_infos($owner, $repo, $branch, $user, $token){
      $data = array();
      $data['status']=0;

      // construction des headers
      $headers = MGH_GHAPI::getBaseHeader($user, $token);

      //construction de l'url avec les query
      $url='https://api.github.com/repos/'.$owner.'/'.$repo.'/forks';

      $query='?sort=newest&per_page=100';
      
      $url.=$query;
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─── FORK URL :'.$url);
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─── Headers requete :'.implode(" | ",$headers));
      
       // construction de la requete
       $ch = curl_init();
       MGH_GHAPI::configureBasecURL($ch, $url, $headers, $headersA);

        /* execution de la requete */
      $result = curl_exec($ch);
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─ headers Answer :'.json_encode($headersA));

      log::add('MonitoGitHub', 'debug', '║ ║ ╟─ status:'.MGH_GHAPI::gvfa($headersA,'status')[0]);
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─ x-ratelimit-remaining :'.MGH_GHAPI::gvfa($headersA,'x-ratelimit-remaining')[0]);
      $dataJson=json_decode($result,true);

      $data['status']=MGH_GHAPI::gvfa($headersA,'status')[0];
      $data['fork_count']=count($dataJson);
      $data['fork_name']=MGH_GHAPI::gvfaKR($dataJson[0],array('full_name'));
      $data['fork_owner']=MGH_GHAPI::gvfaKR($dataJson[0],array('owner','login'));
      $data['fork_date']=MGH_GHAPI::formatDate(MGH_GHAPI::gvfaKR($dataJson[0],array('created_at')));


      log::add('MonitoGitHub', 'debug', '║ ║ ╟─ fork_count:'.$data['fork_count']);
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─ fork_name:'.$data['fork_name']);
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─ fork_owner:'.$data['fork_owner']);
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─ fork_date:'.$data['fork_date']);

      return $data;

   }


   // utilitaire
   // pour avoir le header de base
   public static function getBaseHeader($user, $token){
      $headers = array();
      $headers[] = "User-Agent:Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:47.0) Gecko/20100101 Firefox/47.0";
      $headers[] ="accept:application/vnd.github.v3+json";

      if($token<>''){
         $headers[] ='Authorization: token '.$token;
      }
      return $headers;
   }
   // pour configurer les curl de base
   public static function configureBasecURL($ch, $url, $headers, &$headersA){
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      // this function is called by curl for each header received
      curl_setopt($ch, CURLOPT_HEADERFUNCTION,
      function($curl, $header) use (&$headersA)
      {
         $len = strlen($header);
         $header = explode(':', $header, 2);
         if (count($header) < 2) // ignore invalid headers
            return $len;

         $headersA[strtolower(trim($header[0]))][] = trim($header[1]);

         return $len;
      }
      );
   }

   // retourne une valeur à partir d'un array si la clé exiiste sinon false
   public static function gvfa($arr, $val){
      if(is_array($arr) && array_key_exists($val,$arr)){
         return $arr[$val];
      }else{
         return false;
      }

   }
   // retourne une valeur à partir d'un array si la suite de clé exiiste sinon false
   public static function gvfaKR($arr, $valArr){
      $pointeur=$arr;
      foreach($valArr as $val){
         if(is_array($pointeur) && array_key_exists($val,$pointeur)){
            $pointeur = $pointeur[$val];
         }else{
            return false;
         }

      }
      return $pointeur;
   }
   // retourne une valeur à partir d'un array si la suite de clé exiiste sinon false
   public static function countInArr($arr, $key, $value){
      $countV=0;
      for($i = 0; $i < count($arr); ++$i) {
         //if(!is_array($arr[$i]))continue;
         if(array_key_exists($key,$arr[$i])){
            if($arr[$i][$key]==$value){
               $countV+=1;
            }
         }
     }
      return $countV;
   }

   // formattage des dates
   public static function formatDate($date){
      return str_replace(array('T','Z'),array(' ',''),$date);

   }



  
   
}


