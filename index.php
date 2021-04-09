 <?php
	session_start();
	include_once('settings.php');
	include_once('mysql_prepare.php');
 ?>
 
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<link rel="stylesheet" href="style.css" />
	<title>Activités</title>

<?php
	// VERIFICATION SI UNE SESSION EST OUVERTE	
	if (!isset($_SESSION['login']))
		header('Location: login.php');	
?>	
	
</head>


<body>


<!-- #################################################### -->
<!-- #													# -->
<!-- #						HEADER						# -->
<!-- #													# -->
<!-- #################################################### -->
<?php
	include("header.php");
?>


<section>
<div class="title_bar">
	<h1>Activités</h1>
	<div class='btn_tools'>
		<a href='upload.php'><img src="images/icon_upload.png" alt="upload" title='Upload new activity' class="icone" /></a>
		<a href=#><img src="images/icon_compare.png" alt="compare" title='Compare activities' class="icone" /></a>
		<a href=#><img src="images/icon_delete.png" alt="delete" title='Delete' class="icone" /></a>
	</div>
</div>


<?php
/************************************
		GESTION DES ERREURS
************************************/
	// Si une erreur est survenue lors de la soumission du formulaire
	if (isset($_GET['error']))
	{
		include('error_msg.php');
	
		$code_erreur = htmlspecialchars($_GET['error']);
		if (!array_key_exists($code_erreur, $error_list))
			$code_erreur = 'UNKNOWN';
	
	
		echo '<div class=\'erreur_connexion\'>' . $error_list[$code_erreur] . '</div>';
	}
?>


<?php
/***********************************************************
*
*		CONNEXION A LA BASE DE DONNEES
*
***********************************************************/

	if (false == mysql_connect($host_bdd, $login_bdd, $mdp_bdd))
		die ('Erreur de connexion à la base');
	mysql_set_charset('utf8');
	if (false == mysql_select_db($base_bdd))
		die ('Erreur de sélection de la base de donnée');
?>


<!-- #################################################### -->
<!-- #													# -->
<!-- #				FILTRE DES ACTIVITES				# -->
<!-- #													# -->
<!-- #################################################### -->

<?php
/**************************************************
*			LISTING DES CATEGORIES
**************************************************/
	$requete = mysql_prepare('SELECT ' . $bddTableCategories . '.id, nom_cat FROM ' . $bddTableCategories . ' ORDER BY nom_cat ASC');
	$resultat = mysql_execute($requete);
?>

	
<div class='filtre'>
	<form method="post" action="traitement.php">
		<table class='tableau_filtre'>
			<tr>
				<td class='filtre_basic_col'>
					<table class='filtre_basic_tab'>
						<tr>
							<td class='filtre_libelle'><label for="type_activite">Type :</label></td>
							<td class='filtre_valeur'><select name="type_activite" id="type_activite">
								<option value="0" selected>Tout type d'activité</option>
								<?php
									// Afficahge de toutes les catégories
									while ($categorie = mysql_fetch_array($resultat))
										echo '<option value="' . $categorie['id'] . '">' . $categorie['nom_cat'] . '</option>';
								?>
								</select></td>
						</tr>
						<tr>
							<td class='filtre_libelle'><label for="periode_activite">Période :</label></td>
							<td class='filtre_valeur'><select name="periode_activite" id="periode_activite">
								<option value="0" selected>Tout le temps</option>
								<option value="1">7 derniers jours</option>
								<option value="2">30 derniers jours</option>
								<option value="3">365 derniers jours</option>
								<option value="4">Année en cours</option>
								</select></td>
						</tr>
					</table>
				</td>
				<td class='filtre_avance_col'>
					<table class='filtre_avance_tab'>
						<tr>
							<td class='filtre_libelle'><label for="duree_min">Durée :</label></td>
							<td class='filtre_valeur'><input type="text" size="3" name="duree_min" id="duree_min"/>
								- <input type="text" size="3" name="duree_max" id="duree_max"/> h</td>
						</tr>
						<tr>
							<td class='filtre_libelle'><label for="distance_min">Distance :</label></td>
							<td class='filtre_valeur'><input type="text" size="3" name="distance_min" id="distance_min"/>
								- <input type="text" size="3" name="distance_max" id="distance_max"/> km</td>
						</tr>
						<tr>
							<td class='filtre_libelle'><label for="denivelle_min">Dénivellé :</label></td>
							<td class='filtre_valeur'><input type="text" size="3" name="denivelle_min" id="denivelle_min"/>
								- <input type="text" size="3" name="denivelle_max" id="denivelle_max"/> m</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>

		<input id='btn_filtrer' type="submit" value="Filtrer" />
	</form>	
