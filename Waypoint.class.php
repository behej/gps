<?php

/**
* Class:	Waypoint
* Role:		Constitue un point du tracé.
*			Gère également les calculs lié à ce point
*/
class Waypoint
{
//####################################
//			Attributs
//####################################
	private $lat;			// Latitude du point
	private $lon;			// Longitude du point
	private $ele;			// Altitude du point
	private $time;			// Horodatage du point
	private $time_prec;		// Temps écoulé par rapport au point précédent
	private $time_total;	// Temps total écoulé depuis le début de l'activité
	private $distPtPrec;	// Distance par rapport au point précédent (en m)
	private $distCumul;		// Distance cumulée depuis le début (en m)
	private $vitesseInst;	// Vitesse instantannée (en m/s)
	private $vitesseFiltree;		// Vitesse filtrée (en m/s)
	

//####################################
//			Méthodes
//####################################


	/**
	* Crée un nouveau point. Contient également les contrôles nécessaires
	* pour vérifier les données.
	*
	* $lat:		Latitude du point à créer
	* $lon:		Longitude du point à créer
	* $ele:		Altitude du point à créer
	* $time:	Horodatage du point à créer
	*/
	public function __construct($lat, $lon, $ele, $time)
	{
		$this->lat = (float) $lat;
		$this->lon = (float) $lon;
		$this->ele = (float) $ele;
		$this->time = (string) $time;
		$this->distPtPrec = 0.0;
		$this->distCumul = 0.0;
		$this->vitesseInst = 0.0;
		$this->vitesseFiltree = 0.0;
	}
	

	/**
	* Calcule le temps écoulé par rapport au point passé en paramètre. Calcule
	* également le temps total écoulé.
	*
	* $pt_prec:	Point précédent
	*/
	public function calcTime($pt_prec)
	{	
		date_default_timezone_set('UTC');
		
		$timestamp_cur = strtotime($this->time);
		$timestamp_prev = strtotime($pt_prec->getTime());
		$delta_timestamp = $timestamp_cur - $timestamp_prev;
		
		$this->time_prec = date("H:i:s" ,$delta_timestamp);
		$this->time_total = date("H:i:s", strtotime($pt_prec->getTimeTotal()) + $delta_timestamp);
	}

	
	/**
	* Calcule la distance du point par rapport au point précédent.
	*
	* $pt_prec:	Point précédent
	* return:	distance calculée entre ce point et le point précédent
	*/
	public function calcDist($pt_prec)
	{
		$earth_radius = 6378137;   // Terre = sphère de 6378km de rayon
	
		$rla1 = deg2rad($pt_prec->getLat());
		$rlo1 = deg2rad($pt_prec->getLon());
		$rla2 = deg2rad($this->lat);
		$rlo2 = deg2rad($this->lon);
		
		$dlo = ($rlo2 - $rlo1) / 2;
		$dla = ($rla2 - $rla1) / 2;
		$a = (sin($dla) * sin($dla)) + cos($rla1) * cos($rla2) * (sin($dlo) * sin($dlo));
		$d = 2 * atan2(sqrt($a), sqrt(1 - $a));
		$dist = $earth_radius * $d;
		
		$this->distPtPrec = $dist;
		$this->distCumul = $pt_prec->getDistCumul() + $dist;
		return $dist;
	}

	
	/**
	* Calcule la vitesse instantannée du point
	*/
	public function calcVit()
	{	
		// On soustrait le timestamp du jour actuel afin d'avoir la durée exacte
		$temps = strtotime($this->time_prec) - strtotime(date('Y-m-d'));
	
		$this->vitesseInst = $this->distPtPrec / $temps;
		return $this->vitesseInst;
	}
	
	
	
	/**
	* Calcule la vitesse filtrée du point
	*/
	public function calcVitFilt($pt_prec)
	{
		$deltaDist = $pt_prec->getDistPtPrec() + $this->distPtPrec;
		$deltaTemps = strtotime($pt_prec->getTimePrec()) + strtotime($this->time_prec) - 2*strtotime(date('Y-m-d'));
		
		$this->vitesseFiltree = $deltaDist/$deltaTemps;
		return $this->vitesseFiltree;
	}
	
	

	/**
	* Retourne l'horadatage du point
	*/
	public function getTime()
	{
		return $this->time;
	}
	

	/**
	* Retourne la distance entre ce point et le point précédent
	*/
	public function getDistPtPrec()
	{
		return $this->distPtPrec;
	}
	


	/**
	* Retourne le temps cumulé du point
	*/
	public function getTimeTotal()
	{
		return $this->time_total;
	}
	
	/**
	* Retourne la distance cumulée
	*/
	public function getDistCumul()
	{
		return $this->distCumul;
	}
	
	
	
	// Getters
	public function getLat()
	{
		return $this->lat;
	}

	public function getLon()
	{
		return $this->lon;
	}
	
	public function getEle()
	{
		return $this->ele;
	}
	

	public function getInstantSpeed()
	{
		return $this->vitesseInst;
	}
	
	
	public function setVitesseFiltree($speed)
	{
		$this->vitesseFiltree = $speed;
	}
	

	
	public function getFiltSpeed()
	{
		return $this->vitesseFiltree;
	}
	
	
	public function getTimePrec()
	{
		return $this->time_prec;
	}



}