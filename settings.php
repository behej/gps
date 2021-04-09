<?php

/***************************************************
*
*			INFORMATIONS GENERALES
*
****************************************************/
$version = 0.2;


/***************************************************
*
*			PARAMETRES BASE DE DONNEES
*
****************************************************/
/* Dev */
/*
$host_bdd = 'localhost';
$login_bdd = 'root';
$mdp_bdd = '';
$base_bdd = 'gps';
*/

/* prod */
$host_bdd = 'sql.free.fr';
$login_bdd = '<db_login>';
$mdp_bdd = '<db_password>';
$base_bdd = '<db_name>';



$prefix_tables = 'gps_';
$bddTableActivites = 'activities';
$bddTableWaypoints = 'trackpoints';
$bddTableUtilisateurs = 'users';
$bddTableCategories = 'categories';





/***************************************************
*
*			PARAMETRES
*
****************************************************/
$gpxFileMaxSize = 1000000;
$UploadedFilesDirectory = 'UploadedGpxFiles';
$serverTimeZone = 'Europe/Paris';
$freqCardiaqueMin = 0;
$freqCardiaqueMax = 250;
$nbActParPageDefault = 25;
$nbMaxBtnPages = 5;








/***************************************************
*
*			OPERATIONS AUTOMATIQUES
*
****************************************************/
if (isset($prefix_tables))
{
	$bddTableActivites = $prefix_tables . $bddTableActivites;
	$bddTableWaypoints = $prefix_tables . $bddTableWaypoints;
	$bddTableUtilisateurs = $prefix_tables . $bddTableUtilisateurs;
	$bddTableCategories = $prefix_tables . $bddTableCategories;
}