</div>

<?php
	mysql_free_result($resultat);
?>



<!-- #################################################### -->
<!-- #													# -->
<!-- #		AFFICHAGE DE LA LISTE DES ACTIVITES			# -->
<!-- #													# -->
<!-- #################################################### -->

<?php
/**************************************************
*		RECUPERE PREFERENCES UTILISATEUR
**************************************************/

	// Nombre d'activités par page
	$requete = mysql_prepare('SELECT activiteParPage FROM ' . $bddTableUtilisateurs . ' WHERE id = ?');
	$resultat = mysql_execute($requete, array($_SESSION['user_id']));
	$actParPage = mysql_fetch_array($resultat);
	mysql_free_result($resultat);
	$actParPage = $actParPage['activiteParPage'];
	if ($actParPage <= 0)
		$actParPage = $nbActParPageDefault;
	
	// Nombre total d'activités à afficher
	$requete = mysql_prepare(
				'SELECT COUNT(' . $bddTableActivites . '.id) AS nombre
				FROM (' . $bddTableActivites . ' LEFT OUTER JOIN ' . $bddTableCategories . '
						ON ' . $bddTableActivites . '.cat_id = ' . $bddTableCategories . '.id)
						INNER JOIN ' . $bddTableUtilisateurs . '
						ON ' . $bddTableActivites . '.user_id = ' . $bddTableUtilisateurs . '.id				
				WHERE ' . $bddTableUtilisateurs . '.id = ?');
	$resultat = mysql_execute($requete, array($_SESSION['user_id']));
	$nbTotalActivites = mysql_fetch_array($resultat);
	$nbTotalActivites = $nbTotalActivites['nombre'];
	
	// nombre de pages
	$nbPages = (int) ceil($nbTotalActivites / $actParPage);
	
	// Récupération du nombre de page depuis l'URL
	if (isset($_GET['page']))
		$page = (int) htmlspecialchars($_GET['page']);
	else
		$page = 1;
	
	// Contrôle la plage de validité
	if (($page <= 0) || ($page > $nbPages))
		$page = 1;
	 
	


	
/********************************************************
*														*
*	ETABLISSEMENT DE LA LISTE DE TOUTES LES ACTIVITES	*
*														*
********************************************************/

// On récupère uniquement les numéros de toutes les activités qui correspondent aux critères
// Cette liste sera utilisée pour naviguer parmi les activités avec les boutons 'suivants' et 'précédents'

	$requete = mysql_prepare(
				'SELECT ' . $bddTableActivites . '.id
				FROM (' . $bddTableActivites . ' LEFT OUTER JOIN ' . $bddTableCategories . '
						ON ' . $bddTableActivites . '.cat_id = ' . $bddTableCategories . '.id)
						INNER JOIN ' . $bddTableUtilisateurs . '
						ON ' . $bddTableActivites . '.user_id = ' . $bddTableUtilisateurs . '.id				
				WHERE ' . $bddTableUtilisateurs . '.id = ?
				ORDER BY ' . $bddTableActivites . '.date_time DESC'
				);
	$resultat = mysql_execute($requete, array($_SESSION['user_id']));
	$_SESSION['liste_activities'] = null;
	while ($num = mysql_fetch_array($resultat))
	{
		$_SESSION['liste_activities'][] = $num['id'];
	}

	
/********************************************************
*														*
*	ETABLISSEMENT DE LA LISTE DES ACTIVITES	A AFFICHER	*
*														*
********************************************************/

// on récupère les activités ainsi que leurs différents attributs
// Cette liste est limitée au nombre max d'activités affichées
// Elle est utilisée pour l'affichage du tableau récap

	$requete = mysql_prepare(
				'SELECT ' . $bddTableActivites . '.id, name, date_time, duration_total, speed_avg, distance, denivele_pos, FC_moy, FC_max, nom_cat
				FROM (' . $bddTableActivites . ' LEFT OUTER JOIN ' . $bddTableCategories . '
						ON ' . $bddTableActivites . '.cat_id = ' . $bddTableCategories . '.id)
						INNER JOIN ' . $bddTableUtilisateurs . '
						ON ' . $bddTableActivites . '.user_id = ' . $bddTableUtilisateurs . '.id				
				WHERE ' . $bddTableUtilisateurs . '.id = ?
				ORDER BY ' . $bddTableActivites . '.date_time DESC
				LIMIT ?
				OFFSET ?'				);
	$resultat = mysql_execute($requete, array($_SESSION['user_id'], $actParPage, ($page-1)*$actParPage));
?>

<div class='tableau'>
	<table>
		<thead>
			<th>Nom de l'activité</th>
			<th>Type</th>
			<th>Date</th>
			<th>Heure</th>
			<th>Durée</th>
			<th>Distance</th>
			<th>Dénivelé</th>
			<th>Vitesse moyenne</th>
			<th>FC Moy.</th>			
			<th>FC Max</th>
		</thead>
		
<?php
	$i = 1;
	
	while ($act = mysql_fetch_array($resultat))	
	{	
		if ($i == 1)	{
			echo '<tr class="tab_li_impaire">';
			$i = 2;		}
		else			{
			echo '<tr class="tab_li_paire">';
			$i = 1;		}
		
			echo '<td><a href=\'activite.php?id=' . $act['id'] . '\'>' . $act['name'] . '</a></td>';
			echo '<td>' . $act['nom_cat'] . '</td>';
			echo '<td>' . date("d/m/Y", strtotime($act['date_time'])) . '</td>';
			echo '<td>' . date("H:i:s", strtotime($act['date_time'])) . '</td>';
			echo '<td>' . $act['duration_total'] . '</td>';
			echo '<td>' . $act['distance'] . ' km</td>';
			echo '<td>' . $act['denivele_pos'] . ' m</td>';
			echo '<td>' . $act['speed_avg'] . ' km/h</td>';
			echo '<td>' . (($act['FC_moy'] == 0)? '-' : $act['FC_moy']) . '</td>';
			echo '<td>' . (($act['FC_max'] == 0)? '-' : $act['FC_max']) . '</td>';
		echo '</tr>';
	}
?>
		</table>

		
<!-- #################################################### -->
<!-- #													# -->
<!-- #			NAVIGATION ENTRE LES PAGES				# -->
<!-- #													# -->
<!-- #################################################### -->

<?php

// Calcul du nb de boutons avant et après la page courante
$nbBtnAvant = (int) floor(($nbMaxBtnPages - 1) / 2);
$nbBtnApres = (int) ceil(($nbMaxBtnPages - 1) / 2);

// Si on est dans les dernières pages, alors on affiche le max de pages avant
if (($page+$nbBtnApres) > $nbPages)
	$pageDebut = $nbPages - $nbMaxBtnPages + 1;
// Sinon, moitié pages avant, moitié pages après
else
	$pageDebut = $page - $nbBtnAvant;

// Contrôle si pas débordement à gauche
if ($pageDebut < 1)
	$pageDebut = 1;

if ($nbPages > 1)
{
	// Si page supérieur à page1, alors affichage flèche 'page précédente'
	if ($page > 1)	echo '<a href=index.php?page=' . ($page-1) . '><div class="page_nb"><</div></a>' . "\n";

	if ($pageDebut > 1)
		echo '<div class="page_nb">...</div>' . "\n";
		
	
	// Affichage des numéro de page
	for ($i=$pageDebut ; (($i<=$nbPages) && ($i<($pageDebut+$nbMaxBtnPages))) ; $i++)
	{
		// Distinction page en cours et autre page
		if ($i == $page)
			echo '<div class="current_page_nb">' . $i . '</div>' . "\n";
		else
			echo '<a href=index.php?page=' . $i . '><div class="page_nb">' . $i . '</div></a>' . "\n";
	}	
	
	if (($i-1) < $nbPages)
		echo '<div class="page_nb">...</div>' . "\n";

	// Si page inférieure à dernière page, alors affichage flèche 'page suivante'
	if ($page < $nbPages)	echo '<a href=index.php?page=' . ($page+1) . '><div class="page_nb">></div></a>' . "\n";
}
?>

		
</div>
</section>


<!-- #################################################### -->
<!-- #													# -->
<!-- #						FOOTER						# -->
<!-- #													# -->
<!-- #################################################### -->
<?php
	include('footer.php');
?>


</body>
</html>
