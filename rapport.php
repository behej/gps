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
	<h1>Rapports</h1>
</div>


<div class='filtre'>
	<form method="post" action="rapport.php">
		<div>
			<label for="periode">Rapport sur </label>
			<select name="periode" id="periode">
				<option value="0" selected>Tous les jours</option>
				<option value="1">7 derniers jours</option>
				<option value="2">30 derniers jours</option>
				<option value="3">Semaine en cours</option>
				<option value="4">Semaine dernière</option>
				<option value="5">Mois en cours</option>
				<option value="6">Mois dernier</option>
				<option value="7">Année en cours</option>
				<option value="8">Année dernière</option>
			</select>	
		</div>
		<div>
			<label for="categorie"> pour </label>
			<select name="categorie" id="categorie">
				<option value="0" selected>Tout type d'activité</option>
				<option value="1">Footing</option>
				<option value="2">VTT</option>
				<option value="3">Randonnée</option>
			</select>	
		</div>
		
		<div><input type="submit" value="Appliquer" /></div>
	</form>	
</div>


<div class='tableau_rapport'>
	<table>
		<tr class="tab_li_impaire">
			<td>Nombre d'activités</td>
			<td>5</td>
		</tr>
		<tr class="tab_li_paire">
			<td>Durée totale</td>			
			<td>5h 23m 25s</td>
		</tr>
		<tr class="tab_li_impaire">
			<td>Durée moyenne</td>			
			<td>1h 23m 25s</td>
		</tr>
		<tr class="tab_li_paire">
			<td>Distance totale</td>			
			<td>152.25 km</td>
		</tr>
		<tr class="tab_li_impaire">
			<td>Distance moyenne</td>			
			<td>22.25 km</td>
		</tr>
		<tr class="tab_li_paire">
			<td>Dénivelé total</td>			
			<td>1253 m</td>
		</tr>
		<tr class="tab_li_impaire">
			<td>Dénivelé moyen</td>			
			<td>223 m</td>
		</tr>
		<tr class="tab_li_paire">
			<td>Vitesse moyenne</td>			
			<td>12.3 km/h</td>
		</tr>
		</table>

		
</div>
</section>

<?php
	include('footer.php');
?>



</body>
</html>