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


<!-- HEADER DE LA PAGE -->
<?php
	include("header.php");
?>


<section>
<div class="title_bar">
	<h1>Upload</h1>
</div>


<?php
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


<div class='formulaire_upload'>
	<form method="post" action="extract_gpx.php" enctype="multipart/form-data">
		<table><tbody>
			<tr>
				<td><label for="nom_activite">Nom de l'activité :</label></td>
				<td><input type="text" name="nom_activite" id="nom_activite" /></td>
			</tr>
			<tr>
				<td><label for="commentaire">Commentaire :</label></td>
				<td><textarea name="commentaire" id="commentaire"></textarea></td>
			</tr>
			<tr>
				<td><label for="type_activite">Type d'activité :</label></td>
				<td><select name="type_activite" id="type_activite">				
					<?php
						// Connexion à la BDD
						if (false == mysql_connect($host_bdd, $login_bdd, $mdp_bdd))
							die ('Erreur de connexion à la base');
						mysql_set_charset('utf8');
						if (false == mysql_select_db($base_bdd))
							die ('Erreur de sélection de la base de donnée');
				
						// Récupération de la liste des catégories
						$requete = mysql_prepare('SELECT ' . $bddTableCategories . '.id, nom_cat FROM ' . $bddTableCategories . ' ORDER BY nom_cat ASC');
						$resultat = mysql_execute($requete);
						
						// Afficahge de toutes les catégories
						while ($categorie = mysql_fetch_array($resultat))
						{
							echo '<option value="' . $categorie['id'] . '">' . $categorie['nom_cat'] . '</option>';						
						}
					?>
				</select></td>
			</tr>
			<tr>
				<td><label for="FC_moy">FC moyenne :</label></td>
				<td><input type="text" name="FC_moy" id="FC_moy" /></td>
			</tr>
			<tr>
				<td><label for="FC_max">FC max :</label></td>
				<td><input type="text" name="FC_max" id="FC_max" /></td>
			</tr>
			<tr>
				<td><label for="fichier">Fichier :</label></td>
				<td><input type="file" name="fichier" id="ficher" /></td>
			</tr>
			<tr>
				<td colspan="2" class='btn_upload'><input type="submit" value="Upload" /></td>
			</tr>
		</tbody></table>
	</form>
</div>


</section>



<?php
	include('footer.php');
?>



</body>
</html>