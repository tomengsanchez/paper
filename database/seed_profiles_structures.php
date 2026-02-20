<?php
/**
 * Seeder: 10,000 Profiles, 20 Structures each (8 tagging + 10 structure images per structure), 50 Projects
 * Context: Philippine Government Projects
 *
 * Run from project root: php database/seed_profiles_structures.php
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Models\Profile;
use App\Models\Structure;
use App\Models\Project;
use Core\Database;

const UPLOAD_BASE = __DIR__ . '/../public/uploads/structure';
const WEB_BASE = '/uploads/structure';

const PROFILE_COUNT = 10000;
const BATCH_SIZE = 50;
const STRUCTURES_PER_PROFILE = 20;
const TAGGING_IMAGES_PER_STRUCTURE = 8;
const STRUCTURE_IMAGES_PER_STRUCTURE = 10;
const TARGET_PROJECT_COUNT = 50;

// Philippine Government Projects (50)
const PROJECTS = [
    ['name' => '4Ps - Pantawid Pamilyang Pilipino Program', 'description' => 'Conditional cash transfer for health and education of poor households.'],
    ['name' => 'KALAHI-CIDSS', 'description' => 'Kapit-Bisig Laban sa Kahirapan - Community-driven development and infrastructure.'],
    ['name' => 'SLP - Sustainable Livelihood Program', 'description' => 'Micro-enterprise and employment facilitation for marginalized families.'],
    ['name' => 'NHA Resettlement Program', 'description' => 'National Housing Authority - Relocation and housing for informal settlers.'],
    ['name' => 'TUPAD - Tulong Panghanapbuhay', 'description' => 'Emergency employment for displaced and seasonal workers.'],
    ['name' => 'BALAI - Balik Probinsya, Bagong Pag-asa', 'description' => 'Decongestion of Metro Manila through provincial relocation.'],
    ['name' => 'PAMANA - Payapa at Masaganang Pamayanan', 'description' => 'Peace and development in conflict-affected areas.'],
    ['name' => 'BUB - Bottom-Up Budgeting', 'description' => 'Participatory budgeting for local government projects.'],
    ['name' => 'CCT - Conditional Cash Transfer', 'description' => 'Financial assistance conditional on health and education compliance.'],
    ['name' => 'CHTs - Community Health Teams', 'description' => 'Barangay health workers deployment program.'],
    ['name' => 'CSF - Core Shelter Assistance', 'description' => 'Typhoon-resistant housing for disaster victims.'],
    ['name' => 'DA - Kapatid Angat Lahat', 'description' => 'Department of Agriculture agribusiness development.'],
    ['name' => 'DILG - Sagana at Ligtas na Tubig', 'description' => 'Water supply systems for rural communities.'],
    ['name' => 'DOST - Small Enterprise Technology Upgrading', 'description' => 'Technology transfer for MSMEs.'],
    ['name' => 'DOLE - Special Program for Employment of Students', 'description' => 'Student summer employment.'],
    ['name' => 'DPWH - Farm-to-Market Roads', 'description' => 'Rural road networks for agricultural areas.'],
    ['name' => 'DSWD - Supplementary Feeding Program', 'description' => 'Nutrition support for day care children.'],
    ['name' => 'DTI - Shared Service Facilities', 'description' => 'Common facilities for MSME clusters.'],
    ['name' => 'DENR - National Greening Program', 'description' => 'Reforestation and watershed rehabilitation.'],
    ['name' => 'DepEd - Last Mile Schools', 'description' => 'Schools in geographically isolated areas.'],
    ['name' => 'DOH - Bakuna Centers', 'description' => 'Vaccination and health centers.'],
    ['name' => 'HLURB - Socialized Housing', 'description' => 'Affordable housing for informal settlers.'],
    ['name' => 'Pag-IBIG - Affordable Housing Program', 'description' => 'Housing loans for minimum wage earners.'],
    ['name' => 'PhilHealth - Indigent Program', 'description' => 'Health insurance for poor families.'],
    ['name' => 'SSS - Flexi-Fund', 'description' => 'Social security for informal sector.'],
    ['name' => 'TESDA - Training for Work Scholarship', 'description' => 'Technical-vocational skills training.'],
    ['name' => 'LGU - BPLS Streamlining', 'description' => 'Business permits and licensing simplification.'],
    ['name' => 'DICT - Free Wi-Fi in Public Places', 'description' => 'Internet access in plazas, parks, terminals.'],
    ['name' => 'PCC - Kapatid Mentor Me', 'description' => 'Mentoring for micro entrepreneurs.'],
    ['name' => 'BFAR - Fish Landing Centers', 'description' => 'Port facilities for artisanal fishermen.'],
    ['name' => 'PCA - Coconut Farmers Rehabilitation', 'description' => 'Support for coconut industry recovery.'],
    ['name' => 'DAR - Agrarian Reform Community Development', 'description' => 'Infrastructure in agrarian reform areas.'],
    ['name' => 'NIA - National Irrigation Systems', 'description' => 'Irrigation for rainfed farmlands.'],
    ['name' => 'PhilMech - Farm Machinery Distribution', 'description' => 'Mechanization support for farmers.'],
    ['name' => 'DA - Rice Competitiveness Enhancement', 'description' => 'Seeds, credit, and equipment for rice farmers.'],
    ['name' => 'DepEd - Gulayan sa Paaralan', 'description' => 'School-based vegetable gardens.'],
    ['name' => 'DOH - Botika ng Barangay', 'description' => 'Affordable medicines in communities.'],
    ['name' => 'DILG - Seal of Good Local Governance', 'description' => 'Incentives for high-performing LGUs.'],
    ['name' => 'DSWD - Kapit-Bisig sa Pag-unlad', 'description' => 'Community-based poverty reduction.'],
    ['name' => 'OWWA - Reintegration Program', 'description' => 'OFW livelihood upon return.'],
    ['name' => 'CHED - Tulong Dunong', 'description' => 'Scholarship for indigent students.'],
    ['name' => 'PRC - Continuing Professional Development', 'description' => 'License renewal support.'],
    ['name' => 'NNC - Nutrition Alert System', 'description' => 'Malnutrition monitoring and intervention.'],
    ['name' => 'POPCOM - Responsible Parenthood', 'description' => 'Family planning and reproductive health.'],
    ['name' => 'NCIP - Ancestral Domain Titling', 'description' => 'Land titles for indigenous peoples.'],
    ['name' => 'NCDA - PWD Livelihood', 'description' => 'Employment for persons with disability.'],
    ['name' => 'OSEC - Senior Citizen Programs', 'description' => 'Social pension and health support.'],
    ['name' => 'PCW - VAWC Safe Spaces', 'description' => 'Support for violence against women survivors.'],
    ['name' => 'PIDS - Policy Research', 'description' => 'Evidence-based policy development.'],
    ['name' => 'LBP - Agrarian Production Credit', 'description' => 'Agricultural financing.'],
];

// Filipino names - expanded
const FIRST_NAMES = [
    'Maria', 'Juan', 'Jose', 'Pedro', 'Ana', 'Rosa', 'Miguel', 'Antonio', 'Carmen', 'Francisco',
    'Teresa', 'Ramon', 'Pilar', 'Fernando', 'Lourdes', 'Eduardo', 'Manuel', 'Rita', 'Ricardo', 'Gloria',
    'Roberto', 'Luz', 'Carlos', 'Consuelo', 'Alberto', 'Rizal', 'Felipa', 'Andres', 'Corazon', 'Emilio',
    'Amor', 'Bayani', 'Dalisay', 'Lakandula', 'Maganda', 'Sampaguita', 'Tagumpay', 'Ligaya', 'Ding', 'Bong',
    'Cristina', 'Marco', 'Paulo', 'Isabel', 'Gabriel', 'Sofia', 'Alejandro', 'Beatriz', 'Diego', 'Elena',
    'Felipe', 'Graciela', 'Hector', 'Imelda', 'Javier', 'Katrina', 'Leonardo', 'Margarita', 'Nestor', 'Olivia',
    'Pablo', 'Querida', 'Rodrigo', 'Sylvia', 'Tomas', 'Ursula', 'Vicente', 'Wilma', 'Xavier', 'Yolanda',
    'Zenaida', 'Arnel', 'Bianca', 'Cecilio', 'Diana', 'Emmanuel', 'Florante', 'Gina', 'Hernando', 'Ivy',
    'Jaime', 'Katherine', 'Lorenzo', 'Melinda', 'Nathaniel', 'Ophelia', 'Patricio', 'Queenie', 'Rolando', 'Salvador',
    'Teodora', 'Urban', 'Virginia', 'Winston', 'Yvette', 'Zandro', 'Adrian', 'Brenda', 'Clemente', 'Donna',
];
const SURNAMES = [
    'Reyes', 'Santos', 'Cruz', 'Bautista', 'Garcia', 'Ramos', 'Mendoza', 'Villanueva', 'Dela Cruz', 'Torres',
    'Fernandez', 'Gonzalez', 'Diaz', 'Castillo', 'Sanchez', 'Romero', 'Flores', 'Rivera', 'Aquino', 'Magsaysay',
    'Bonifacio', 'Luna', 'Rizal', 'Marcos', 'Estrada', 'Duterte', 'Macapagal', 'Osmeña', 'Quezon', 'Laurel',
    'Roxas', 'Quirino', 'Magsaysay', 'Garcia', 'Maceda', 'Enrile', 'Angara', 'Cayetano', 'Villar', 'Pimentel',
    'Drilon', 'Sotto', 'Pacquiao', 'Binay', 'Ejercito', 'Recto', 'Honasan', 'Lacson', 'Trillanes', 'Gordon',
    'Go', 'Zubiri', 'Gatchalian', 'Tolentino', 'Pangilinan', 'Hontiveros', 'De Lima', 'Legarda', 'Revilla', 'Escudero',
    'Abad', 'Abalos', 'Alvarez', 'Andaya', 'Aumentado', 'Belmonte', 'Caguioa', 'Calida', 'Cayetano', 'Diokno',
    'Domagoso', 'Duque', 'Guevarra', 'Lapeña', 'Lorenzana', 'Lopez', 'Panelo', 'Peralta', 'Roque', 'Tugade',
    'Yap', 'Yuchengco', 'Ayala', 'Zobel', 'Aboitiz', 'Gokongwei', 'Tan', 'Sy', 'Cojuangco', 'Araneta',
];

// Structure tags and descriptions - expanded
const STRUCTURE_TAG_PREFIXES = ['RES', 'BLDG', 'LOT', 'UNIT', 'SITE', 'BLK', 'BLOCK', 'PHASE', 'ZONE', 'PARCEL', 'LOT', 'STR', 'BLD', 'APT', 'RM'];
const STRUCTURE_TAG_SUFFIXES = ['A', 'B', 'C', '1', '2', '3', 'N', 'S', 'E', 'W', '01', '02', '03', 'N1', 'S1'];
const DESCRIPTIONS = [
    'Residential unit - concrete hollow block construction, 24 sqm floor area',
    'Semi-permanent structure with galvanized iron roof, typhoon-resistant',
    'Relocation housing unit - core shelter assistance program beneficiary',
    'Core shelter assistance - typhoon-resistant design, 18 sqm',
    'Community facility - multipurpose hall for barangay assemblies',
    'Livelihood center - shared workspace for SLP beneficiaries',
    'Single-detached housing unit - NHA resettlement site',
    'Row house unit - socialized housing project',
    'Duplex unit - housing for informal settler families',
    'Multi-purpose building - daycare and health station',
    'Evacuation center - disaster-resistant structure',
    'Water system facility - Level II water supply',
    'Sanitation facility - communal toilet and bath',
    'Day care center - ECCD program building',
    'Barangay health station - BHS with birthing facility',
    'Senior citizen center - OSCA office and activity hall',
    'Youth development center - SK and out-of-school youth programs',
    'Agricultural storage - warehouse for farm produce',
    'Livelihood training center - TESDA-accredited facility',
    'Solar-powered street lighting - renewable energy initiative',
    'Farm-to-market road - concrete paving',
    'Flood control structure - riprap and retaining wall',
    'Irrigation canal - NIA-assisted system',
    'School building - DepEd Last Mile Schools',
    'Botika ng Barangay - affordable medicines outlet',
    'Gulayan sa Paaralan - school vegetable garden shed',
];
const OTHER_DETAILS = [
    'Beneficiary under Philippine Government social housing program.',
    'KALAHI-CIDSS community sub-project.',
    '4Ps program participant - housing component.',
    'NHA off-city resettlement recipient.',
    'DSWD Core Shelter Assistance Program.',
    'LGU-assisted community development project.',
    'Pag-IBIG affordable housing loan beneficiary.',
];

function ensureUploadDirs(): void
{
    foreach (['tagging', 'images'] as $subdir) {
        $dir = UPLOAD_BASE . '/' . $subdir;
        if (!is_dir($dir)) mkdir($dir, 0755, true);
    }
}

function createPlaceholderImage(string $subdir, string $filename): string
{
    $dir = UPLOAD_BASE . '/' . $subdir;
    $path = $dir . '/' . $filename;
    if (file_exists($path)) return WEB_BASE . '/' . $subdir . '/' . $filename;
    if (!function_exists('imagecreatetruecolor')) {
        throw new \RuntimeException('GD extension required. Install php-gd.');
    }
    $img = imagecreatetruecolor(200, 200);
    if (!$img) throw new \RuntimeException('Failed to create image.');
    $colors = [0x4A90D9, 0x7CB342, 0xFFB74D, 0xE57373, 0xBA68C8, 0x64B5F6, 0x81C784, 0xFFD54F];
    $bg = $colors[array_rand($colors)];
    imagefill($img, 0, 0, $bg);
    $white = imagecolorallocate($img, 255, 255, 255);
    imagestring($img, 3, 50, 95, 'PAPS', $white);
    imagestring($img, 2, 40, 115, 'Structure', $white);
    imagepng($img, $path);
    imagedestroy($img);
    return WEB_BASE . '/' . $subdir . '/' . $filename;
}

/** Pre-create image pool to avoid millions of files. Returns [tagging_urls[], structure_urls[]] */
function buildImagePool(): array
{
    $tagging = [];
    $structure = [];
    $tagCount = max(50, TAGGING_IMAGES_PER_STRUCTURE * 2);
    $structCount = max(50, STRUCTURE_IMAGES_PER_STRUCTURE * 2);
    for ($i = 0; $i < $tagCount; $i++) {
        $tagging[] = createPlaceholderImage('tagging', 'pool_tag_' . $i . '_' . uniqid() . '.png');
    }
    for ($i = 0; $i < $structCount; $i++) {
        $structure[] = createPlaceholderImage('images', 'pool_img_' . $i . '_' . uniqid() . '.png');
    }
    return [$tagging, $structure];
}

