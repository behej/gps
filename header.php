<?php
if (session_status() != PHP_SESSION_ACTIVE)
{
	session_start();
}
?>

<header>
	<a href='index.php'><img src="images/logo.png" alt='logo' class='logo' /></a>
	<div class='menu'>
		<ul>
			<?php
				if (isset($_SESSION['login']))
				{
			?>		
				<li><?php echo $_SESSION['login'] ?></li>
				<li><a href='preferences.php'>Paramètres</a></li>
				<li><a href='login.php?action=logout'>Déconnexion</a></li>
				
			<?php
				}
				else
				{
			?>
				<li><a href='login.php'>Connexion</a></li>
			<?php
				}
			?>
				
		</ul>
	</div>
	
	<?php
	if (isset($_SESSION['login']))
	{
	?>
		<nav>
			<div><a href='index.php'>Activités</a></div>
			<div><a href='rapport.php'>Rapport</a></div>
			<div><a href=#>Calendrier</a></div>
		</nav>
	<?php } ?>
</header>