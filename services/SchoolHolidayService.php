<?php

/**
 * Service d'accès à l'API des vacances scolaires
 * Source : https://data.education.gouv.fr/explore/dataset/fr-en-calendrier-scolaire/
 */
class SchoolHolidayService
{
    private const API_BASE_URL = 'https://data.education.gouv.fr/api/explore/v2.1/catalog/datasets/fr-en-calendrier-scolaire/records';
    private const CACHE_DIR = __DIR__ . '/../cache';
    private const CACHE_DURATION = 86400; // 24 heures

    /**
     * Récupère les vacances scolaires pour une année et une zone
     *
     * @param int $year L'année (ex: 2026)
     * @param string $zone La zone ("Zone A", "Zone B", "Zone C", ou null pour toutes)
     * @return array Tableau des vacances scolaires
     */
    public static function getHolidaysByYear($year, $zone = null)
    {
        // Construire la requête WHERE
        $whereConditions = [];
        
        // Filtrer par années scolaires
        $schoolYears = self::getSchoolYears($year);
        $yearList = implode("', '", $schoolYears);
        $whereConditions[] = "annee_scolaire IN ('{$yearList}')";
        
        // Ajouter le filtre de zone optionnel
        if ($zone) {
            // Normaliser la zone au format "Zone X"
            $zone = trim(strtoupper($zone));
            if (!strpos($zone, 'ZONE')) {
                $zone = 'Zone ' . $zone;
            }
            $whereConditions[] = "zones IN ('{$zone}')";
        }
        
        $where = implode(' AND ', $whereConditions);
        
        $holidays = self::callAPI(
            ['description', 'start_date', 'end_date'],
            $where,
            ['description', 'start_date', 'end_date']
        );
        
        // Filtrer et recadrer les vacances pour l'année demandée
        $filtered = [];
        $yearStart = new DateTime("{$year}-01-01");
        $yearEnd = new DateTime("{$year}-12-31");
        $parisTimezone = new DateTimeZone('Europe/Paris');
        
        foreach ($holidays as $holiday) {
            // Convertir les dates UTC en timezone de Paris puis extraire uniquement la date (Y-m-d)
            $startDateUtc = new DateTime($holiday['start_date'], new DateTimeZone('UTC'));
            $startDateUtc->setTimezone($parisTimezone);
            $endDateUtc = new DateTime($holiday['end_date'], new DateTimeZone('UTC'));
            $endDateUtc->setTimezone($parisTimezone);
            
            // Créer des dates "pures" sans timezone pour la comparaison
            $startDate = new DateTime($startDateUtc->format('Y-m-d'));
            $endDate = new DateTime($endDateUtc->format('Y-m-d'));
            
            // Ignorer les entrées invalides où start_date >= end_date
            if ($startDate >= $endDate) {
                continue;
            }
            
            // Vérifier si la période de vacances chevauche l'année demandée
            if ($endDate >= $yearStart && $startDate <= $yearEnd) {
                // Recadrer les dates aux limites de l'année
                if ($startDate < $yearStart) {
                    $startDate = clone $yearStart;
                }
                if ($endDate > $yearEnd) {
                    $endDate = clone $yearEnd;
                }
                
                // Stocker les dates en format simple Y-m-d
                $holiday['start_date'] = $startDate->format('Y-m-d');
                $holiday['end_date'] = $endDate->format('Y-m-d');
                
                $filtered[] = $holiday;
            }
        }
        
        // Éliminer les doublons : si deux vacances ont le même nom et que l'une est incluse dans l'autre, garder la plus large
        $filtered = self::deduplicateHolidays($filtered);
        
        return $filtered;
    }

    /**
     * Élimine les doublons de vacances : si deux vacances ont le même nom et que l'une est incluse dans l'autre,
     * garde la plus large
     *
     * @param array $holidays Les vacances
     * @return array Les vacances dédupliquées
     */
    private static function deduplicateHolidays($holidays)
    {
        $result = [];
        $toRemove = [];
        
        for ($i = 0; $i < count($holidays); $i++) {
            if (isset($toRemove[$i])) {
                continue;
            }
            
            $holiday1 = $holidays[$i];
            $start1 = new DateTime($holiday1['start_date']);
            $end1 = new DateTime($holiday1['end_date']);
            
            for ($j = $i + 1; $j < count($holidays); $j++) {
                if (isset($toRemove[$j])) {
                    continue;
                }
                
                $holiday2 = $holidays[$j];
                
                // Si même nom de vacances
                if ($holiday1['description'] === $holiday2['description']) {
                    $start2 = new DateTime($holiday2['start_date']);
                    $end2 = new DateTime($holiday2['end_date']);
                    
                    // Vérifier si l'une est incluse dans l'autre
                    if ($start2 >= $start1 && $end2 <= $end1) {
                        // holiday2 est inclus dans holiday1, éliminer holiday2
                        $toRemove[$j] = true;
                    } else if ($start1 >= $start2 && $end1 <= $end2) {
                        // holiday1 est inclus dans holiday2, éliminer holiday1
                        $toRemove[$i] = true;
                        break;
                    }
                }
            }
            
            if (!isset($toRemove[$i])) {
                $result[] = $holiday1;
            }
        }
        
        return $result;
    }

