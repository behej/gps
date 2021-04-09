<?php
session_start();
include_once('mysql_prepare.php');
include_once('settings.php');


/********************************************************************
*
*			VERIFICATION DE SESSION VALIDE
*
********************************************************************/
if (!isset($_SESSION['login']))
	header('Location: login.php');


	
/********************************************************************
*
*			CONNEXION A LA BASE DE DONNEES
*
********************************************************************/
if (false == mysql_connect($host_bdd, $login_bdd, $mdp_bdd))
	header('Location: index.php?error=CONNECT_ERR');
mysql_set_charset('utf8');
if (false == mysql_select_db($base_bdd))
	header('Location: index.php?error=DB_ERR');
		
	

/********************************************************************
*
*			VERIFICATION DES DONNEES D'ENTREE
*
********************************************************************/

// Vérification de l'intégrité du formulaire
if (!((isset($_GET['id']))))
{
	header('Location: index.php?error=CORRUPTED_FORM');
	exit;
}

// Vérification si l'activité demandée existe et appartient à l'utilisateur loggé
$requete = mysql_prepare('SELECT fichier
							FROM ' . $bddTableActivites . ' 
							WHERE id = ?
								AND user_id = ?');
$resultat = mysql_execute($requete, array((integer)$_GET['id'], $_SESSION['user_id']));
$nb_resultat = mysql_num_rows($resultat);

if ($nb_resultat != 1)
{
	header('Location: index.php?error=CORRUPTED_FORM');
	exit;
}


/********************************************************************
*
*				SUPPRESSION DE L'ACTIVITE
*
********************************************************************/

// Suppression du fichier gpx
//***************************
$activityToDelete = mysql_fetch_array($resultat);
$fichierAEffacer = $UploadedFilesDirectory . '/' . $activityToDelete['fichier'];
unlink($fichierAEffacer);


// Suppression des points de l'activité
//**************************************
$requete = mysql_prepare('DELETE FROM ' . $bddTableWaypoints . ' WHERE activity_id = ?');
$resultat = mysql_execute($requete, array((integer)$_GET['id']));

// Suppression de l'activité
//***************************
$requete = mysql_prepare('DELETE FROM ' . $bddTableActivites . ' WHERE id = ?');
$resultat = mysql_execute($requete, array((integer)$_GET['id']));




/********************************************************************
*
*				RETOUR A LA PAGE PRINCIPALE
*
********************************************************************/

	header('Location: index.php');
?>