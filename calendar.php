<?php

require_once __DIR__ . '/services/EasterCalculator.php';
require_once __DIR__ . '/services/FlowerCalendar.php';
require_once __DIR__ . '/services/SchoolHolidayService.php';

/**
 * Classe pour générer un calendrier HTML annuel
 */
class CalendarGenerator
{
    private $year;
    private $holidays;
    private $schoolHolidaysA;
    private $schoolHolidaysB;
    private $schoolHolidaysC;

    public function __construct($year)
    {
        $this->year = $year;
        $this->holidays = EasterCalculator::getFrenchHolidays($year);
        
        // Charger les vacances scolaires pour les trois zones
        $this->schoolHolidaysA = SchoolHolidayService::getHolidaysByYear($year, 'A');
        $this->schoolHolidaysB = SchoolHolidayService::getHolidaysByYear($year, 'B');
        $this->schoolHolidaysC = SchoolHolidayService::getHolidaysByYear($year, 'C');
    }

    /**
     * Vérifie si une date est en vacances scolaires pour une zone donnée
     *
     * @param DateTime $date
     * @param string $zone 'A', 'B', ou 'C'
     * @return bool True si en vacances
     */
    private function isSchoolHoliday($date, $zone)
    {
        $holidays = [];
        switch($zone) {
            case 'A':
                $holidays = $this->schoolHolidaysA;
                break;
            case 'B':
                $holidays = $this->schoolHolidaysB;
                break;
            case 'C':
                $holidays = $this->schoolHolidaysC;
                break;
        }
        
        $dateStr = $date->format('Y-m-d');
        
        foreach ($holidays as $holiday) {
            // Comparaison simple de dates en format Y-m-d
            // La date de fin est exclusive (jour de rentrée)
            if ($dateStr >= $holiday['start_date'] && $dateStr < $holiday['end_date']) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Vérifie si une date est un jour férié
     *
     * @param DateTime $date
     * @return string|false Le nom du jour férié ou false
     */
    private function getHolidayName($date)
    {
        foreach ($this->holidays as $name => $holidayDate) {
            if ($date->format('Y-m-d') === $holidayDate->format('Y-m-d')) {
                return $name;
            }
        }
        return false;
    }

    /**
     * Calcule les statistiques de jours travaillables
     *
     * @param int $year L'année
     * @return array Tableau avec 'workdays' et 'rtt'
     */
    private function calculateWorkDayStats($year)
    {
        $totalDays = 365;
        if ((int)date('L', mktime(0, 0, 0, 1, 1, $year))) {
            $totalDays = 366; // Année bissextile
        }
        
        $saturdays = 0;
        $sundays = 0;
        $holidaysOnWeekday = 0;
        
        // Compter les samedis et dimanches
        for ($month = 1; $month <= 12; $month++) {
            $daysInMonth = (int)date('t', mktime(0, 0, 0, $month, 1, $year));
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = new DateTime("{$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-" . str_pad($day, 2, '0', STR_PAD_LEFT));
                $dayOfWeek = (int)$date->format('w');
                
                if ($dayOfWeek === 6) {
                    $saturdays++;
                } elseif ($dayOfWeek === 0) {
                    $sundays++;
                }
                
                // Compter les jours fériés qui ne tombent pas le weekend
                $holiday = $this->getHolidayName($date);
                if ($holiday && $dayOfWeek !== 0 && $dayOfWeek !== 6) {
                    $holidaysOnWeekday++;
                }
            }
        }
        
        // Calcul des jours travaillables
        $workdays = $totalDays - $saturdays - $sundays - $holidaysOnWeekday;
        
        // Calcul des RTT (forfait 213 jours + 25 jours de congés payés)
        $congesPayes = 25;
        $rtt = $workdays - $congesPayes - 213;
        if ($rtt < 0) {
            $rtt = 0;
        }
        
        return [
            'workdays' => $workdays,
            'rtt' => $rtt
        ];
    }

    /**
     * Génère le HTML pour un mois
     *
     * @param int $month Le mois (1-12)
     * @return string Le HTML du mois
     */
    private function generateMonthHTML($month)
    {
        $monthNames = [
            'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
            'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'
        ];

        $dayNames = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
        $dayInitials = ['D', 'L', 'M', 'M', 'J', 'V', 'S']; // Dimanche to Samedi
        $firstDay = new DateTime("{$this->year}-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01");
        $lastDay = clone $firstDay;
        $lastDay->modify('last day of this month');
        $lastDayNum = (int)$lastDay->format('d');

        // Génération du HTML
        $html = '<div class="month-calendar">' . "\n";
        $html .= '  <h3>' . ucfirst($monthNames[$month - 1]) . '</h3>' . "\n";
        $html .= '  <table>' . "\n";

        // Générer une ligne par jour du mois
        $currentWeek = null;
        for ($day = 1; $day <= $lastDayNum; $day++) {
            $currentDate = new DateTime("{$this->year}-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-" . str_pad($day, 2, '0', STR_PAD_LEFT));
            $dayOfWeekNum = (int)$currentDate->format('w');
            $dayInitial = $dayInitials[$dayOfWeekNum];
            $isToday = $currentDate->format('Y-m-d') === date('Y-m-d');
            $holiday = $this->getHolidayName($currentDate);
            $isWeekend = ($dayOfWeekNum === 0 || $dayOfWeekNum === 6); // 0=dimanche, 6=samedi

            // Déterminer le numéro de la semaine (1=lun-dim, etc.)
            $weekNum = (int)$currentDate->format('W');
            
            // Fermer la semaine précédente si on change de semaine
            if ($currentWeek !== null && $currentWeek !== $weekNum) {
                $html .= '    </tbody>' . "\n";
            }
            
            // Ouvrir une nouvelle semaine
            if ($currentWeek !== $weekNum) {
                $html .= '    <tbody class="week" data-week="' . $weekNum . '">' . "\n";
                $currentWeek = $weekNum;
            }

            $cellClass = ['day-row'];
            if ($isToday) {
                $cellClass[] = 'today';
            }
            if ($holiday) {
                $cellClass[] = 'holiday';
            }
            if ($isWeekend) {
                $cellClass[] = 'weekend';
            }
            
            // Ajouter les classes pour les vacances scolaires des différentes zones
            if ($this->isSchoolHoliday($currentDate, 'A')) {
                $cellClass[] = 'school-holiday-a';
            }
            if ($this->isSchoolHoliday($currentDate, 'B')) {
                $cellClass[] = 'school-holiday-b';
            }
            if ($this->isSchoolHoliday($currentDate, 'C')) {
                $cellClass[] = 'school-holiday-c';
            }

            $classAttr = ' class="' . implode(' ', $cellClass) . '"';
            
            $html .= '      <tr' . $classAttr . '>' . "\n";
            $html .= '        <td class="date-col">' . $day . '</td>' . "\n";
            $html .= '        <td class="day-col">' . $dayInitial . '</td>' . "\n";
            $html .= '        <td class="holiday-col">';
            
            if ($holiday) {
                $html .= '<span class="holiday-name">' . $holiday . '</span>';
            } else {
                $dayInfo = FlowerCalendar::getName($day, $month, $this->year);
                if (is_array($dayInfo) && $dayInfo['name']) {
                    $cssClass = $dayInfo['type'] === 'festival' ? 'festival-name' : 'flower-name';
                    $html .= '<span class="' . $cssClass . '">' . $dayInfo['name'] . '</span>';
                } elseif (is_string($dayInfo) && $dayInfo) {
                    $html .= '<span class="flower-name">' . $dayInfo . '</span>';
                }
            }
            
            $html .= '</td>' . "\n";
            $html .= '      </tr>' . "\n";
        }

        // Fermer la dernière semaine
        if ($currentWeek !== null) {
            $html .= '    </tbody>' . "\n";
        }

        $html .= '  </table>' . "\n";
        $html .= '</div>' . "\n";

        return $html;
    }

    /**
     * Génère le calendrier complet en HTML
     *
     * @return string Le HTML complet du calendrier
     */
    public function generateCalendar()
    {
        $html = '<!DOCTYPE html>' . "\n";
        $html .= '<html lang="fr">' . "\n";
        $html .= '<head>' . "\n";
        $html .= '  <meta charset="UTF-8">' . "\n";
        $html .= '  <meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
        $html .= '  <meta name="theme-color" content="#667eea">' . "\n";
        $html .= '  <meta name="mobile-web-app-capable" content="yes">' . "\n";
        $html .= '  <meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
        $html .= '  <meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";
        $html .= '  <meta name="apple-mobile-web-app-title" content="Calendrier">' . "\n";
        $html .= '  <link rel="manifest" href="/manifest.webmanifest">' . "\n";
        $html .= '  <link rel="icon" href="/favicon.svg" type="image/svg+xml">' . "\n";
        $html .= '  <link rel="apple-touch-icon" href="/icons/icon-192.svg">' . "\n";
        $html .= '  <title>Calendrier Perpétuel ' . $this->year . '</title>' . "\n";
        $html .= '  <style>' . "\n";
        $html .= $this->generateCSS();
        $html .= '  </style>' . "\n";
        $html .= '</head>' . "\n";
        $html .= '<body>' . "\n";
        
        $html .= '<h1>';
        $html .= '<a href="?year=' . ($this->year - 1) . '" class="year-nav">&laquo;</a>';
        $html .= ' Calendrier ' . $this->year . ' ';
        $html .= '<a href="?year=' . ($this->year + 1) . '" class="year-nav">&raquo;</a>';
        $html .= '<button id="install-app" class="install-btn" type="button" aria-label="Installer l\'application">Installer</button>';
        $html .= '</h1>' . "\n";
        $html .= '<div class="calendar-container">' . "\n";

        // Générer les 12 mois
        for ($month = 1; $month <= 12; $month++) {
            $html .= $this->generateMonthHTML($month);
        }

        $html .= '</div>' . "\n";
        
        // Légende avec statistiques
        $html .= '<div class="legend">' . "\n";
        
        // Calculer les statistiques
        $stats = $this->calculateWorkDayStats($this->year);
        
        // Statistiques sur une ligne
        $html .= '  <div class="stats-line">' . "\n";
        $html .= '    <span class="stat-label">Jours travaillables : <strong>' . $stats['workdays'] . '</strong></span>' . "\n";
        $html .= '    <span class="separator">|</span>' . "\n";
        $html .= '    <span class="stat-label">RTT (forfait <input type="number" id="forfait" class="forfait-input" value="213" min="200" max="230"> jours) : <strong id="rtt-value">' . $stats['rtt'] . '</strong></span>' . "\n";
        $html .= '  </div>' . "\n";
        
        // Légende des zones avec académies
        $html .= '  <div class="color-legend">' . "\n";
        $html .= '    <h4>Vacances scolaires par zone :</h4>' . "\n";
        
        $locationsA = SchoolHolidayService::getLocations($this->year, 'A');
        $locationsB = SchoolHolidayService::getLocations($this->year, 'B');
        $locationsC = SchoolHolidayService::getLocations($this->year, 'C');
        
        $html .= '    <div class="zone-items">' . "\n";
        $html .= '      <div class="zone-item">' . "\n";
        $html .= '        <div class="zone-header"><span class="zone-color" style="background-color: #FFA500;"></span><strong>Zone A</strong></div>' . "\n";
        $html .= '        <div class="zone-academies">' . implode(', ', $locationsA) . '</div>' . "\n";
        $html .= '      </div>' . "\n";
        
        $html .= '      <div class="zone-item">' . "\n";
        $html .= '        <div class="zone-header"><span class="zone-color" style="background-color: #228B22;"></span><strong>Zone B</strong></div>' . "\n";
        $html .= '        <div class="zone-academies">' . implode(', ', $locationsB) . '</div>' . "\n";
        $html .= '      </div>' . "\n";
        
        $html .= '      <div class="zone-item">' . "\n";
        $html .= '        <div class="zone-header"><span class="zone-color" style="background-color: #0066CC;"></span><strong>Zone C</strong></div>' . "\n";
        $html .= '        <div class="zone-academies">' . implode(', ', $locationsC) . '</div>' . "\n";
        $html .= '      </div>' . "\n";
        $html .= '    </div>' . "\n";
        $html .= '  </div>' . "\n";
        $html .= '</div>' . "\n";
        
        // Ajouter le script JavaScript pour recalculer les RTT
        $html .= '<script>' . "\n";
        $html .= 'document.getElementById("forfait").addEventListener("change", function() {' . "\n";
        $html .= '  const workdays = ' . $stats['workdays'] . ';' . "\n";
        $html .= '  const forfait = parseInt(this.value);' . "\n";
        $html .= '  const congesPayes = 25;' . "\n";
        $html .= '  const rtt = Math.max(0, workdays - congesPayes - forfait);' . "\n";
        $html .= '  document.getElementById("rtt-value").textContent = rtt + " jours";' . "\n";
        $html .= '});' . "\n";
        $html .= 'const installButton = document.getElementById("install-app");' . "\n";
        $html .= 'let deferredInstallPrompt = null;' . "\n";
        $html .= 'const isMobile = window.matchMedia("(max-width: 768px)").matches;' . "\n";
        $html .= 'window.addEventListener("beforeinstallprompt", function(event) {' . "\n";
        $html .= '  event.preventDefault();' . "\n";
        $html .= '  deferredInstallPrompt = event;' . "\n";
        $html .= '  if (isMobile) {' . "\n";
        $html .= '    installButton.style.display = "inline-flex";' . "\n";
        $html .= '  }' . "\n";
        $html .= '});' . "\n";
        $html .= 'installButton.addEventListener("click", async function() {' . "\n";
        $html .= '  if (!deferredInstallPrompt) {' . "\n";
        $html .= '    return;' . "\n";
        $html .= '  }' . "\n";
        $html .= '  deferredInstallPrompt.prompt();' . "\n";
        $html .= '  await deferredInstallPrompt.userChoice;' . "\n";
        $html .= '  deferredInstallPrompt = null;' . "\n";
        $html .= '  installButton.style.display = "none";' . "\n";
        $html .= '});' . "\n";
        $html .= 'window.addEventListener("appinstalled", function() {' . "\n";
        $html .= '  installButton.style.display = "none";' . "\n";
        $html .= '});' . "\n";
        $html .= 'if ("serviceWorker" in navigator) {' . "\n";
        $html .= '  window.addEventListener("load", function() {' . "\n";
        $html .= '    navigator.serviceWorker.register("/service-worker.js").catch(function(error) {' . "\n";
        $html .= '      console.warn("Service worker registration failed:", error);' . "\n";
        $html .= '    });' . "\n";
        $html .= '  });' . "\n";
        $html .= '}' . "\n";
        $html .= '</script>' . "\n";

        $html .= '</body>' . "\n";
        $html .= '</html>';

        return $html;
    }

    /**
     * Génère le CSS pour le calendrier
     *
     * @return string Le CSS
     */
    private function generateCSS()
    {
        return <<<'CSS'
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      padding: 10px;
      min-height: 100vh;
    }

    h1 {
      text-align: center;
      color: white;
      margin-bottom: 10px;
      font-size: 1.5em;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 15px;
    }

    .year-nav {
      color: white;
      text-decoration: none;
      font-size: 1.2em;
      transition: color 0.2s;
      cursor: pointer;
    }

    .year-nav:hover {
      color: #dc3545;
    }

    .install-btn {
      display: none;
      margin-left: 10px;
      border: 0;
      border-radius: 6px;
      padding: 6px 10px;
      font-size: 0.6em;
      font-weight: 700;
      background: #ffffff;
      color: #333;
      cursor: pointer;
    }

    .install-btn:active {
      transform: translateY(1px);
    }

    .calendar-container {
      display: grid;
      grid-template-columns: repeat(12, 1fr);
      gap: 2px;
      max-width: 100%;
      margin: 0 auto 10px;
      padding: 0 5px;
    }

    .month-calendar {
      background: white;
      border-radius: 2px;
      padding: 3px;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      min-width: 0;
    }

    .month-calendar h3 {
      text-align: center;
      color: #FFF;
      background-color: #000;
      margin-bottom: 3px;
      font-size: 0.85em;
      text-transform: capitalize;
      font-weight: 600;
      padding: 2px 0;
    }

    .month-calendar table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.75em;
    }