function generateStructureImages(array $tagPool, array $structPool): array
{
    $taggingPaths = [];
    $structurePaths = [];
    for ($i = 0; $i < TAGGING_IMAGES_PER_STRUCTURE; $i++) {
        $taggingPaths[] = $tagPool[array_rand($tagPool)];
    }
    for ($i = 0; $i < STRUCTURE_IMAGES_PER_STRUCTURE; $i++) {
        $structurePaths[] = $structPool[array_rand($structPool)];
    }
    return [
        'tagging_images' => json_encode($taggingPaths),
        'structure_images' => json_encode($structurePaths),
    ];
}

function randomElement(array $arr) { return $arr[array_rand($arr)]; }

function randomPhone(): string { return '09' . str_pad((string) random_int(100000000, 999999999), 9, '0'); }

function randomStructureTag(int $seq): string
{
    return randomElement(STRUCTURE_TAG_PREFIXES) . '-' . randomElement(STRUCTURE_TAG_SUFFIXES) . '-' . str_pad((string) $seq, 4, '0');
}

// --- Main ---
echo "Philippine Government Projects Seeder\n";
echo "=====================================\n";
echo "Profiles: " . number_format(PROFILE_COUNT) . " | Structures/profile: " . STRUCTURES_PER_PROFILE . " | Images/structure: " . TAGGING_IMAGES_PER_STRUCTURE . " tagging + " . STRUCTURE_IMAGES_PER_STRUCTURE . " structure\n";
echo "Projects: " . TARGET_PROJECT_COUNT . "\n\n";

