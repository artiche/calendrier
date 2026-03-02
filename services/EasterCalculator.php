<?php

/**
 * Classe pour calculer la date de Pâques et les jours fériés français
 */
class EasterCalculator
{
    /**
     * Calcule la date de Pâques pour une année donnée
     * Utilise l'algorithme de Meeus/Jones/Butcher
     * Valide de 1583 à 4099
     *
     * @param int $year L'année pour laquelle calculer Pâques
     * @return DateTime La date de Pâques (dimanche)
     */
    public static function calculateEaster($year)
    {
        // Vérification de la plage valide
        if ($year < 1583 || $year > 4099) {
            throw new InvalidArgumentException("L'année doit être entre 1583 et 4099");
        }

        // Algorithme de Meeus/Jones/Butcher
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        // Créer la date (month: 3=mars, 4=avril)
        $easterMonth = ($month === 3) ? 3 : 4;
        
        return new DateTime("{$year}-{$easterMonth}-{$day}");
    }

    /**
     * Calcule le Baptême du Seigneur (2e dimanche de l'année, dimanche après l'Épiphanie)
     *
     * @param int $year L'année
     * @return DateTime La date du Baptême du Seigneur
     */
    public static function getBaptismOfLord($year)
    {
        // L'Épiphanie est le premier dimanche après le 1er janvier
        $date = new DateTime("{$year}-01-02");
        
        // Trouver le premier dimanche après le 1er janvier (l'Épiphanie)
        while ($date->format('w') !== '0') {
            $date->modify('+1 day');
        }
        
        // Ajouter 7 jours pour obtenir le dimanche suivant (2e dimanche de l'année)
        $date->modify('+7 days');
        
        return $date;
    }

    /**
     * Calcule la date de l'Épiphanie (1er dimanche après le 1er janvier)
     *
     * @param int $year L'année
     * @return DateTime La date de l'Épiphanie
     */
    public static function getEpiphany($year)
    {
        $date = new DateTime("{$year}-01-02");
        
        // Trouver le premier dimanche après le 1er janvier
        while ($date->format('w') !== '0') {
            $date->modify('+1 day');
        }
        
        return $date;
    }

    /**
     * Calcule l'Annonciation (25 mars, ou reportée si pendant la Semaine Sainte)
     * Si le 25 mars tombe pendant la Semaine Sainte (du dimanche de Rameaux au dimanche de Pâques),
     * l'Annonciation est reportée au lundi suivant Pâques
     *
     * @param int $year L'année
     * @return DateTime La date de l'Annonciation
     */
    public static function getAnnunciation($year)
    {
        $annunciation = new DateTime("{$year}-03-25");
        $easter = self::calculateEaster($year);
        $palmSunday = self::getPalmSunday($year);
        
        // Vérifier si le 25 mars tombe entre Rameaux et Pâques (inclus)
        if ($annunciation >= $palmSunday && $annunciation <= $easter) {
            // Reporter au lundi suivant Pâques
            $annunciation = clone $easter;
            $annunciation->modify('+1 day');
        }
        
        return $annunciation;
    }

    /**
     * Calcule le Lundi de Pâques
     *
     * @param int $year L'année
     * @return DateTime Le Lundi de Pâques
     */
    public static function getEasterMonday($year)
    {
        $easter = self::calculateEaster($year);
        $easterMonday = clone $easter;
        $easterMonday->modify('+1 day');
        return $easterMonday;
    }

    /**
     * Calcule l'Ascension (39 jours après Pâques)
     *
     * @param int $year L'année
     * @return DateTime La date de l'Ascension
     */
    public static function getAscension($year)
    {
        $easter = self::calculateEaster($year);
        $ascension = clone $easter;
        $ascension->modify('+39 days');
        return $ascension;
    }

    /**
     * Calcule le Mardi Gras (47 jours avant Pâques)
     *
     * @param int $year L'année
     * @return DateTime La date du Mardi Gras
     */
    public static function getFatTuesday($year)
    {
        $easter = self::calculateEaster($year);
        $fatTuesday = clone $easter;
        $fatTuesday->modify('-47 days');
        return $fatTuesday;
    }

