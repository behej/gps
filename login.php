<?php session_start()
	include_once('settings.php');
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<link rel="stylesheet" href="style.css" />
	<title>Log in</title>
	
	<?php
	// Inclusion des fichiers nécessaires
	include('mysql_prepare.php');
	
	
	// DECONNEXION DE L'UTILISATEUR
	//==============================
	if (isset($_GET['action']))
		if ($_GET['action'] == 'logout')
			session_destroy();


	// EXECUTION DU FORMULAIRE DE CONNEXION
	//======================================
	
	if ( (isset($_POST['utilisateur'])) AND (isset($_POST['motdepasse'])))
	{
		// Anti injection de code
		$login = strip_tags($_POST['utilisateur']);
		$motdepasse = crypt(strip_tags($_POST['motdepasse']), '$6$rounds=5000$lesaltquelconque$');
		// $_SESSION['login'] = $login;
		

		// Connexion à la BDD et sélection de la base
		if (false == mysql_connect($host_bdd, $login_bdd, $mdp_bdd))
			die ('Erreur de connexion à la base');
		mysql_set_charset('utf8');
		if (false == mysql_select_db($base_bdd))
			die ('Erreur de sélection de la base de donnée');
			
		
		// Requête SQL: est-ce que l'utilisateur existe ?
		$requete = mysql_prepare('SELECT * FROM ' . $bddTableUtilisateurs . ' WHERE login = ? AND password = ?');
		$resultat = mysql_execute($requete, array($login, $motdepasse));
		$nb_resultat = mysql_num_rows($resultat);
		
		if ($nb_resultat == 1)
		{
			// Utilisateur trouvé
			$ligne = mysql_fetch_array($resultat);
			$_SESSION['login'] = $login;
			$_SESSION['user_id'] = $ligne['id'];
			header('Location: index.php');
		}
		elseif ($nb_resultat == 0)
		{
			// Aucun utilisateur trouvé
			$erreur = 'Mauvais login ou mot de passe';
		}
		else
		{
			// Erreur: plusieurs utilisateurs trouvés
			$erreur = 'Une erreur est survenue';
		}
		
		// Libération des ressources
		mysql_free_result($resultat);
		mysql_close();
		
	}
	?>
	
	
	
</head>


<body>


<!-- HEADER DE LA PAGE -->
<?php
	include("header.php");
?>


<section>
<div class="title_bar">
	<h1>Connexion</h1>
</div>



<?php
	// Si une erreur est survenue lors de la connexion
	if (isset($erreur))
		echo '<div class=\'erreur_connexion\'>' . $erreur . '</div>';

		
?>




<div class='connexion'>
	<form method="post" action="login.php">
	
	<div class='champ_connexion'>
		<label for="utilisateur">Login<br /></label>
		<input type="text" name="utilisateur" id="utilisateur" />
	</div>
	<div class='champ_connexion'>
		<label for="motdepasse">Mot de passe<br /></label>
		<input type="password" name="motdepasse" id="motdepasse"/>
	</div>
	<input type="submit" value="Connexion" class='btn_connexion' />
	
	</form>


		
</div>
</section>

<?php
	include('footer.php');
?>



</body>
</html>