$db = Database::getInstance();

// 1. Ensure 50 projects
$existingProjects = Project::all();
$projectIds = array_map(fn($p) => $p->id, $existingProjects);

if (count($projectIds) < TARGET_PROJECT_COUNT) {
    $toCreate = TARGET_PROJECT_COUNT - count($projectIds);
    echo "Creating $toCreate Philippine Government Project(s)...\n";
    for ($i = 0; $i < $toCreate && $i < count(PROJECTS); $i++) {
        $projectIds[] = Project::create(PROJECTS[$i]);
    }
    echo "  Total projects: " . count($projectIds) . "\n";
}
$projectIds = array_slice($projectIds, 0, TARGET_PROJECT_COUNT);
echo "Assigning profiles to " . count($projectIds) . " projects.\n";

ensureUploadDirs();
echo "Building image pool...\n";
[$tagPool, $structPool] = buildImagePool();
echo "  Tagging pool: " . count($tagPool) . " | Structure pool: " . count($structPool) . "\n";

echo "\nCreating " . number_format(PROFILE_COUNT) . " profiles in batches of " . BATCH_SIZE . " (" . (PROFILE_COUNT / BATCH_SIZE) . " batches)...\n";

$createdProfiles = 0;
$createdStructures = 0;
$existingProfiles = (int) $db->query("SELECT COUNT(*) FROM eav_entities WHERE entity_type = 'profile'")->fetchColumn();
$ctrlNumStart = 100000 + $existingProfiles;
$totalBatches = (int) ceil(PROFILE_COUNT / BATCH_SIZE);