    /**
     * Calcule le Mercredi des Cendres (46 jours avant Pâques)
     *
     * @param int $year L'année
     * @return DateTime La date du Mercredi des Cendres
     */
    public static function getAshWednesday($year)
    {
        $easter = self::calculateEaster($year);
        $ashWednesday = clone $easter;
        $ashWednesday->modify('-46 days');
        return $ashWednesday;
    }

    /**
     * Calcule le Dimanche de Carême (42 jours avant Pâques)
     *
     * @param int $year L'année
     * @return DateTime La date du Dimanche de Carême
     */
    public static function getLentSunday($year)
    {
        $easter = self::calculateEaster($year);
        $lentSunday = clone $easter;
        $lentSunday->modify('-42 days');
        return $lentSunday;
    }

    /**
     * Calcule la Mi-Carême (3e dimanche avant Pâques, 24 jours avant Pâques)
     *
     * @param int $year L'année
     * @return DateTime La date de la Mi-Carême
     */
    public static function getMidLent($year)
    {
        $easter = self::calculateEaster($year);
        $midLent = clone $easter;
        $midLent->modify('-24 days');
        return $midLent;
    }

    /**
     * Calcule le Souvenir des Déportés (dernier dimanche d'avril)
     *
     * @param int $year L'année
     * @return DateTime La date du Souvenir des Déportés
     */
    public static function getDeportationMemorial($year)
    {
        // Créer une date au 30 avril
        $date = new DateTime("{$year}-04-30");
        
        // Reculer jusqu'au dimanche précédent si nécessaire
        while ($date->format('w') !== '0') {
            $date->modify('-1 day');
        }
        
        return $date;
    }

    /**
     * Calcule la Fête de Jeanne d'Arc (2e dimanche de mai)
     *
     * @param int $year L'année
     * @return DateTime La date de la Fête de Jeanne d'Arc
     */
    public static function getJoanOfArcDay($year)
    {
        // Créer une date au 1er mai
        $date = new DateTime("{$year}-05-01");
        
        // Trouver le premier dimanche à partir du 1er mai
        while ($date->format('w') !== '0') {
            $date->modify('+1 day');
        }
        
        // Ajouter 7 jours pour obtenir le 2e dimanche de mai
        $date->modify('+7 days');
        
        return $date;
    }

    /**
     * Calcule le Dimanche des Rameaux (8 jours avant Pâques)
     *
     * @param int $year L'année
     * @return DateTime La date des Rameaux
     */
    public static function getPalmSunday($year)
    {
        $easter = self::calculateEaster($year);
        $palmSunday = clone $easter;
        $palmSunday->modify('-8 days');
        return $palmSunday;
    }

    /**
     * Calcule le Vendredi Saint (2 jours avant Pâques)
     *
     * @param int $year L'année
     * @return DateTime La date du Vendredi Saint
     */
    public static function getGoodFriday($year)
    {
        $easter = self::calculateEaster($year);
        $goodFriday = clone $easter;
        $goodFriday->modify('-2 days');
        return $goodFriday;
    }

    /**
     * Calcule la Pentecôte (49 jours après Pâques)
     *
     * @param int $year L'année
     * @return DateTime La date de la Pentecôte
     */
    public static function getPentecost($year)
    {
        $easter = self::calculateEaster($year);
        $pentecost = clone $easter;
        $pentecost->modify('+49 days');
        return $pentecost;
    }

    /**
     * Calcule la Fête des Mères (dernier dimanche de mai)
     *
     * @param int $year L'année
     * @return DateTime La date de la Fête des Mères
     */
    public static function getMothersDay($year)
    {
        // Créer une date au 31 mai
        $date = new DateTime("{$year}-05-31");
        
        // Reculer jusqu'au dimanche précédent si nécessaire
        while ($date->format('w') !== '0') {
            $date->modify('-1 day');
        }
        
        return $date;
    }

