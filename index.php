<?php
/*
Planning Biblio, Version 1.9.4
Licence GNU/GPL (version 2 et au dela)
Voir les fichiers README.md et LICENSE
Copyright (C) 2011-2015 - Jérôme Combes

Fichier : index.php
Création : mai 2011
Dernière modification : 7 avril 2015
Auteur : Jérôme Combes, jerome@planningbilbio.fr

Description :
Page principale,
Vérifie si la base de données doit être mise à jour, inclus les pages de configuration et
de fonctions communes, vérifie les droits à la page demandée en argument et l'inclus si l'utilisateur
est autorisé à la consulter

Inclut au départ les fichiers config.php, doctype.php et header.php
Inclut à la fin le fichier footer.php
*/

session_start();

// Version
$version="1.9.4";

// Redirection vers setup si le fichier config est absent
if(!file_exists("include/config.php")){
  header("Location: setup/index.php");
  exit;
}

require_once "include/config.php";
require_once "include/sanitize.php";

// Error reporting
ini_set('display_errors',$config['display_errors']);
switch($config['error_reporting']){
  case 0: error_reporting(0); break;
  case 1: error_reporting(E_ERROR | E_WARNING | E_PARSE); break;
  case 2: error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE); break;
  case 3: error_reporting(E_ALL ^ (E_NOTICE | E_WARNING)); break;
  case 4: error_reporting(E_ALL ^ E_NOTICE); break;
  case 5: error_reporting(E_ALL); break;
  default: error_reporting(E_ALL ^ E_NOTICE); break;
}

// Initialisation des variables
$date=filter_input(INPUT_GET,"date",FILTER_CALLBACK,array("options"=>"sanitize_dateSQL"));
$menu_get=filter_input(INPUT_GET,"menu",FILTER_SANITIZE_STRING);
$menu_post=filter_input(INPUT_POST,"menu",FILTER_SANITIZE_STRING);
$menu=($menu_get=="off" or $menu_post=="off")?false:true;

$page_get=filter_input(INPUT_GET,"page",FILTER_CALLBACK,array("options"=>"sanitize_page"));
$page_post=filter_input(INPUT_POST,"page",FILTER_CALLBACK,array("options"=>"sanitize_page"));
if($page_post){
  $page=$page_post;
}elseif($page_get){
  $page=$page_get;
}else{
  $page="planning/poste/index.php";
}

$_SESSION['PLdate']=array_key_exists("PLdate",$_SESSION)?$_SESSION['PLdate']:date("Y-m-d");

if(!array_key_exists("oups",$_SESSION)){
  $_SESSION['oups']=array("week"=>false);
}
  
// Affichage de tous les plannings de la semaine
if($page=="planning/poste/index.php" and !$date and $_SESSION['oups']['week']){
  $page="planning/poste/semaine.php";
}


date_default_timezone_set("Europe/Paris");

// Vérification de la version de la base de données
// Si la version est différente, mise à jour de la base de données 
if($version!=$config['Version']){
  include "include/maj.php";
}
// Sinon, on continue
else{
  include "include/feries.php";
  include "plugins/plugins.php";
  include "include/cron.php";
}

//		Si pas de session, redirection vers la page d'authentification
if(!$_SESSION['login_id']){
  if(!$menu)	// dans le cas d'une action executée dans un popup alors que la session a été perdue, on affiche la page d'auth sur le parent
    echo "<script type='text/JavaScript'>parent.location.href='authentification.php';</script>\n";
  else{
    $redirURL=urlencode(addslashes($_SERVER['REQUEST_URI']));
    header("Location: authentification.php?redirURL=$redirURL");		// session perdue, on affiche la page d'authentification
  }
}

include "include/header.php";
if($menu){
  include "include/menu.php";
}

//		Recupération des droits d'accès de l'agent
$db=new db();
$db->select2("personnel","droits",array("id"=>$_SESSION['login_id']));
$droits=unserialize($db->result[0]['droits']);
$droits[]=99;	// Ajout du droit de consultation pour les connexions anonymes
$_SESSION['droits']=$droits;

//		Droits necessaires pour consulter la page en cours
$db=new db();
$db->select2("acces","*",array("page"=>$page));

echo "<div id='content'>\n";
if(in_array($db->result[0]['groupe_id'],$droits)){
  include $page;
}
else{
  echo "<div id='acces_refuse'>Accès refusé</div>\n";
}
if($menu){
  include "include/footer.php";
}
?>