for ($batch = 0; $batch < $totalBatches; $batch++) {
    $batchStart = $batch * BATCH_SIZE;
    $batchCount = min(BATCH_SIZE, PROFILE_COUNT - $batchStart);

    for ($i = 0; $i < $batchCount; $i++) {
        $idx = $batchStart + $i;
        $firstName = randomElement(FIRST_NAMES);
        $surname = randomElement(SURNAMES);
        $fullName = $firstName . ' ' . $surname;
        $projectId = randomElement($projectIds);

        $profileId = Profile::create([
            'control_number' => 'CTRL-' . ($ctrlNumStart + $idx),
            'full_name' => $fullName,
            'age' => random_int(18, 75),
            'contact_number' => randomPhone(),
            'project_id' => $projectId,
        ]);
        $createdProfiles++;

        for ($s = 0; $s < STRUCTURES_PER_PROFILE; $s++) {
            $imgs = generateStructureImages($tagPool, $structPool);
            Structure::create([
                'owner_id' => $profileId,
                'structure_tag' => randomStructureTag($s + 1),
                'description' => randomElement(DESCRIPTIONS),
                'tagging_images' => $imgs['tagging_images'],
                'structure_images' => $imgs['structure_images'],
                'other_details' => randomElement(OTHER_DETAILS),
            ]);
            $createdStructures++;
        }
    }

    echo "  Batch " . ($batch + 1) . "/" . $totalBatches . ": " . number_format($createdProfiles) . " profiles, " . number_format($createdStructures) . " structures\n";
}

echo "\nDone!\n";
echo "  Profiles created: " . number_format($createdProfiles) . "\n";
echo "  Structures created: " . number_format($createdStructures) . "\n";