    .month-calendar thead {
      background-color: #667eea;
      color: white;
    }

    .month-calendar th {
      padding: 2px 1px;
      text-align: center;
      font-weight: 600;
      font-size: 0.8em;
    }

    .month-calendar td {
      border: 1px solid #e0e0e0;
      padding: 1px;
      text-align: left;
    }

    .day-col {
      width: 8%;
      font-weight: 600;
      color: #333;
    }

    .date-col {
      width: 12%;
      font-weight: 600;
      color: #333;
    }

    .holiday-col {
      width: 74%;
      text-align: left;
      padding-left: 1px;
      font-size: 1em;
      position: relative;
    }

    .month-calendar tr.today {
      background-color: #fff3cd;
    }

    .month-calendar tr.holiday {
      background-color: #ffe6e6;
    }

    .month-calendar tr.weekend {
      background-color: #e8e8e8;
    }

    .month-calendar tr.today.weekend {
      background-color: #ffe6cc;
    }

    .month-calendar tr.holiday.weekend {
      background-color: #ffcccc;
    }

    .month-calendar tbody.week {
      border: 2px solid #BBB;
      display: table-row-group;
      position: relative;
    }

    .month-calendar tbody.week::before {
      content: attr(data-week);
      position: absolute;
      top: 50%;
      right: 5px;
      transform: translateY(-50%);
      font-size: 2.5em;
      font-weight: 700;
      color: rgba(0, 0, 0, 0.1);
      pointer-events: none;
      z-index: 0;
    }