    /**
     * Récupère les vacances scolaires pour une date spécifique
     *
     * @param DateTime|string $date La date à vérifier
     * @param string|null $zone La zone (optionnel)
     * @return array|null Les informations de vacances ou null
     */
    public static function getHolidayByDate($date, $zone = null)
    {
        if (is_string($date)) {
            $date = new DateTime($date);
        }
        
        $year = (int)$date->format('Y');
        
        $holidays = self::getHolidaysByYear($year, $zone);
        
        foreach ($holidays as $holiday) {
            $startDate = new DateTime($holiday['start_date']);
            $endDate = new DateTime($holiday['end_date']);
            
            if ($date >= $startDate && $date <= $endDate) {
                return $holiday;
            }
        }
        
        return null;
    }

    /**
     * Récupère toutes les zones disponibles
     *
     * @param int $year L'année
     * @return array Liste des zones
     */
    public static function getZones($year = null)
    {
        if ($year === null) {
            $year = (int)date('Y');
        }
        
        $schoolYears = self::getSchoolYears($year);
        $whereConditions = [];
        $yearList = implode("', '", $schoolYears);
        $whereConditions[] = "annee_scolaire IN ('{$yearList}')";
        
        $where = implode(' AND ', $whereConditions);
        
        return self::callAPIWithGroupBy('zones', $where, 'zones');
    }

    /**
     * Récupère toutes les académies disponibles
     *
     * @param int $year L'année
     * @param string|null $zone La zone (optionnel)
     * @return array Liste des académies
     */
    public static function getLocations($year = null, $zone = null)
    {
        if ($year === null) {
            $year = (int)date('Y');
        }
        
        $schoolYears = self::getSchoolYears($year);
        $whereConditions = [];
        $yearList = implode("', '", $schoolYears);
        $whereConditions[] = "annee_scolaire IN ('{$yearList}')";
        
        if ($zone) {
            // Normaliser la zone au format "Zone X"
            $zone = trim(strtoupper($zone));
            if (!strpos($zone, 'ZONE')) {
                $zone = 'Zone ' . $zone;
            }
            $whereConditions[] = "zones = '{$zone}'";
        }
        
        $where = implode(' AND ', $whereConditions);
        
        return self::callAPIWithGroupBy('location', $where, 'location');
    }

    /**
     * Récupère tous les types de vacances
     *
     * @param int $year L'année
     * @return array Liste des descriptions
     */
    public static function getDescriptions($year = null)
    {
        if ($year === null) {
            $year = (int)date('Y');
        }
        
        $schoolYears = self::getSchoolYears($year);
        $whereConditions = [];
        $yearList = implode("', '", $schoolYears);
        $whereConditions[] = "annee_scolaire IN ('{$yearList}')";
        
        $where = implode(' AND ', $whereConditions);
        
        return self::callAPIWithGroupBy('description', $where, 'description');
    }

    /**
     * Retourne les années scolaires pour une année civile
     *
     * @param int $year L'année civile
     * @return array Les années scolaires correspondantes
     */
    private static function getSchoolYears($year)
    {
        return [
            ($year - 1) . '-' . $year,
            $year . '-' . ($year + 1)
        ];
    }

    /**
     * Appelle l'API avec les paramètres donnés
     *
     * @param array|string $select Champs à sélectionner (string ou array)
     * @param string $where Clause WHERE pour le filtrage
     * @param array|string|null $groupBy Champs pour le groupement
     * @param int $limit Limite du nombre de résultats
     * @return array Les résultats de l'API
     */
    private static function callAPI($select = '', $where = '', $groupBy = null, $limit = 100)
    {
        // Gérer les signatures existantes
        if (is_array($select) && is_string($where) && is_array($groupBy)) {
            // Nouvelle signature: callAPI(['field1', 'field2'], 'where clause', ['field1', 'field2'])
            $selectStr = implode(', ', $select);
            $groupByStr = implode(', ', $groupBy);
        } else if (is_string($select) && is_string($where)) {
            // Ancienne signature: callAPI('where clause')
            $selectStr = '';
            $groupByStr = '';
        } else {
            return [];
        }
        
        // Vérifier le cache
        $cacheKey = md5(($selectStr ?? $select) . $where . ($groupByStr ?? '') . $limit . 'full');
        $cachedData = self::getCache($cacheKey);
        
        if ($cachedData !== false) {
            return $cachedData;
        }
        
        // Construire l'URL
        $url = self::API_BASE_URL . '?limit=' . $limit;
        
        if (!empty($selectStr)) {
            $url .= '&select=' . urlencode($selectStr);
        }
        
        if (!empty($where)) {
            $url .= '&where=' . urlencode($where);
        }
        
        if (!empty($groupByStr)) {
            $url .= '&group_by=' . urlencode($groupByStr);
        }

        
        // Effectuer la requête
        $response = @file_get_contents($url);
        
        if ($response === false) {
            trigger_error("Impossible de contacter l'API des vacances scolaires : " . $url, E_USER_WARNING);
            return [];
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['results'])) {
            trigger_error("Format de réponse invalide de l'API", E_USER_WARNING);
            return [];
        }
        
