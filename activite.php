<?php
	session_start();
	include_once('settings.php');
	include_once('mysql_prepare.php');
	
	
	
/********************************************************************
*
*			VERIFICATION DE SESSION VALIDE
*
********************************************************************/
if (!isset($_SESSION['login']))
	header('Location: login.php');
	
	
	
/********************************************************************
*
*	Contrôles préliminaires
*
********************************************************************/
	if (!isset($_GET['id']))
		header('Location: index.php?error=NO_ACT');


	// Connexion à la BDD et sélection de la base
	if (false == mysql_connect($host_bdd, $login_bdd, $mdp_bdd))
		header('Location: index.php?error=CONNECT_ERR');
	mysql_set_charset('utf8');
	if (false == mysql_select_db($base_bdd))
		header('Location: index.php?error=DB_ERR');

	// Requête SQL: est-ce que l'utilisateur existe ?
	$requete = mysql_prepare('SELECT lat, lon, ele, time_total, speed_filt
							FROM ' . $bddTableWaypoints . ' RIGHT OUTER JOIN ' . $bddTableActivites . '
								ON '. $bddTableWaypoints . '.activity_id = ' . $bddTableActivites . '.id
							WHERE ((' . $bddTableActivites . '.id = ?)
								AND (' . $bddTableActivites . '.user_id = ?))');
	$resultat = mysql_execute($requete, array((integer)$_GET['id'], $_SESSION['user_id']));
	$nb_resultat = mysql_num_rows($resultat);

	if ($nb_resultat <= 0)
		header('Location: index.php?error=NO_MATCH');

?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<link rel="stylesheet" href="style.css" />
	<title>Activité footing</title>
	
	<!-- JAVASCRIPT API GOOGLE MAP -->
	<script type="text/javascript"
		src="https://maps.googleapis.com/maps/api/js?key=<insertApiKeyHere>&sensor=TRUE">
    </script>
	
	<!-- JAVASCRIPT POUR CHARGER & AFFICHER LA CARTE -->
    <script type="text/javascript">
		function initialize()
		{
			// Définitions des options d'afficahge de la carte
			var mapOptions =
				{
					center: new google.maps.LatLng(0, 0), // ou {lat: 45.5, lng: 5.2}
					zoom: 1,
					mapTypeId: google.maps.MapTypeId.TERRAIN
				};
			
			// Création de la carte
			var map = new google.maps.Map(document.getElementById("carte"), mapOptions);
			
			<?php
			// Définition des points du parcours
			echo 'var parcours_coordonnees = [';
			while ($p = mysql_fetch_array($resultat))
			{
				echo 'new google.maps.LatLng(' . $p['lat'] . ', ' . $p['lon'] . '),';
			}
			echo '];';
			mysql_data_seek ($resultat , 0);
			?>

	
			// Centrage et zoom auto pour afficher l'intégralité du tracé
			var bounds = new google.maps.LatLngBounds();
			for (var i=0 ; i<parcours_coordonnees.length ; i++)
			{
				bounds.extend(parcours_coordonnees[i]);
			}
			map.setCenter(bounds.getCenter()); //or use custom center
			map.fitBounds(bounds);

			// Création & afficahge du parcours
			var parcours = new google.maps.Polyline({
				path: parcours_coordonnees,
				geodesic: true,
				strokeColor: '#FF0000',
				strokeOpacity: 1.0,
				strokeWeight: 2
				});

			parcours.setMap(map);			
		}
		
		google.maps.event.addDomListener(window, 'load', initialize);
	</script>
	
	<!-- JAVASCRIPT POUR L'AFFICHAGE DES COURBES -->
	<script language="javascript" type="text/javascript" src="./flot/jquery.min.js"></script>
	<script language="javascript" type="text/javascript" src="./flot/jquery.flot.min.js"></script>
	<script language="javascript" type="text/javascript" src="./flot/jquery.flot.time.min.js"></script>
	<script language="javascript" type="text/javascript" src="./flot/jquery.flot.crosshair.min.js"></script>
	<script language="javascript" type="text/javascript" src="./flot/jquery.flot.axislabels.js"></script>
	<script type="text/javascript">
		$(function()
		{
			<?php
				// Reglage du fuseau horaire
				date_default_timezone_set('UTC');			
			
				// Génération du tableau de valeurs pour l'altitude
				echo 'var altitude = [';
				while ($p = mysql_fetch_array($resultat))
				{
					echo '[' . (strtotime($p['time_total'], 0)*1000) . ' , ' . $p['ele'] . '], ';
				}
				echo '];';
				mysql_data_seek ($resultat , 0);
				
				// Génération du tableau de valeur pour la vitesse
				echo 'var vitesse = [';
				while ($p = mysql_fetch_array($resultat))
				{
					echo '[' . (strtotime($p['time_total'], 0)*1000) . ' , ' . round($p['speed_filt']*3.6, 1) . '], ';
				}
				echo '];';
				mysql_data_seek ($resultat , 0);
			?>
			
			
			//var plot = null;
			
			function doPlot(position)
			{
				plot = $.plot("#courbes", [
					{data: vitesse, label: "Vitesse: 00.0 km/h"},
					{data: altitude, label: "Altitude: 000 m", yaxis: 2}
				], {
					axisLabels: {
						show: true
					},
					xaxis: {mode: "time",
							axisLabel: 'Temps',
					},
					yaxes: [
						{position: "left", axisLabel: 'Vitesse',},
						{position: "right", axisLabel: 'Altitude',}
						],
					series: {lines: {show: true}},
					grid: {
						hoverable: true,
						autoHighlight: false
						},
					crosshair: {mode: "x"}
				});				
			}
			
			doPlot("right");
			

		
		var legends = $("#courbes .legendLabel");
		

		legends.each(function () {
			// fix the widths so they don't jump around
			$(this).css('width', $(this).width());
			});

		var updateLegendTimeout = null;
		var latestPosition = null;

		

			// Mise à jour de la légende
			function updateLegend()
			{
				updateLegendTimeout = null;
				var pos = latestPosition;

				var axes = plot.getAxes();

				if (pos.x < axes.xaxis.min || pos.x > axes.xaxis.max ||
					pos.y < axes.yaxis.min || pos.y > axes.yaxis.max)
					{
						return;
					}


				var i, j, dataset = plot.getData();
				for (i = 0; i < dataset.length; ++i)
				{

					var series = dataset[i];

					// Find the nearest points, x-wise
					for (j = 0; j < series.data.length; ++j) {
						if (series.data[j][0] > pos.x) {
							break;
						}
					}

					// Now Interpolate
					var y,
						p1 = series.data[j - 1],
						p2 = series.data[j];

					if (p1 == null) {
						y = p2[1];
					} else if (p2 == null) {
						y = p1[1];
					} else {
						y = p1[1] + (p2[1] - p1[1]) * (pos.x - p1[0]) / (p2[0] - p1[0]);
					}
					
					legends.eq(i).text(series.label.replace(/: [0-9\.]*/, ": " + y.toFixed(1)));
				}
			}
	


		$("#courbes").bind("plothover",  function (event, pos, item) {
			latestPosition = pos;
			if (!updateLegendTimeout) {
				updateLegendTimeout = setTimeout(updateLegend, 50);
			}
		});

		}
	);
	</script>
	
	
</head>


<body>








<!-- HEADER DE LA PAGE -->
<?php
	include("header.php");
?>


<?php
	$requete = mysql_prepare('SELECT name, date_time, duration_total, duration_move, speed_avg, speed_move, speed_max, distance, denivele_pos, denivele_neg, alt_min, alt_max, FC_moy, FC_max, commentaire, fichier FROM ' . $bddTableActivites . ' WHERE id = ?');
	$resultat = mysql_execute($requete, array((integer)$_GET['id']));
	$activite = mysql_fetch_array($resultat);
?>


<section>
<div class="title_bar">
	<h1>
		<?php
			echo $activite['name'];
		?>
	</h1>
	<div class='btn_tools'>
		<a href=#><img src="images/icon_modify.png" alt="modify" title='Modify' class="icone" /></a>
		<a href='<?php echo $UploadedFilesDirectory . '/' . $activite['fichier']; ?>'><img src="images/icon_download.png" alt="download" title='Download' class="icone" /></a>
		<a href='delete.php?id=<?php echo (integer)$_GET['id']; ?>'><img src="images/icon_delete.png" alt="delete" title='Delete' class="icone" /></a>
	</div>
	
<!-- Boutons 'suivant' et 'précédent' -->
<!-- ******************************** -->
	<div class='btn_nav'>
	
<?php
	$index = array_search($_GET['id'], $_SESSION['liste_activities']);
	
// Bouton 'précédent'
	if ($index <= 0)
		echo '<span class=\'inactive_link\'>< Précédent</span>';
	else
		echo '<a href=\'activite.php?id=' . $_SESSION['liste_activities'][$index-1] . '\'>< Précédent</a>';
	 
// Bouton 'suivant'
	if ($index >= (count($_SESSION['liste_activities'])-1) )
		echo '<span class=\'inactive_link\'>Suivant ></span>';
	else
		echo '<a href=\'activite.php?id=' . $_SESSION['liste_activities'][$index+1] . '\'>Suivant ></a>';
		
?>
	</div>
	
	
	<div class='dateheure_activite'>
		<?php
			echo date("l j F Y - G:i", strtotime($activite['date_time']));
		?>
	
	</div>
	<div class='commentaire_activite'>
		<?php
			echo $activite['commentaire'];
		?>
	</div>
</div>



<div class='stats'>
	<table>
		<thead>
			<th>Distance</th>
			<th>Temps total</th>
			<th>Temps déplacement</th>
			<th>Vitesse moyenne</th>
			<th>Vitesse en déplacement</th>
			<th>Vitesse max</th>
			<th>Dénivellé +</th>
			<th>Dénivellé -</th>
			<th>Altitude min</th>
			<th>Altitude max</th>
			<th>FC moy</th>
			<th>FC max</th>
		</thead>
		<tr>
			<td><?php echo $activite['distance']; ?> km</td>
			<td><?php echo $activite['duration_total']; ?></td>
			<td><?php echo $activite['duration_move']; ?></td>
			<td><?php echo $activite['speed_avg']; ?> km/h</td>
			<td><?php echo $activite['speed_move']; ?> km/h</td>
			<td><?php echo $activite['speed_max']; ?> km/h</td>
			<td><?php echo $activite['denivele_pos']; ?> m</td>
			<td><?php echo $activite['denivele_neg']; ?> m</td>
			<td><?php echo $activite['alt_min']; ?> m</td>
			<td><?php echo $activite['alt_max']; ?> m</td>
			<td><?php echo (($activite['FC_moy']==0)? '-' : $activite['FC_moy']); ?></td>
			<td><?php echo (($activite['FC_max']==0)? '-' : $activite['FC_max']); ?></td>
		</tr>
	</table>		
</div>

<div id="courbes"></div>

<div id='carte' />


</section>

<?php
	include('footer.php');
?>



</body>
</html>
