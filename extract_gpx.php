<?php
session_start();
include_once('Waypoint.class.php');
include_once('Track.class.php');
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
{
	header('Location: upload.php?error=CONNECT_ERR');
	exit;
}
mysql_set_charset('utf8');
if (false == mysql_select_db($base_bdd))
{
	header('Location: upload.php?error=DB_ERR');
	exit;
}
		
	

/********************************************************************
*
*			VERIFICATION DES DONNEES D'ENTREE
*
********************************************************************/

// Vérification de l'intégrité du formulaire
if (!((isset($_POST['nom_activite'])) AND (isset($_POST['commentaire'])) AND (isset($_POST['type_activite'])) AND (isset($_POST['FC_moy'])) AND (isset($_POST['FC_max']))))
{
	header('Location: upload.php?error=CORRUPTED_FORM');
	exit;
}


// Vérification de la présence de la variable superglobale
if (!isset($_FILES['fichier']))
{
	header('Location: upload.php?error=CORRUPTED_FORM');
	exit;
}


// Vérification du type de l'activité
$type_act = (integer) $_POST['type_activite'];
$requete = mysql_prepare('SELECT id FROM ' . $bddTableCategories . ' WHERE id=?');
$resultat = mysql_execute($requete, array($type_act));
$nb_resultat = mysql_num_rows($resultat);
if ($nb_resultat != 1)
{
	header('Location: upload.php?error=CORRUPTED_FORM');
	exit;
}


// Vérification de la validité de la fréquence cardiaque
$freq = (integer)htmlspecialchars($_POST['FC_moy']);
if ( ($freq < $freqCardiaqueMin) OR ($freq > $freqCardiaqueMax))
{
	header('Location: upload.php?error=INVALID_FC');
	exit;
}
$freq = (integer)htmlspecialchars($_POST['FC_max']);
if ( ($freq < $freqCardiaqueMin) OR ($freq > $freqCardiaqueMax))
{
	header('Location: upload.php?error=INVALID_FC');
	exit;
}



// Si erreur lors du téléchargement du fichier
switch ($_FILES['fichier']['error'])
{
	case UPLOAD_ERR_OK:
		break;
	case UPLOAD_ERR_INI_SIZE:
		header('Location: upload.php?error=FILE_TOO_LARGE');
		exit;
		break;
	case UPLOAD_ERR_FORM_SIZE:
		header('Location: upload.php?error=FILE_TOO_LARGE');
		exit;
		break;
	case UPLOAD_ERR_PARTIAL:
		header('Location: upload.php?error=FILE_INCOMPLETE');
		exit;
		break;
	case UPLOAD_ERR_NO_FILE:
		header('Location: upload.php?error=NO_FILE');
		exit;
		break;
	case UPLOAD_ERR_NO_TMP_DIR:
		header('Location: upload.php?error=NO_TMP_FOLDER');
		exit;
		break;
	case UPLOAD_ERR_CANT_WRITE:
		header('Location: upload.php?error=READ_ONLY');
		exit;
		break;
	case UPLOAD_ERR_EXTENSION:
		header('Location: upload.php?error=EXTENSION');
		exit;
		break;
	default:
		header('Location: upload.php?error=UNKNOWN');
		exit;
		break;
}


// Contrôle de la taille du fichier
if ($_FILES['fichier']['size'] > $gpxFileMaxSize)
{
	header('Location: upload.php?error=FILE_TOO_LARGE');
	exit;
}


// Contrôle de l'extension du fichier
if (pathinfo($_FILES['fichier']['name'], PATHINFO_EXTENSION) != 'gpx')
{
	header('Location: upload.php?error=NO_GPX');
	exit;
}


/********************************************************************
*
*				EXPLOITATION DES DONNEES
*
********************************************************************/

// Activation de la gestion des erreurs par l'utilisateur
libxml_use_internal_errors(TRUE);


