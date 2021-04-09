 <?php
	session_start();
	include_once('settings.php');
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<link rel="stylesheet" href="style.css" />
	<title>Activités</title>
</head>


<body>


<!-- HEADER DE LA PAGE -->
<?php
	include("header.php");
?>


<section>
<div class="title_bar">
	<h1>Préférences</h1>
</div>


<div class='formulaire_upload'>
	<form method="post" action="preferences.php">
		<table><tbody>
			<tr>
				<td><label for="pseudo">Pseudo :</label></td>
				<td><input type="text" name="pseudo" id="pseudo" value='Djé'/></td>
			</tr>
			<tr>
				<td><label for="mdp">Nouveau mot de passe :</label></td>
				<td><input type="password" name="mdp" id="mdp"/></td>
			</tr>
			<tr>
				<td><label for="mdp_confirm">Confirmation du mot de passe :</label></td>
				<td><input type="password" name="mdp_confirm" id="mdp_confirm"/></td>
			</tr>
			<tr>
				<td><label for="nb_activite">Nombre d'activités par page</label></td>
				<td><select name="nb_activite" id="nb_activite" />
					<option value='10'>10</option>
					<option value='20'>20</option>
					<option value='50'>50</option>
					<option value='100'>100</option>
					<option value='0'>Tout</option>
				</td>
			</tr>
			<tr>
				<td>Supprimer une catégorie :</td>
				<td>
					<input type="checkbox" name='delete_footing' id='delete_footing'/>
					<label for="delete_footing">Footing</label>
					<br />
					<input type="checkbox" name='delete_VTT' id='delete_VTT'/>
					<label for="delete_VTT">VTT</label>
					<br />
					<input type="checkbox" name='delete_randonnee' id='delete_randonnee'/>
					<label for="delete_randonnee">Randonnée</label>
				</td>
			</tr>
			<tr>
				<td><label for="nouvelle_cat">Ajouter une nouvelle catégorie :</label></td>
				<td><input type="text" name="nouvelle_cat" id="nouvelle_cat" /></td>
			</tr>
			
			<tr>
				<td colspan="2" class='btn_upload'>
					<input type="reset" value="Annuler" />
					<input type="submit" value="Sauvegarder" />
				</td>
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