    /* Lignes verticales en filigrane pour les vacances scolaires */
    /* 3 lignes fixes alignées à droite */
    
    /* Zone A - ligne orange à droite */
    .month-calendar tr.school-holiday-a .holiday-col::before {
      content: '';
      position: absolute;
      right: 22px;
      top: 0;
      bottom: 0;
      width: 8px;
      background-color: rgba(255, 165, 0, 0.35);
      z-index: 0;
    }

    /* Zone B - ligne verte au centre-droite */
    .month-calendar tr.school-holiday-b .holiday-col::after {
      content: '';
      position: absolute;
      right: 12px;
      top: 0;
      bottom: 0;
      width: 8px;
      background-color: rgba(34, 139, 34, 0.35);
      z-index: 0;
    }

    /* Zone C - ligne bleue tout à droite */
    .month-calendar tr.school-holiday-c .holiday-col {
      background: linear-gradient(
        90deg,
        transparent 0px,
        transparent calc(100% - 8px),
        rgba(0, 102, 204, 0.35) calc(100% - 8px),
        rgba(0, 102, 204, 0.35) 100%
      );
    }

    /* Le texte doit rester au-dessus des lignes */
    .holiday-col span {
      position: relative;
      z-index: 1;
    }

    .week-header {
      background-color: #f5f5f5;
    }

