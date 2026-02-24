<?php
/**
 * Grievance Seeder – Filipino context, random PAPS/Profile or full name,
 * dates from last year to now, random status with history.
 *
 * Run from project root: php database/seed_grievances.php [count]
 * Default count = 1000 (override with first argument).
 *
 * Requires: projects and optionally profiles in DB. Options (vulnerabilities,
 * respondent types, GRM channels, etc.) are used if present.
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Models\Grievance;
use App\Models\GrievanceStatusLog;
use App\Models\GrievanceVulnerability;
use App\Models\GrievanceRespondentType;
use App\Models\GrievanceGrmChannel;
use App\Models\GrievancePreferredLanguage;
use App\Models\GrievanceType;
use App\Models\GrievanceCategory;
use App\Models\GrievanceProgressLevel;
use App\Models\Project;
use App\Models\Profile;
use Core\Database;

// ============== SEED CONFIG ==============
$SEED_GRIEVANCE_COUNT = isset($argv[1]) ? max(1, (int) $argv[1]) : 1000;
// ========================================

// Filipino names
const FIRST_NAMES = [
    'Maria', 'Juan', 'Jose', 'Pedro', 'Ana', 'Rosa', 'Miguel', 'Antonio', 'Carmen', 'Francisco',
    'Teresa', 'Ramon', 'Pilar', 'Fernando', 'Lourdes', 'Eduardo', 'Manuel', 'Rita', 'Ricardo', 'Gloria',
    'Roberto', 'Luz', 'Carlos', 'Consuelo', 'Alberto', 'Cristina', 'Marco', 'Paulo', 'Isabel', 'Gabriel',
    'Sofia', 'Alejandro', 'Beatriz', 'Diego', 'Elena', 'Felipe', 'Imelda', 'Javier', 'Leonardo', 'Margarita',
    'Nestor', 'Olivia', 'Pablo', 'Rodrigo', 'Sylvia', 'Tomas', 'Vicente', 'Wilma', 'Zenaida', 'Bianca',
];
const SURNAMES = [
    'Reyes', 'Santos', 'Cruz', 'Bautista', 'Garcia', 'Ramos', 'Mendoza', 'Villanueva', 'Dela Cruz', 'Torres',
    'Fernandez', 'Gonzalez', 'Diaz', 'Castillo', 'Sanchez', 'Romero', 'Flores', 'Rivera', 'Aquino', 'Magsaysay',
    'Bonifacio', 'Luna', 'Rizal', 'Marcos', 'Estrada', 'Duterte', 'Macapagal', 'Osmeña', 'Quezon', 'Laurel',
    'Roxas', 'Quirino', 'Angara', 'Cayetano', 'Villar', 'Pimentel', 'Drilon', 'Sotto', 'Pacquiao', 'Binay',
];

// Filipino addresses (Barangay, Lungsod/Municipality, Lalawigan)
const BARANGAYS = [
    'Poblacion', 'San Roque', 'Sta. Cruz', 'Sto. Niño', 'Bagong Silang', 'Bayanihan', 'Maligaya', 'Pag-asa',
    'Sampaguita', 'Tagumpay', 'Ligaya', 'Masagana', 'Bagumbayan', 'Barangka', 'Concepcion', 'Del Pilar',
    'San Isidro', 'San Jose', 'San Juan', 'San Miguel', 'Santiago', 'Santo Domingo', 'Tanque', 'Tumana',
];
const MUNICIPALITIES = [
    'Manila', 'Quezon City', 'Caloocan', 'Davao City', 'Cebu City', 'Mandaluyong', 'Pasig', 'Marikina',
    'Paranaque', 'Las Pinas', 'Makati', 'Taguig', 'Valenzuela', 'Malabon', 'Navotas', 'San Juan',
    'Muntinlupa', 'Pasay', 'Pateros', 'Bacoor', 'Imus', 'Dasmariñas', 'Baguio', 'Iloilo City',
    'Cagayan de Oro', 'Zamboanga City', 'Antipolo', 'Cainta', 'Taytay', 'Binangonan', 'Angono',
];
const PROVINCES = [
    'Metro Manila', 'Cavite', 'Laguna', 'Rizal', 'Bulacan', 'Pampanga', 'Batangas', 'Quezon',
    'Cebu', 'Davao del Sur', 'Negros Occidental', 'Pangasinan', 'Iloilo', 'Camarines Sur', 'Leyte',
];

// Filipino complaint / resolution phrases (Tagalog/English)
const COMPLAINT_PHRASES = [
    'Hindi na naibigay ang tamang ayuda ayon sa listahan.',
    'Nawawala ang aking application form para sa housing assistance.',
    'Hindi makontak ang case officer sa loob ng dalawang buwan.',
    'Maling halaga ng cash transfer na na-credit sa account.',
    'Delay sa pag-release ng livelihood seed fund.',
    'Hindi na-update ang status ng aming resettlement application.',
    'Walang sumagot sa hotline at opisina ng proyekto.',
    'Nagreklamo kami tungkol sa kalidad ng materyales sa relocation site.',
    'Hindi naibalik ang aming dokumento pagkatapos ng verification.',
    'May discrepancy sa listahan ng beneficiaries at hindi naayos.',
    'Nawala ang record ng aming household sa system.',
    'Hindi na-process ang grievance namin within the promised period.',
    'Maling classification ng aming vulnerability status.',
    'Delay sa delivery ng assistance package.',
    'Hindi na-address ang damage sa aming structure from the project.',
];
const RESOLUTION_PHRASES = [
    'Nais naming ma-verify at ma-correct ang aming beneficiary status.',
    'Sana ay maibalik ang tamang halaga at ma-update ang record.',
    'Hiling namin na makausap ang case officer at ma-resolve ang issue.',
    'Nananawagan na ma-expedite ang processing ng aming application.',
    'Gusto naming ma-clarify ang timeline at next steps.',
    'Nais naming ma-acknowledge ang complaint at makatanggap ng feedback.',
    'Sana ay ma-follow up ang aming case at ma-resolve within 30 days.',
];

// Status change notes (Filipino)
const STATUS_NOTES = [
    'Na-receive ang reklamo. For initial assessment.',
    'Na-assign na sa case officer. Under review.',
    'Pending verification ng documents.',
    'Inaantay ang feedback mula sa field office.',
    'Na-escalate sa level 2. For further evaluation.',
    'Resolved. Na-communicate na sa complainant.',
    'Closed. Naka-comply na ang complainant sa requirements.',
];

function randomElement(array $arr)
{
    return $arr[array_rand($arr)];
}

function randomSubset(array $arr, int $min = 0, int $max = 3): array
{
    if (empty($arr)) return [];
    $n = count($arr);
    $k = min($max, max($min, random_int(0, min($n, $max))));
    if ($k <= 0) return [];
    $ids = array_map(fn($o) => (int) $o->id, $arr);
    shuffle($ids);
    return array_slice($ids, 0, $k);
}

function randomDateFromYearAgo(): string
{
    $end = time();
    $start = strtotime('-1 year');
    $ts = random_int($start, $end);
    return date('Y-m-d H:i:s', $ts);
}

function randomDateBetween(string $from, string $to): string
{
    $a = strtotime($from);
    $b = strtotime($to);
    if ($a > $b) [$a, $b] = [$b, $a];
    $ts = random_int($a, $b);
    return date('Y-m-d H:i:s', $ts);
}

function randomPhone(): string
{
    return '09' . str_pad((string) random_int(100000000, 999999999), 9, '0');
}

function randomAddress(): string
{
    $b = randomElement(BARANGAYS);
    $m = randomElement(MUNICIPALITIES);
    $p = randomElement(PROVINCES);
    $num = random_int(1, 999);
    return "Blk/Lot $num, Brgy. $b, $m, $p";
}

// --- Main ---
echo "Grievance Seeder (Filipino context)\n";
echo "====================================\n";
echo "Count: " . number_format($SEED_GRIEVANCE_COUNT) . "\n\n";

$db = Database::getInstance();

$projects = Project::all();
$projectIds = array_map(fn($p) => (int) $p->id, $projects);
if (empty($projectIds)) {
    echo "ERROR: No projects in database. Run projects/profiles seeder first or create projects.\n";
    exit(1);
}

$profiles = Profile::all();
$profilesByProject = [];
foreach ($profiles as $p) {
    $pid = (int) $p->project_id;
    if ($pid) {
        if (!isset($profilesByProject[$pid])) $profilesByProject[$pid] = [];
        $profilesByProject[$pid][] = $p;
    }
}

$vulnerabilities = GrievanceVulnerability::all();
$respondentTypes = GrievanceRespondentType::all();
$grmChannels = GrievanceGrmChannel::all();
$preferredLanguages = GrievancePreferredLanguage::all();
$grievanceTypes = GrievanceType::all();
$grievanceCategories = GrievanceCategory::all();
$progressLevels = GrievanceProgressLevel::all();
$progressLevelIds = array_map(fn($pl) => (int) $pl->id, $progressLevels);

$firstUserId = null;
$u = $db->query('SELECT id FROM users ORDER BY id ASC LIMIT 1')->fetch(\PDO::FETCH_OBJ);
if ($u) $firstUserId = (int) $u->id;

$statuses = ['open', 'in_progress', 'closed'];
$genders = ['Male', 'Female', 'Others', 'Prefer not to say'];

echo "Projects: " . count($projectIds) . " | Profiles: " . count($profiles) . "\n";
echo "Options: Vuln=" . count($vulnerabilities) . " Resp=" . count($respondentTypes) . " GRM=" . count($grmChannels) . " Lang=" . count($preferredLanguages) . " Types=" . count($grievanceTypes) . " Cat=" . count($grievanceCategories) . " Levels=" . count($progressLevels) . "\n\n";

$created = 0;
$oneYearAgo = date('Y-m-d H:i:s', strtotime('-1 year'));
$now = date('Y-m-d H:i:s');

for ($i = 0; $i < $SEED_GRIEVANCE_COUNT; $i++) {
    $dateRecorded = randomDateFromYearAgo();
    $projectId = randomElement($projectIds);
    $isPaps = !empty($profiles) && random_int(0, 1) === 1;

    $profileId = null;
    $respondentFullName = '';
    if ($isPaps && !empty($profilesByProject[$projectId])) {
        $profList = $profilesByProject[$projectId];
        $profile = $profList[array_rand($profList)];
        $profileId = (int) $profile->id;
        $projectId = (int) $profile->project_id;
    } elseif (!$isPaps || empty($profiles)) {
        $respondentFullName = randomElement(FIRST_NAMES) . ' ' . randomElement(SURNAMES);
        if ($isPaps) $isPaps = false;
    }

    $grmIds = [];
    if (!empty($grmChannels)) {
        $gc = randomElement($grmChannels);
        $grmIds = [(int) $gc->id];
    }

    $data = [
        'date_recorded' => $dateRecorded,
        'grievance_case_number' => Grievance::generateCaseNumber(),
        'project_id' => $projectId ?: null,
        'is_paps' => $isPaps,
        'profile_id' => $profileId,
        'respondent_full_name' => $respondentFullName,
        'gender' => randomElement($genders),
        'gender_specify' => '',
        'valid_id_philippines' => random_int(0, 1) ? 'National ID' : 'Voter\'s ID',
        'id_number' => (string) random_int(100000, 999999),
        'vulnerability_ids' => randomSubset($vulnerabilities, 0, 2),
        'respondent_type_ids' => randomSubset($respondentTypes, 0, 2),
        'respondent_type_other_specify' => '',
        'home_business_address' => randomAddress(),
        'mobile_number' => randomPhone(),
        'email' => random_int(0, 1) ? ('user' . $i . '@example.ph') : '',
        'contact_others_specify' => '',
        'grm_channel_ids' => $grmIds,
        'preferred_language_ids' => randomSubset($preferredLanguages, 0, 2),
        'preferred_language_other_specify' => '',
        'grievance_type_ids' => randomSubset($grievanceTypes, 0, 2),
        'grievance_category_ids' => randomSubset($grievanceCategories, 0, 2),
        'location_same_as_address' => random_int(0, 1),
        'location_specify' => random_int(0, 1) ? randomAddress() : '',
        'incident_one_time' => random_int(0, 1),
        'incident_date' => random_int(0, 1) ? date('Y-m-d', strtotime($dateRecorded) - random_int(0, 90) * 86400) : null,
        'incident_multiple' => random_int(0, 1),
        'incident_dates' => '',
        'incident_ongoing' => random_int(0, 1),
        'description_complaint' => randomElement(COMPLAINT_PHRASES),
        'desired_resolution' => randomElement(RESOLUTION_PHRASES),
        'status' => 'open',
        'progress_level' => null,
    ];

    $gid = Grievance::create($data);
    $created++;

    // Status history: first entry "open" at date_recorded
    $stmtLog = $db->prepare('INSERT INTO grievance_status_log (grievance_id, status, progress_level, note, attachments, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmtLog->execute([$gid, 'open', null, randomElement(STATUS_NOTES), json_encode([]), $firstUserId, $dateRecorded]);

    $numTransitions = random_int(0, 4);
    $currentStatus = 'open';
    $currentLevel = null;
    $lastAt = $dateRecorded;

    for ($t = 0; $t < $numTransitions; $t++) {
        if ($currentStatus === 'closed') break;
        $nextStatus = $statuses[array_rand($statuses)];
        if ($nextStatus === 'in_progress' && !empty($progressLevelIds)) {
            $currentLevel = $progressLevelIds[array_rand($progressLevelIds)];
        } elseif ($nextStatus !== 'in_progress') {
            $currentLevel = null;
        }
        $logAt = randomDateBetween($lastAt, $now);
        $lastAt = $logAt;
        // Insert log with that timestamp (we need to do raw insert for custom created_at)
        $stmt = $db->prepare('INSERT INTO grievance_status_log (grievance_id, status, progress_level, note, attachments, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $gid,
            $nextStatus,
            $currentLevel,
            randomElement(STATUS_NOTES),
            json_encode([]),
            $firstUserId,
            $logAt,
        ]);
        $currentStatus = $nextStatus;
    }

    // Update grievance to final status/level
    $finalLevel = $currentStatus === 'in_progress' ? $currentLevel : null;
    $db->prepare('UPDATE grievances SET status = ?, progress_level = ? WHERE id = ?')->execute([$currentStatus, $finalLevel, $gid]);

    if (($i + 1) % 100 === 0) {
        echo "  " . ($i + 1) . " / " . $SEED_GRIEVANCE_COUNT . " grievances created.\n";
    }
}

echo "\nDone!\n";
echo "  Grievances created: " . number_format($created) . "\n";