    /**
     * Calcule la Fête des Pères (3e dimanche de juin)
     *
     * @param int $year L'année
     * @return DateTime La date de la Fête des Pères
     */
    public static function getFathersDay($year)
    {
        // Créer une date au 1er juin
        $date = new DateTime("{$year}-06-01");
        
        // Trouver le premier dimanche à partir du 1er juin
        while ($date->format('w') !== '0') {
            $date->modify('+1 day');
        }
        
        // Ajouter 14 jours pour obtenir le 3e dimanche de juin
        $date->modify('+14 days');
        
        return $date;
    }

    /**
     * Calcule l'Avent (4e dimanche avant Noël)
     *
     * @param int $year L'année
     * @return DateTime La date de l'Avent
     */
    public static function getAdvent($year)
    {
        // Créer une date pour le 25 décembre (Noël)
        $christmas = new DateTime("{$year}-12-25");
        
        // Reculer jusqu'au dimanche précédent si nécessaire
        $advent = clone $christmas;
        while ($advent->format('w') !== '0') {
            $advent->modify('-1 day');
        }
        
        // Reculer de 21 jours supplémentaires pour obtenir le 4e dimanche avant Noël
        $advent->modify('-21 days');
        
        return $advent;
    }

    /**
     * Calcule la Sainte Famille (dimanche suivant Noël, ou 30 décembre si Noël est un dimanche)
     *
     * @param int $year L'année
     * @return DateTime La date de la Sainte Famille
     */
    public static function getHolyFamily($year)
    {
        // Créer une date pour le 25 décembre (Noël)
        $christmas = new DateTime("{$year}-12-25");
        
        // Si Noël est un dimanche, la Sainte Famille est le 30 décembre
        if ($christmas->format('w') === '0') {
            return new DateTime("{$year}-12-30");
        }
        
        // Sinon, la Sainte Famille est le dimanche suivant Noël
        $holyFamily = clone $christmas;
        $holyFamily->modify('+1 day');
        while ($holyFamily->format('w') !== '0') {
            $holyFamily->modify('+1 day');
        }
        
        return $holyFamily;
    }

    /**
     * Calcule la Trinité (56 jours après Pâques)
     *
     * @param int $year L'année
     * @return DateTime La date de la Trinité
     */
    public static function getTrinity($year)
    {
        $easter = self::calculateEaster($year);
        $trinity = clone $easter;
        $trinity->modify('+56 days');
        return $trinity;
    }

    /**
     * Calcule la Fête-Dieu (dimanche suivant 60 jours après Pâques)
     * En France, la Fête-Dieu est toujours célébrée un dimanche
     *
     * @param int $year L'année
     * @return DateTime La date de la Fête-Dieu
     */
    public static function getCorpusChristi($year)
    {
        $easter = self::calculateEaster($year);
        $corpusChristi = clone $easter;
        $corpusChristi->modify('+60 days');
        
        // Avancer jusqu'au dimanche suivant si ce n'est pas déjà un dimanche
        while ($corpusChristi->format('w') !== '0') {
            $corpusChristi->modify('+1 day');
        }
        
        return $corpusChristi;
    }

    /**
     * Calcule le Sacré-Cœur (68 jours après Pâques)
     *
     * @param int $year L'année
     * @return DateTime La date du Sacré-Cœur
     */
    public static function getSacredHeart($year)
    {
        $easter = self::calculateEaster($year);
        $sacredHeart = clone $easter;
        $sacredHeart->modify('+68 days');
        return $sacredHeart;
    }