// Chargement du fichier gpx en mémoire
if (!($fichier_gpx = simplexml_load_file($_FILES['fichier']['tmp_name'])))
{
	header('Location: upload.php?error=LOAD_GPX');
	exit;
}


// Récupération du nom de l'activité
if ((isset($_POST['nom_activite'])) AND ($_POST['nom_activite'] != ''))
	$nom_activite = htmlspecialchars($_POST['nom_activite']);
elseif ((isset($fichier_gpx->trk->name)) AND ($fichier_gpx->trk->name != ''))
	$nom_activite = htmlspecialchars($fichier_gpx->trk->name);
else
{
	header('Location: upload.php?error=NO_NAME');
	exit;
}


// Vérification de la structure du tracé au sein du fichier gpx
if (!(isset($fichier_gpx->trk->trkseg->trkpt)))
{
	header('Location: upload.php?error=LOAD_GPX');
	exit;
}
	
// Création de l'objet TRACE
//****************************
$trace = new Track($nom_activite, $_POST['commentaire'], $_POST['FC_moy'], $_POST['FC_max'], $type_act);
	
	
// Parcours du fichier pour récupérer tous les points
foreach($fichier_gpx->trk->trkseg->trkpt as $p)
{
	// Vérification de la présence de tous les attributs requis
	if (!isset($p->attributes()->lat) OR !isset($p->attributes()->lon) OR !isset($p->ele) OR !isset($p->time))
	{
		header('Location: upload.php?error=LOAD_GPX');
		exit;
	}
	
	$trace->addPoint($p->attributes()->lat, $p->attributes()->lon, $p->ele, $p->time);
}



/********************************************************************
*
*				REALISATION DES CALCULS
*
********************************************************************/

$trace->calculeTemps();
$trace->calculeDistance();
$trace->calculeVitesse();
$trace->calculeVitesseFiltree();
$trace->calculeDenivele();





/********************************************************************
*
*				ECRITURE DES DONNEES DANS LA BASE
*
********************************************************************/
		
	
	// Création de l'activité
	//=========================
	$requete = mysql_prepare('INSERT INTO ' . $bddTableActivites . '(name, commentaire, date_time, duration_total, distance, speed_avg, speed_max, denivele_pos, denivele_neg, alt_min, alt_max, FC_moy, FC_max, cat_id, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');	
	$traceValuesArray = $trace->getValuesArray();
	$myArray = array_merge($traceValuesArray, array($_SESSION['user_id']));
	$resultat = mysql_execute($requete, $myArray);
	
	$idActivity = mysql_insert_id();

	

	// Ecriture de chaque point de l'activité
	//=========================================
	$requete = mysql_prepare('INSERT INTO ' . $bddTableWaypoints . '(date_time, dist_prev_pt, dist_total, lat, lon, ele, speed_instant, speed_filt, time_prev_pt, time_total, activity_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
	$nbPts = $trace->getNbPts();
	
	for($i=0 ; $i<$nbPts ; $i++)
	{
		$PointValuesArray = $trace->getPoint($i);
		$myArray = array_merge($PointValuesArray, array($idActivity));	
		$resultat = mysql_execute($requete, $myArray);
	}




/********************************************************************
*
*				ENREGISTREMENT DU FICHIER
*
********************************************************************/
	$nomFichier = $trace->createFileName($idActivity);
	$fullPathName = $UploadedFilesDirectory . '/' . $nomFichier;
	move_uploaded_file($_FILES['fichier']['tmp_name'], $fullPathName);

	// Mise à jour de l'entrée de la BDD pour ajouter le nom du fichier
	$requete = mysql_prepare('UPDATE ' . $bddTableActivites . ' SET fichier=? WHERE id = ?');
	$resultat = mysql_execute($requete, array($nomFichier, $idActivity));
	

/********************************************************************
*
*				RETOUR A LA PAGE PRINCIPALE
*
********************************************************************/

	header('Location: index.php');

?>