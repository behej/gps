<?php

include_once('Waypoint.class.php');
include_once('settings.php');

class Track
{
	private $titre;		// Titre de l'activité
	private $commentaire;	// Commentaire de l'activité
	private $nb_pts;	// Nombre de points qui composent le tracé
	private $tab;		// Tableau qui contient tous les points du tracé
	private $dateTimeDebut;		// Horodatage du début de l'activité
	private $duree;				// Durée de l'activité
	private	$distanceTotale;	// Distance de l'activité (en m)
	private $vitesseMoy;		// Vitesse moyenne de l'activité (en m/s)
	private $vitesseMax;		// Vitesse maximale (en m/s)
	private $FCmoy;				// Fréquence cardiaque moyenne
	private $FCmax;				// Fréquence cardiage maximale
	private $typeAct;			// Type d'activité	
	private $denivelePos;		// Dénivelé cumulé positif
	private $deniveleNeg;		// Dénivelé cumulé négatif
	private $altitudeMin;		// Altitude minimale
	private $altitudeMax;		// Altitude maximale

	
	
	
	// Constructeur
	public function __construct($nom, $comment, $FCmoy, $FCmax, $type)
	{	
		$this->titre = (string)htmlspecialchars($nom);
		$this->commentaire = (string)htmlspecialchars($comment);
		$this->nb_pts = 0;
		$this->dateTimeDebut = "1970-01-01 00:00:00";
		$this->duree = "00:00:00";
		$this->distanceTotale = 0;
		$this->vitesseMoy = 0.0;
		$this->vitesseMax = 0.0;
		$this->FCmoy = (integer)$FCmoy;
		$this->FCmax = (integer)$FCmax;
		$this->typeAct = $type;		
		$this->denivelePos = 0.0;
		$this->deniveleNeg = 0.0;
		$this->altitudeMin = 0.0;
		$this->altitudeMax = 0.0;
	}
	
	
	// Destructeur
	public function __destruct()
	{
		foreach($this->tab as $item)
		{
			unset($item);
		}
	}
	
	/**
	* Ajoute un point au tracé
	*
	* lat: Latitude
	* lon: Longitude
	* ele: Altitude
	* time: horodatage du point
	*/
	public function addPoint($lat, $lon, $ele, $time)
	{
		global $serverTimeZone;
		$time = preg_replace(array('/T/', '/Z/'), array(' ',''), $time);
		$time = (string) htmlspecialchars($time);
		date_default_timezone_set('UTC');
		$timestamp = strtotime($time);
		date_default_timezone_set($serverTimeZone);
		$time = date("Y-m-d H:i:s", $timestamp);
		
		$lat = (float) htmlspecialchars($lat);
		$lon = (float) htmlspecialchars($lon);
		$ele = (float) htmlspecialchars($ele);
		
		$this->tab[] = new Waypoint($lat, $lon, $ele, $time);		
		$this->nb_pts++;
	}
	

	public function getNbPts()
	{
		return $this->nb_pts;
	}
	
	
	/**
	* Calcule le temps entre tous les points du tracé.
	* Récupère la date et l'heure de début ainsi que la durée totale
	*/
	public function calculeTemps()
	{
		// Positionne la date et l'heure de début de l'activité
		$this->dateTimeDebut = $this->tab[0]->getTime();
	
		// Pour chaque point, calcul les temps écoulé et total
		for ($i=1 ; $i < $this->nb_pts ; $i++)
		{
			$this->tab[$i]->calcTime($this->tab[$i-1]);		
		}
		
		// Récupère la durée totale de l'activité grace au temps total du dernier point
		$this->duree = $this->tab[$this->nb_pts - 1]->getTimeTotal();
	}
	
	
	/**
	* Calcule la distance de chaque point le séparant du point précédent
	* Calcule également la distance totale de l'activité
	*/
	public function calculeDistance()
	{
		$distTotal = 0;
		
		// Pour chaque point, calcul la distance
		for ($i=1 ; $i < $this->nb_pts ; $i++)
		{
			$distTotal += $this->tab[$i]->calcDist($this->tab[$i-1]);
		}

		// Récupération de la distance cumulée
		$this->distanceTotale = $distTotal;
	}
	
	
	/**
	* Calcule la vitesse instantannée de chaque point et la vitesse moyenne
	*/
	public function calculeVitesse()
	{	
		// Pour chaque point, calcul la vitesse instantannée
		for ($i=1 ; $i < $this->nb_pts ; $i++)
		{
			// Calcul de la vitesse instantannée
			$this->tab[$i]->calcVit();
		}
	
		// calcule la vitesse moyenne
		$temps = strtotime($this->duree) - strtotime(date('Y-m-d'));
		$this->vitesseMoy = $this->distanceTotale / $temps;
	}
	
	
	