    /**
     * Retourne les fêtes religieuses non fériées pour une année donnée
     *
     * @param int $year L'année
     * @return array Tableau associatif des fêtes religieuses non fériées
     */
    public static function getReligiousFestivals($year)
    {
        $holidays = self::getFrenchHolidays($year);
        $holidayDates = [];
        foreach ($holidays as $holidayDate) {
            $holidayDates[] = $holidayDate->format('Y-m-d');
        }
        
        $festivals = [
            'Épiphanie' => self::getEpiphany($year),
            'Bapt. du Seigneur' => self::getBaptismOfLord($year),
            'Annonciation' => self::getAnnunciation($year),
            'Mardi Gras' => self::getFatTuesday($year),
            'Cendres' => self::getAshWednesday($year),
            'Carême' => self::getLentSunday($year),
            'Mi-Carême' => self::getMidLent($year),
            'Rameaux' => self::getPalmSunday($year),
            'Pâques' => self::calculateEaster($year),
            'Vendredi Saint' => self::getGoodFriday($year),
            'Souv. des Déportés' => self::getDeportationMemorial($year),
            'F. Jeanne d\'Arc' => self::getJoanOfArcDay($year),
            'Pentecôte' => self::getPentecost($year),
            'F. des Mères' => self::getMothersDay($year),
            'F. des Pères' => self::getFathersDay($year),
            'Trinité' => self::getTrinity($year),
            'Fête-Dieu' => self::getCorpusChristi($year),
            'Sacré-Cœur' => self::getSacredHeart($year),
            'Avent' => self::getAdvent($year),
            'Sainte Famille' => self::getHolyFamily($year),
        ];

        // Filtrer les festivals qui coincident avec des jours fériés
        $result = [];
        foreach ($festivals as $name => $date) {
            if (!in_array($date->format('Y-m-d'), $holidayDates)) {
                $result[$name] = $date;
            }
        }
        
        return $result;
    }

    /**
     * Calcule la date de l'Épiphanie (1er dimanche après le 1er janvier)
     *
     * @param int $year L'année
     * @return DateTime Le Lundi de Pentecôte
     */
    public static function getWhitMonday($year)
    {
        $easter = self::calculateEaster($year);
        $whitMonday = clone $easter;
        $whitMonday->modify('+50 days');
        return $whitMonday;
    }

    /**
     * Retourne tous les jours fériés français pour une année donnée
     *
     * @param int $year L'année
     * @return array Tableau associatif des jours fériés avec leurs noms et dates
     */
    public static function getFrenchHolidays($year)
    {
        $holidays = [
            // Jours fériés fixes
            'Jour de l\'An' => new DateTime("{$year}-01-01"),
            'Fête du Travail' => new DateTime("{$year}-05-01"),
            'Victoire 1945' => new DateTime("{$year}-05-08"),
            'Fête Nationale' => new DateTime("{$year}-07-14"),
            'Assomption' => new DateTime("{$year}-08-15"),
            'Armistice 1918' => new DateTime("{$year}-11-11"),
            'Noël' => new DateTime("{$year}-12-25"),
            
            // Jours fériés mouvants (dépendent de Pâques)
            'Lundi de Pâques' => self::getEasterMonday($year),
            'Ascension' => self::getAscension($year),
            'Lundi de Pentecôte' => self::getWhitMonday($year),
        ];

        // Trier les jours fériés par date tout en gardant les associations clé => valeur
        uasort($holidays, function($a, $b) {
            return $a <=> $b;
        });

        return $holidays;
    }

    /**
     * Retourne les jours fériés français formatés pour une année donnée
     *
     * @param int $year L'année
     * @param string $format Format de la date (défaut: 'd/m/Y')
     * @return array Tableau avec nom du jour férié et date formatée
     */
    public static function getFormattedHolidays($year, $format = 'd/m/Y')
    {
        $holidays = self::getFrenchHolidays($year);
        $formatted = [];

        foreach ($holidays as $name => $date) {
            $formatted[$name] = $date->format($format);
        }

        return $formatted;
    }

    /**
     * Vérifie si une date donnée est un jour férié français
     *
     * @param DateTime|string $date La date à vérifier
     * @param int|null $year L'année (optionnelle si DateTime fourni)
     * @return array|false Tableau avec le nom du jour férié ou false si ce n'est pas un jour férié
     */
    public static function isHoliday($date, $year = null)
    {
        if (is_string($date)) {
            $date = new DateTime($date);
        }

        if ($year === null) {
            $year = (int)$date->format('Y');
        }

        $holidays = self::getFrenchHolidays($year);

        foreach ($holidays as $name => $holidayDate) {
            if ($date->format('Y-m-d') === $holidayDate->format('Y-m-d')) {
                return [
                    'name' => $name,
                    'date' => $holidayDate,
                    'formatted' => $holidayDate->format('d/m/Y')
                ];
            }
        }

        return false;
    }
}

?>