        // Normaliser les dates et résultats
        $results = [];
        foreach ($data['results'] as $record) {
            $results[] = [
                'description' => $record['description'] ?? '',
                'population' => $record['population'] ?? '-',
                'start_date' => $record['start_date'] ?? '',
                'end_date' => $record['end_date'] ?? '',
                'location' => $record['location'] ?? '',
                'zones' => $record['zones'] ?? '',
                'annee_scolaire' => $record['annee_scolaire'] ?? ''
            ];
        }
        
        // Mettre en cache
        self::setCache($cacheKey, $results);
        
        return $results;
    }

    /**
     * Appelle l'API avec groupement
     *
     * @param string $select Champ à sélectionner
     * @param string $where Clause WHERE pour le filtrage
     * @param string $groupBy Champ pour le groupement
     * @return array Les résultats groupés
     */
    private static function callAPIWithGroupBy($select = '', $where = '', $groupBy = '')
    {
        // Vérifier le cache
        $cacheKey = md5($select . $where . $groupBy . 'grouped');
        $cachedData = self::getCache($cacheKey);
        
        if ($cachedData !== false) {
            return $cachedData;
        }
        
        // Construire l'URL
        $url = self::API_BASE_URL . '?limit=100';
        
        if (!empty($select)) {
            $url .= '&select=' . urlencode($select);
        }
        
        if (!empty($where)) {
            $url .= '&where=' . urlencode($where);
        }
        
        if (!empty($groupBy)) {
            $url .= '&group_by=' . urlencode($groupBy);
        }
        
        // Effectuer la requête
        $response = @file_get_contents($url);
        
        if ($response === false) {
            trigger_error("Impossible de contacter l'API des vacances scolaires : " . $url, E_USER_WARNING);
            return [];
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['results'])) {
            trigger_error("Format de réponse invalide de l'API", E_USER_WARNING);
            return [];
        }
        
        // Extraire les valeurs du champ regroupé
        $results = [];
        foreach ($data['results'] as $record) {
            if (!empty($record[$select])) {
                $results[] = $record[$select];
            }
        }
        
        sort($results);
        
        // Mettre en cache
        self::setCache($cacheKey, $results);
        
        return $results;
    }

    /**
     * Récupère les données du cache
     *
     * @param string $key Clé du cache
     * @return mixed Les données mises en cache ou false
     */
    private static function getCache($key)
    {
        if (!is_dir(self::CACHE_DIR)) {
            return false;
        }
        
        $filepath = self::CACHE_DIR . '/' . $key . '.json';
        
        if (!file_exists($filepath)) {
            return false;
        }
        
        $mtime = filemtime($filepath);
        if (time() - $mtime > self::CACHE_DURATION) {
            @unlink($filepath);
            return false;
        }
        
        $data = file_get_contents($filepath);
        return json_decode($data, true);
    }

    /**
     * Stocke les données en cache
     *
     * @param string $key Clé du cache
     * @param mixed $data Les données à mettre en cache
     * @return bool Succès de l'opération
     */
    private static function setCache($key, $data)
    {
        if (!is_dir(self::CACHE_DIR)) {
            @mkdir(self::CACHE_DIR, 0755, true);
        }
        
        if (!is_writable(self::CACHE_DIR)) {
            return false;
        }
        
        $filepath = self::CACHE_DIR . '/' . $key . '.json';
        return file_put_contents($filepath, json_encode($data)) !== false;
    }

    /**
     * Efface le cache
     *
     * @return bool Succès de l'opération
     */
    public static function clearCache()
    {
        if (!is_dir(self::CACHE_DIR)) {
            return true;
        }
        
        $files = glob(self::CACHE_DIR . '/*.json');
        foreach ($files as $file) {
            @unlink($file);
        }
        
        return true;
    }
}

// Exemple d'utilisation
if (php_sapi_name() === 'cli') {
    $year = (int)($argv[1] ?? date('Y'));
    
    echo "=== Vacances scolaires {$year} ===\n";
    echo "Zones : " . implode(', ', SchoolHolidayService::getZones($year)) . "\n";
    echo "Académies (Zone A) : " . implode(', ', SchoolHolidayService::getLocations($year, 'A')) . "\n";
    echo "\n";
    
    $holidays = SchoolHolidayService::getHolidaysByYear($year, 'A');
    foreach ($holidays as $holiday) {
        $start = new DateTime($holiday['start_date']);
        $end = new DateTime($holiday['end_date']);
        echo $holiday['description'] . " (" . $holiday['location'] . ") : " . 
             $start->format('d/m/Y') . " - " . $end->format('d/m/Y') . "\n";
    }
}

?>