	/**
	* Calcule la vitesse filtrée de chaque point
	* 2e méthode: calcul de la vitesse sur 2 points
	*/
	public function calculeVitesseFiltree()
	{	
		$Vmax = 0.0;
	
		if ($this->nb_pts <= 2)
			return;
	
		// Pour chaque point, calcul la vitesse instantannée
		for ($i=2 ; $i < $this->nb_pts ; $i++)
		{
			$V = $this->tab[$i]->calcVitFilt($this->tab[$i-1]);
			if ($V > $Vmax)
				$Vmax = $V;
		}
	
		// Cas particulier des 2 premiers points
		$this->tab[0]->setVitesseFiltree($this->tab[2]->getFiltSpeed());
		$this->tab[1]->setVitesseFiltree($this->tab[2]->getFiltSpeed());
		
		// Vitesse max
		$this->vitesseMax = $Vmax;
	}
	
	
	/**
	* Calcul les dénivelés cumulés positifs et négatifs et détermine également
	* les altitudes min et max
	*
	* le dénivelé cumulé est basé non pas sur l'altitude mesurée du point mais sur la moyenne
	* des altitudes de 3 points consécutifs.
	*/
	public function calculeDenivele()
	{
		$alt_min = 0;
		$alt_max = 0;
		$cumul_pos = 0;
		$cumul_neg = 0;
		$premier_point = true;
		
		
		for ($i=2 ; $i < $this->nb_pts-1 ; $i++)
		{
			// Calcul de l'altitude moyenne du point précédent, du point courant et du point suivant
			$alt1 = $this->tab[$i-1]->getEle();
			$alt2 = $this->tab[$i]->getEle();
			$alt3 = $this->tab[$i+1]->getEle();
			$alt_moy = ($alt1 + $alt2 + $alt3) / 3;
			
			// Sauf pour le premier point, calcul du delta d'altitude
			if (!$premier_point)
			{
				$delta = $alt_moy - $alt_moy_prec;
				if ($delta < 0)
					$cumul_neg -= $delta;
				elseif ($delta > 0)
					$cumul_pos += $delta;
			}
			
			
			// Recherche des altitudes min et max
			if ($premier_point) {
				$alt_min = $alt_moy;
				$alt_max = $alt_moy;
			}
			else {
				$alt_min = ($alt_moy < $alt_min) ? $alt_moy : $alt_min;
				$alt_max = ($alt_moy > $alt_max) ? $alt_moy : $alt_max;
			}
			
			// Mémorisation de l'altitude pour le prochain point
			$alt_moy_prec = $alt_moy;
			$premier_point = false;
		}
	
	// Mémorisation dans les attributs de l'objet
	$this->denivelePos = $cumul_pos;
	$this->deniveleNeg = $cumul_neg;
	$this->altitudeMin = $alt_min;
	$this->altitudeMax = $alt_max;	
	}
	
	
	/**
	* Crée le nom de fichier sous lequel sera sauvegardé le fichier gpx
	*
	* le nom est de la forme: YYYYMMDD_HHMMSS_activity_id.gpx
	*/
	public function createFileName($id)
	{
		return date('Ymd_His', strtotime($this->dateTimeDebut)) . '_activity_' . $id . '.gpx';	
	}
	
	
	
	/**
	* Retourne les propriétés du tracé sous forme de tableau
	*/
	public function getValuesArray()
	{
		return array($this->titre,
					$this->commentaire,
					$this->dateTimeDebut,
					$this->duree,
					round($this->distanceTotale/1000,1),
					round($this->vitesseMoy*3.6, 1),
					round($this->vitesseMax*3.6, 1),
					$this->denivelePos,
					$this->deniveleNeg,
					$this->altitudeMin,
					$this->altitudeMax,
					$this->FCmoy,
					$this->FCmax,
					$this->typeAct);
	}
	
	
	public function getPoint($indice)
	{
		if ($indice >= $this->nb_pts)
			return null;
			
		$time = $this->tab[$indice]->getTime();
		$distPrev = $this->tab[$indice]->getDistPtPrec();
		$distTotal = $this->tab[$indice]->getDistCumul();
		$lat = $this->tab[$indice]->getLat();
		$lon = $this->tab[$indice]->getLon();
		$ele = $this->tab[$indice]->getEle();
		$speedInstant = $this->tab[$indice]->getInstantSpeed();
		$speedFilt = $this->tab[$indice]->getFiltSpeed();
		$timePrev = $this->tab[$indice]->getTimePrec();
		$timeTotal = $this->tab[$indice]->getTimeTotal();
	
		return array($time, $distPrev, $distTotal, $lat, $lon, $ele, $speedInstant, $speedFilt, $timePrev, $timeTotal);
	}



}