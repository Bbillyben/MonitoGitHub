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
      //construction de l'url avec les query
      $url='https://api.github.com/repos/'.$owner.'/'.$repo.'/commits';
      $query='?per_page=1';

      if ($path<>'')$query .= '&path='.$path;
      if ($branch<>'')$query .= '&sha='.$branch;

      $url.=$query;
      $dataCmd=MGH_GHAPI::computeCMD($url,$user,$token);

      $data['status']=$dataCmd['status'];
      $data['last_commit_date']=MGH_GHAPI::formatDate(MGH_GHAPI::gvfaKR($dataCmd['result'],0,array('commit','author','date')));
      $data['last_commit_user']=MGH_GHAPI::gvfaKR($dataCmd['result'],0,array('commit','author','name'));
      $data['last_commit_comment_cnt']=MGH_GHAPI::gvfaKR($dataCmd['result'],0,array('commit','comment_count'));
      MGH_GHAPI::printData($data);

      return $data;
   }

   public static function getPR_infos($owner, $repo, $branch, $user, $token){
   
      //construction de l'url avec les query
      $url='https://api.github.com/repos/'.$owner.'/'.$repo.'/pulls';

      $query='?per_page=1&state=open&sort=created&direction=desc';
      
      if ($branch<>'')$query .= '&base='.$branch;
      $url.=$query;
      $dataCmd=MGH_GHAPI::computeCMD($url,$user,$token);

      $data['status']=$dataCmd['status'];
      $data['pr_open_count']= MGH_GHAPI::extractPageNum($dataCmd['header'],'link'); //count($dataJson);
      $data['pr_open_user']=MGH_GHAPI::gvfaKR($dataCmd['result'],0,array('user','login'));
      $data['pr_open_date']=MGH_GHAPI::formatDate(MGH_GHAPI::gvfaKR($dataCmd['result'],0,array('created_at')));
      $data['pr_open_title']=MGH_GHAPI::gvfaKR($dataCmd['result'],0,array('title'));

      // pour les closed
      //construction de l'url avec les query
      $url='https://api.github.com/repos/'.$owner.'/'.$repo.'/pulls';

      $query='?state=closed&sort=created&direction=desc&per_page=1';
      
      if ($branch<>'')$query .= '&base='.$branch;
      $url.=$query;
      $dataCmd=MGH_GHAPI::computeCMD($url,$user,$token);

      $data['status']=$dataCmd['status'];
      $data['pr_closed_count']= MGH_GHAPI::extractPageNum($dataCmd['header'],'link'); //count($dataJson);
      $data['pr_closed_user']=MGH_GHAPI::gvfaKR($dataCmd['result'],0,array('user','login'));
      $data['pr_closed_date']=MGH_GHAPI::formatDate(MGH_GHAPI::gvfaKR($dataCmd['result'],0,array('closed_at')));
      $data['pr_closed_title']=MGH_GHAPI::gvfaKR($dataCmd['result'],0,array('title'));

      MGH_GHAPI::printData($data);

      return $data;
      
   }

   public static function getFORK_infos($owner, $repo, $branch, $user, $token){
      //construction de l'url avec les query
      $url='https://api.github.com/repos/'.$owner.'/'.$repo.'/forks';

      $query='?sort=newest&per_page=1';
      
      $url.=$query;
      $dataCmd=MGH_GHAPI::computeCMD($url,$user,$token);

      $data['status']=$dataCmd['status'];
      $data['fork_count']= MGH_GHAPI::extractPageNum($dataCmd['header'],'link'); //count($dataJson);
      $data['fork_name']=MGH_GHAPI::gvfaKR($dataCmd['result'],0,array('full_name'));
      $data['fork_owner']=MGH_GHAPI::gvfaKR($dataCmd['result'],0,array('owner','login'));
      $data['fork_date']=MGH_GHAPI::formatDate(MGH_GHAPI::gvfaKR($dataCmd['result'],0,array('created_at')));
      MGH_GHAPI::printData($data);
     return $data;

   }
   public static function getRELEASE_infos($owner, $repo, $branch, $user, $token){
   
      //construction de l'url avec les query
      $url='https://api.github.com/repos/'.$owner.'/'.$repo.'/releases';

      $query='?per_page=1';
      
      $url.=$query;
      $dataCmd=MGH_GHAPI::computeCMD($url,$user,$token);

      $data['status']=$dataCmd['status'];
      $data['release_count']= MGH_GHAPI::extractPageNum($dataCmd['header'],'link'); //count($dataJson);
      $data['release_owner']=MGH_GHAPI::gvfaKR($dataCmd['result'],0,array('author','login'));
      $data['release_created_date']=MGH_GHAPI::formatDate(MGH_GHAPI::gvfaKR($dataCmd['result'],0,array('created_at')));
     $data['release_published_date']=MGH_GHAPI::formatDate(MGH_GHAPI::gvfaKR($dataCmd['result'],0,array('published_at')));
     MGH_GHAPI::printData($data);
     return $data;
   }


   // utilitaire
   // execution de la commande 
   // return un array avec status, header et result de la réponse
   public static function computeCMD($cmd,$user,$token){
      $data = array();
      $data['status']=0;
      $headers = MGH_GHAPI::getBaseHeader($user, $token);
      $headersA=[];
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─── Commande :'.$cmd);
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─── Headers requete :'.implode(" | ",$headers));

      $ch = curl_init();
      MGH_GHAPI::configureBasecURL($ch, $cmd, $headers, $headersA);
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

       $data['status']=MGH_GHAPI::gvfa($headersA,'status')[0];
       $data['header']=$headersA;
       $data['result']=json_decode($result,true);

       return $data;

   }
   // permet d'imprimer dans le debug les résultats
   public static function printData($data){
      foreach($data as $k=>$v){
         log::add('MonitoGitHub', 'debug', "║ ║ ╟─ $k : $v");
      }
   }
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
   public static function gvfaKR($arr, $id, $valArr){
      if(!is_array($arr) || !array_key_exists($id,$arr))return false;

      $pointeur=$arr[$id];

      foreach($valArr as $val){
         if(is_array($pointeur) && array_key_exists($val,$pointeur)){
            $pointeur = $pointeur[$val];
         }else{
            return false;
         }

      }
      return $pointeur;
   }

   // extrait le nombre de page dans l'entete
   public static function extractPageNum($head,$key){
      $headLink= MGH_GHAPI::gvfa($head, $key);

      if (!$headLink)return 0;
      $link=$headLink[0];
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─ page count link : '.$link);
      $matches = array();
      preg_match('/.*"next".*page=([0-9]*).*"last".*/', $link, $matches);
      log::add('MonitoGitHub', 'debug', '║ ║ ╟─  Page count exrtact from :'.json_encode($matches));

      if (count($matches) > 0 && is_numeric($matches[1])){
         return $matches[1];
      }else{
         return 0;
      }
      
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