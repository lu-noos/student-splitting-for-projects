<?php
/**
 * Segment PHP à inclure
 * Insère les entrées dans des objets etudiant
 * Calcule les stats à partir de ces étudiants et détermine le nombre de groupes & de membres par groupe
 * Prédéfinit des préfixes de groupe à partir des étudiant déjà associés (insérés manuellement dans le code)
 * A partir d'une moyenne et d'une tolérance d'écart, essaie d'insérer tout ce monde là dans le nombre de groupes défini au préalable
 * Si les conditions sont respectées, affiche tout
 */

/**
 *  CLASSE ETUDIANT
 */
class etudiant {
    private $nom;
    private $prenom;
    private $niveau;

    public function __construct(string $n, string $p, int $lv)
    {
        $this->nom = $n;
        $this->prenom = $p;
        $this->niveau = $lv;
    }
    public function getNom() {
        return $this->nom;
    }
    public function getPrenom() {
        return $this->prenom;
    }
    public function getNiveau() {
        return $this->niveau;
    }
}

/**
 *  Requête & remplissage de l'array
 */
try {
    $bddpersonnes = new PDO("mysql:host=localhost; dbname=DBNAME", "USERNAME", "PASSWORD", array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
    } catch (Exception $e) {
        die("Erreur : " . $e->getMessage());
    }
$reponse1 = $bddpersonnes->query(
        'SELECT nom, prenom, niveauDev,
        DATE_FORMAT(dateCrea, "%d/%m à %hh%i") AS date_format
        FROM eleves_ENI
        ORDER BY niveauDev DESC'
);

$listeEleves = array();
while ($donnees_eleves = $reponse1->fetch()) {
    array_push($listeEleves, new etudiant(strtoupper($donnees_eleves["nom"]), $donnees_eleves["prenom"], $donnees_eleves["niveauDev"]));
}
    
/**
 *  Calculs
 */
$somme = 0;
foreach($listeEleves as $element) {
    $somme += $element->getNiveau();
}

//echo('<p>Total niveaux : ' . $somme . '</p>');
$nbEleves = count($listeEleves);
$avg = round(($somme / $nbEleves),2);
echo('<p>Nombre d\'inscrits : ' . $nbEleves . '</p>');
echo('<p>Niveau moyen : ' . $avg . '</p>');
$maxParGroupe = 3;
$nbMembresExtra = $nbEleves % $maxParGroupe;
$nbGroupes = ($nbEleves - $nbMembresExtra) / $maxParGroupe;
    
// echo('<p>Nombre de groupes : ' . $nbGroupes . '</p>');
// echo('<p>Eleves extra à placer : ' . $nbMembresExtra . '</p>');

/**
 * Insertion d'élèves dans des arrays représentant les groupes
 */

$ecartautorise = 0.4;
$equilibre = false;

$listeCopie = $listeEleves;

$groupeAxelJB = array();
foreach($listeCopie as $n => $etudiantcopie) {
    if($etudiantcopie->getNom() == "DIAGNE" || ($etudiantcopie->getNom()) == "COCHINARD") {
        array_push($groupeAxelJB, $etudiantcopie);
        unset($listeCopie[$n]);
    }
}

$groupeMathisCedric = array();
foreach($listeCopie as $n => $etudiantcopie) {
    if(($etudiantcopie->getNom()) == "MOTAIS" || ($etudiantcopie->getNom()) == "ADISSON") {
        array_push($groupeMathisCedric, $etudiantcopie);
        unset($listeCopie[$n]);
    }
}

$groupeQuentinMaxime = array();
foreach($listeCopie as $n => $etudiantcopie) {
    if(($etudiantcopie->getNom()) == "PINCHART" || ($etudiantcopie->getNom()) == "LOISEAU") {
        array_push($groupeQuentinMaxime, $etudiantcopie);
        unset($listeCopie[$n]);
    }
}

$listeCopie2 = array();
foreach($listeCopie as $etudiantOUT) {
    array_push($listeCopie2, $etudiantOUT);
}

while ($equilibre == false) {
    $l=$nbMembresExtra;
    $k=0;
    $moyennesGroupes = array();
    $groupesFormes = array();

    //itération sur les différents groupes
    for($i=1;$i<$nbGroupes+1;$i++) {
        $scoreGrp=0;
        $groupe = array();

        //itération dans un groupe
        for($j=0;$j<$maxParGroupe;$j++) {

            //insertion des préfixes de groupes prédéfinis
            if ($i==1) {
                $scoreGrp += $groupeAxelJB[0]->getNiveau();
                array_push($groupe, $groupeAxelJB[0]);
                $j++;
                $scoreGrp += $groupeAxelJB[1]->getNiveau();
                array_push($groupe, $groupeAxelJB[1]);
                $j++;
            }
            if ($i==2) {
                $scoreGrp += $groupeMathisCedric[0]->getNiveau();
                array_push($groupe, $groupeMathisCedric[0]);
                $j++;
                $scoreGrp += $groupeMathisCedric[1]->getNiveau();
                array_push($groupe, $groupeMathisCedric[1]);
                $j++;
            }
            if ($i==3) {
                $scoreGrp += $groupeQuentinMaxime[0]->getNiveau();
                array_push($groupe, $groupeQuentinMaxime[0]);
                $j++;
                $scoreGrp += $groupeQuentinMaxime[1]->getNiveau();
                array_push($groupe, $groupeQuentinMaxime[1]);
                $j++;
            }
            //fin des préfixes

            // echo('<p>ligne ' . $k .'</p>');
            $scoreGrp += $listeCopie2[$k]->getNiveau();
            array_push($groupe, $listeCopie2[$k]);
            $k++;
        }
        if($l>0) {
            $scoreGrp += $listeCopie2[$k]->getNiveau();
            array_push($groupe, $listeCopie2[$k]);
            $k++;
            $l--;
            $j++;
        }
        $nbElevesGroupe = count($groupe);
        $moyenneGroupe = round(($scoreGrp/$nbElevesGroupe), 2);
        // $moyenneGroupe = 1.5;

        shuffle($groupe);
        array_push($groupesFormes, $groupe);
        array_push($moyennesGroupes, $moyenneGroupe);
    }
    //vérifier si on est dans l'écart autorisé par rapport à la moyenne
    $equilibre = true;
    foreach($moyennesGroupes as $moyenneEvaluee) {
        if(($moyenneEvaluee < ($avg-$ecartautorise)) || ($moyenneEvaluee > ($avg+$ecartautorise))) {
            $equilibre = false;
            shuffle($listeCopie2);
            break;
        }
    }
}

/**
 * Affichage
 */

$i = 0;
foreach($groupesFormes as $grpSelect) {
    $i++;
    $totGr=0;
    echo('<p><strong>Groupe ' . $i . '</strong></p>');
    foreach($grpSelect as $eleveSelect) {
        echo ('<p>' . $eleveSelect->getNom() . ' ' . $eleveSelect->getPrenom() . '</p>');
        $totGr += $eleveSelect->getNiveau();
        $nbElevesGroupe = count($grpSelect);
    }
    echo ('<p><em>Niveau moyen de ce groupe : ' . $moyennesGroupes[$i-1] . '</em></p>');
}

?>