    .week-number {
      text-align: center;
      font-weight: 700;
      font-size: 0.9em;
      padding: 2px 1px !important;
      border-bottom: 1px solid #ccc;
    }

    .month-calendar tr.today .day-col,
    .month-calendar tr.today .date-col {
      color: #ff9800;
      font-weight: bold;
    }

    .month-calendar tr.holiday .day-col,
    .month-calendar tr.holiday .date-col {
      color: #dc3545;
      font-weight: bold;
    }

    .holiday-name {
      color: #dc3545;
      font-weight: 600;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .flower-name {
      color: #666;
      font-weight: 500;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .festival-name {
      color: #0066cc;
      font-weight: 500;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .legend {
      background: white;
      border-radius: 8px;
      padding: 20px;
      max-width: 1400px;
      margin: 0 auto;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .legend h3 {
      color: #333;
      margin-bottom: 10px;
      font-size: 0.95em;
    }

    .stats-line {
      display: flex;
      align-items: center;
      gap: 20px;
      margin-bottom: 5px;
      font-size: 0.9em;
    }

    .stats-line .stat-label {
      color: #666;
      font-weight: 600;
    }

    .stats-line strong {
      color: #333;
      font-weight: 700;
      font-size: 1.1em;
    }

    .separator {
      color: #ccc;
    }

    .forfait-input {
      width: 65px;
      padding: 4px 8px;
      font-size: 0.9em;
      border: 1px solid #ccc;
      border-radius: 4px;
      margin: 0 4px;
    }

    .forfait-input:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 4px rgba(102, 126, 234, 0.3);
    }

    .stat-value {
      font-size: 1.5em;
      color: #333;
      font-weight: 700;
    }

    .color-legend {
      border-top: 1px solid #e0e0e0;
    }

    .color-legend h4 {
      color: #333;
      font-size: 0.9em;
      margin-bottom: 12px;
      font-weight: 600;
    }

    .zone-items {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 15px;
    }

    .zone-item {
      padding: 12px;
      background-color: #f9f9f9;
      border-radius: 4px;
      border-left: 4px solid #ddd;
    }

    .zone-header {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 8px;
      font-size: 0.95em;
    }

    .zone-color {
      width: 16px;
      height: 16px;
      border-radius: 2px;
      display: inline-block;
    }

    .zone-academies {
      font-size: 0.85em;
      color: #666;
      line-height: 1.4;
    }

    .legend-items {
      display: flex;
      gap: 25px;
      flex-wrap: wrap;
    }

    .legend-item {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 0.9em;
      color: #666;
    }

    .legend-color {
      width: 20px;
      height: 20px;
      border-radius: 3px;
      display: inline-block;
    }

    @media (max-width: 768px) {
      .calendar-container {
        grid-template-columns: repeat(6, 1fr);
      }

      h1 {
        font-size: 1.8em;
        gap: 10px;
      }

      .install-btn {
        font-size: 0.5em;
        padding: 6px 8px;
      }

      .month-calendar table {
        font-size: 0.7em;
      }

      .zone-items {
        grid-template-columns: 1fr;
      }

      .stats-line {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }
    }

    @media (max-width: 480px) {
      .calendar-container {
        grid-template-columns: repeat(3, 1fr);
      }

      h1 {
        font-size: 1.4em;
        gap: 8px;
      }

      .month-calendar {
        padding: 5px;
      }

      .month-calendar h3 {
        font-size: 0.8em;
      }
    }
CSS;
    }
}

// Utilisation
if (php_sapi_name() === 'cli') {
    $year = (int)($argv[1] ?? date('Y'));
    $generator = new CalendarGenerator($year);
    $html = $generator->generateCalendar();
    
    // Sauvegarder dans un fichier ou afficher
    $filename = "calendrier_{$year}.html";
    file_put_contents($filename, $html);
    echo "Calendrier généré : {$filename}\n";
} else {
    // Mode web
    $year = (int)($_GET['year'] ?? date('Y'));
    $generator = new CalendarGenerator($year);
    echo $generator->generateCalendar();
}

?>
