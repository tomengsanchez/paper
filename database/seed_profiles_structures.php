<?php
/**
 * Seeder: Profiles, Structures, Projects (Philippine Government Programs)
 * Compatible with flat database structure.
 *
 * Run from project root: php database/seed_profiles_structures.php
 *
 * Config variables below control seed volumes.
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Models\Profile;
use App\Models\Structure;
use App\Models\Project;
use Core\Database;

// ============== SEED CONFIG ==============
const SEED_PROFILE_COUNT = 100000;
const SEED_STRUCTURES_MIN = 5;
const SEED_STRUCTURES_MAX = 20;
const SEED_PROJECT_COUNT = 50;
const SEED_BATCH_SIZE = 50;
const SEED_TAGGING_IMAGES = 8;
const SEED_STRUCTURE_IMAGES = 10;
// ========================================

const UPLOAD_BASE = __DIR__ . '/../public/uploads/structure';
const WEB_BASE = '/uploads/structure';

// Philippine Government Programs - expanded
const PROJECTS = [
    ['name' => '4Ps - Pantawid Pamilyang Pilipino Program', 'description' => 'Conditional cash transfer for health and education of poor households. Covers 4.4M families nationwide.'],
    ['name' => 'KALAHI-CIDSS', 'description' => 'Kapit-Bisig Laban sa Kahirapan - Community-driven development and infrastructure in conflict-affected and poor municipalities.'],
    ['name' => 'SLP - Sustainable Livelihood Program', 'description' => 'Micro-enterprise and employment facilitation for marginalized families. Skills training and capital assistance.'],
    ['name' => 'NHA Resettlement Program', 'description' => 'National Housing Authority - Relocation and housing for informal settler families along danger zones and waterways.'],
    ['name' => 'TUPAD - Tulong Panghanapbuhay sa Ating Disadvantaged/Displaced Workers', 'description' => 'Emergency employment for displaced, seasonal, and underemployed workers. Short-term community work.'],
    ['name' => 'BALAI - Balik Probinsya, Bagong Pag-asa', 'description' => 'Decongestion of Metro Manila through provincial relocation incentives, housing, and livelihood support.'],
    ['name' => 'PAMANA - Payapa at Masaganang Pamayanan', 'description' => 'Peace and development in conflict-affected areas. OPAPP-managed infrastructure and livelihood.'],
    ['name' => 'BUB - Bottom-Up Budgeting', 'description' => 'Participatory budgeting for local government projects. CSO-identified priority projects.'],
    ['name' => 'CCT - Conditional Cash Transfer', 'description' => 'Financial assistance conditional on health check-ups, school attendance, and family development sessions.'],
    ['name' => 'CHTs - Community Health Teams', 'description' => 'Barangay health workers deployment. Primary care, maternal health, immunization in rural areas.'],
    ['name' => 'CSF - Core Shelter Assistance Program', 'description' => 'Typhoon-resistant housing for disaster victims. DSWD-administered, LGU-implemented.'],
    ['name' => 'DA - Kapatid Angat Lahat', 'description' => 'Department of Agriculture agribusiness development. Links MSMEs to big corporations.'],
    ['name' => 'DILG - Sagana at Ligtas na Tubig sa Lahat', 'description' => 'Water supply systems for rural communities. Level I, II, III systems.'],
    ['name' => 'DOST - Small Enterprise Technology Upgrading Program', 'description' => 'Technology transfer for MSMEs. SETUP provides equipment and technical consultancy.'],
    ['name' => 'DOLE - Special Program for Employment of Students', 'description' => 'Student summer employment. Minimum wage for 20-52 days during break.'],
    ['name' => 'DPWH - Farm-to-Market Roads', 'description' => 'Rural road networks for agricultural areas. Connects farms to markets and towns.'],
    ['name' => 'DSWD - Supplementary Feeding Program', 'description' => 'Nutrition support for day care children. Hot meals, micronutrient supplementation.'],
    ['name' => 'DTI - Shared Service Facilities', 'description' => 'Common facilities for MSME clusters. Processing, packaging, cold storage.'],
    ['name' => 'DENR - National Greening Program', 'description' => 'Reforestation and watershed rehabilitation. 1.5B trees target across 1.5M hectares.'],
    ['name' => 'DepEd - Last Mile Schools', 'description' => 'Schools in geographically isolated and disadvantaged areas. Multi-grade, Indigenous Peoples.'],
    ['name' => 'DOH - Bakuna Centers', 'description' => 'Vaccination and health centers. Immunization, maternal, child health services.'],
    ['name' => 'HLURB - Socialized Housing', 'description' => 'Affordable housing for informal settlers. Amortization support, developer incentives.'],
    ['name' => 'Pag-IBIG - Affordable Housing Program', 'description' => 'Housing loans for minimum wage earners. Developmental housing, community development.'],
    ['name' => 'PhilHealth - Indigent Program', 'description' => 'Health insurance for poor families. No-premium coverage, hospital benefits.'],
    ['name' => 'SSS - Flexi-Fund', 'description' => 'Social security for informal sector. Voluntary contributions, sickness, maternity, retirement.'],
    ['name' => 'TESDA - Training for Work Scholarship', 'description' => 'Technical-vocational skills training. Free tuition, assessment, certification.'],
    ['name' => 'LGU - BPLS Streamlining', 'description' => 'Business permits and licensing simplification. 3-7-20 standard, digital processing.'],
    ['name' => 'DICT - Free Wi-Fi in Public Places', 'description' => 'Internet access in plazas, parks, terminals, government offices nationwide.'],
    ['name' => 'PCC - Kapatid Mentor Me', 'description' => 'Mentoring for micro entrepreneurs. Big business mentors MSMEs on operations, marketing.'],
    ['name' => 'BFAR - Fish Landing Centers', 'description' => 'Port facilities for artisanal fishermen. Ice plants, cold storage, trading posts.'],
    ['name' => 'PCA - Coconut Farmers Rehabilitation', 'description' => 'Support for coconut industry recovery. Replanting, intercropping, processing.'],
    ['name' => 'DAR - Agrarian Reform Community Development', 'description' => 'Infrastructure in agrarian reform areas. Irrigation, farm-to-market roads, post-harvest.'],
    ['name' => 'NIA - National Irrigation Systems', 'description' => 'Irrigation for rainfed farmlands. Communal, national systems, small reservoir.'],
    ['name' => 'PhilMech - Farm Machinery Distribution', 'description' => 'Mechanization support for farmers. Four-wheel tractors, harvesters, dryers.'],
    ['name' => 'DA - Rice Competitiveness Enhancement Fund', 'description' => 'Seeds, credit, and equipment for rice farmers. RCEF from rice tariff.'],
    ['name' => 'DepEd - Gulayan sa Paaralan', 'description' => 'School-based vegetable gardens. Nutrition, agriculture education, feeding.'],
    ['name' => 'DOH - Botika ng Barangay', 'description' => 'Affordable medicines in communities. Generic drugs, essential medicines list.'],
    ['name' => 'DILG - Seal of Good Local Governance', 'description' => 'Incentives for high-performing LGUs. Financial rewards, capacity building.'],
    ['name' => 'DSWD - Kapit-Bisig sa Pag-unlad', 'description' => 'Community-based poverty reduction. CBPRRM, KALAHI convergence.'],
    ['name' => 'OWWA - Reintegration Program', 'description' => 'OFW livelihood upon return. Skills training, entrepreneurship, job placement.'],
    ['name' => 'CHED - Tulong Dunong', 'description' => 'Scholarship for indigent students. Tertiary education support.'],
    ['name' => 'PRC - Continuing Professional Development', 'description' => 'License renewal support for professionals. CPD units, seminars.'],
    ['name' => 'NNC - Nutrition Alert System', 'description' => 'Malnutrition monitoring and intervention. OPT, growth monitoring.'],
    ['name' => 'POPCOM - Responsible Parenthood and Reproductive Health', 'description' => 'Family planning and reproductive health. FP commodities, adolescent health.'],
    ['name' => 'NCIP - Ancestral Domain Titling', 'description' => 'Land titles for indigenous peoples. CADT, CALT, FPIC processes.'],
    ['name' => 'NCDA - PWD Livelihood', 'description' => 'Employment for persons with disability. Skills training, job placement, assistive devices.'],
    ['name' => 'OSEC - Social Pension for Indigent Senior Citizens', 'description' => 'Monthly stipend for senior citizens. 60+ years, no pension.'],
    ['name' => 'PCW - VAWC Safe Spaces', 'description' => 'Support for violence against women survivors. Counseling, shelter, legal aid.'],
    ['name' => 'PIDS - Policy Research', 'description' => 'Evidence-based policy development. Socioeconomic research for government.'],
    ['name' => 'LBP - Agrarian Production Credit', 'description' => 'Agricultural financing. Land Bank credit for farmers, cooperatives.'],
    ['name' => 'DBP - Priority Programs Lending', 'description' => 'Development Bank financing. SME, agriculture, infrastructure.'],
    ['name' => 'PSA - Community-Based Monitoring System', 'description' => 'Localized poverty statistics. Barangay-level data for planning.'],
    ['name' => 'DND - AFP Housing', 'description' => 'Military housing for uniformed personnel. PAF, PN, PA housing projects.'],
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
    'Almira', 'Bienvenido', 'Corazon', 'Domingo', 'Esperanza', 'Fortunato', 'Generoso', 'Herminia', 'Ignacio', 'Juliana',
    'Lamberto', 'Marcela', 'Nicanor', 'Ofelia', 'Pantaleon', 'Remedios', 'Simeon', 'Teofilo', 'Valentina', 'Wilfredo',
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
    'Alcantara', 'Bautista', 'Calderon', 'Dizon', 'Espiritu', 'Fajardo', 'Gutierrez', 'Herrera', 'Imperial', 'Jacinto',
    'Kintanar', 'Lazaro', 'Magbanua', 'Navarro', 'Ocampo', 'Pascual', 'Quizon', 'Reyes', 'Sison', 'Tolentino',
    'Uy', 'Velasco', 'Wong', 'Ybanez', 'Zamora', 'Advincula', 'Beltran', 'Carpio', 'Dela Rosa', 'Evangelista',
];

// Structure tags - wider variety
const STRUCTURE_TAG_PREFIXES = ['RES', 'BLDG', 'LOT', 'UNIT', 'SITE', 'BLK', 'BLOCK', 'PHASE', 'ZONE', 'PARCEL', 'STR', 'BLD', 'APT', 'RM', 'HQ', 'FAC', 'CTR', 'STN', 'BRGY', 'PNB'];
const STRUCTURE_TAG_SUFFIXES = ['A', 'B', 'C', 'D', '1', '2', '3', '4', 'N', 'S', 'E', 'W', '01', '02', '03', 'N1', 'S1', 'E1', 'W1', 'NE', 'SW'];

// Descriptions - wider variety
const DESCRIPTIONS = [
    'Residential unit - concrete hollow block construction, 24 sqm floor area, single occupancy',
    'Semi-permanent structure with galvanized iron roof, typhoon-resistant design per DSWD specs',
    'Relocation housing unit - core shelter assistance program beneficiary, NHA standard',
    'Core shelter assistance - typhoon-resistant design 18 sqm, CFS beneficiary',
    'Community facility - multipurpose hall for barangay assemblies and events',
    'Livelihood center - shared workspace for SLP beneficiaries, TESDA-accredited',
    'Single-detached housing unit - NHA resettlement site, off-city relocation',
    'Row house unit - socialized housing project, 22 sqm floor area',
    'Duplex unit - housing for informal settler families, LGU-assisted',
    'Multi-purpose building - daycare and health station, DepEd-DOH convergence',
    'Evacuation center - disaster-resistant structure, Oplan Listo compliant',
    'Water system facility - Level II water supply, DILG SaLT program',
    'Sanitation facility - communal toilet and bath, Zero Open Defecation',
    'Day care center - ECCD program building, DSWD-accredited',
    'Barangay health station - BHS with birthing facility, DOH standard',
    'Senior citizen center - OSCA office and activity hall',
    'Youth development center - SK and out-of-school youth programs',
    'Agricultural storage - warehouse for farm produce, DA post-harvest facility',
    'Livelihood training center - TESDA-accredited facility',
    'Solar-powered street lighting - renewable energy, DOST-assisted',
    'Farm-to-market road - concrete paving, DPWH-BFAR convergence',
    'Flood control structure - riprap and retaining wall, DPWH project',
    'Irrigation canal - NIA-assisted system, communal irrigation',
    'School building - DepEd Last Mile Schools, three-classroom',
    'Botika ng Barangay - affordable medicines outlet, DOH-accredited',
    'Gulayan sa Paaralan - school vegetable garden shed and nursery',
    'KALAHI-CIDSS sub-project - community-identified priority infrastructure',
    '4Ps housing component - CCT beneficiary with core shelter',
    'Pag-IBIG developmental housing - amortization-assisted unit',
    'TUPAD work site - emergency employment project area',
];

// Other details - wider variety
const OTHER_DETAILS = [
    'Beneficiary under Philippine Government social housing program.',
    'KALAHI-CIDSS community sub-project. CDD approach.',
    '4Ps program participant - housing component.',
    'NHA off-city resettlement recipient.',
    'DSWD Core Shelter Assistance Program. CFS standard.',
    'LGU-assisted community development project.',
    'Pag-IBIG affordable housing loan beneficiary.',
    'TUPAD emergency employment project site.',
    'SLP Sustainable Livelihood Program beneficiary.',
    'PAMANA peace and development project.',
    'BALAI Balik Probinsya recipient.',
    'DAR Agrarian Reform Community infrastructure.',
    'TESDA skills training facility.',
    'DA farm-to-market road convergence project.',
    'DOH Botika ng Barangay outlet.',
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

function buildImagePool(): array
{
    $tagging = [];
    $structure = [];
    $tagCount = max(80, SEED_TAGGING_IMAGES * 3);
    $structCount = max(80, SEED_STRUCTURE_IMAGES * 3);
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
    for ($i = 0; $i < SEED_TAGGING_IMAGES; $i++) {
        $taggingPaths[] = $tagPool[array_rand($tagPool)];
    }
    for ($i = 0; $i < SEED_STRUCTURE_IMAGES; $i++) {
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
$structRange = SEED_STRUCTURES_MIN . '-' . SEED_STRUCTURES_MAX;
echo "Philippine Government Projects Seeder\n";
echo "=====================================\n";
echo "Profiles: " . number_format(SEED_PROFILE_COUNT) . " | Structures/profile: $structRange (random) | Projects: " . SEED_PROJECT_COUNT . "\n";
echo "Images/structure: " . SEED_TAGGING_IMAGES . " tagging + " . SEED_STRUCTURE_IMAGES . " structure\n\n";

$db = Database::getInstance();

// 1. Ensure projects
$existingProjects = Project::all();
$projectIds = array_map(fn($p) => $p->id, $existingProjects);

if (count($projectIds) < SEED_PROJECT_COUNT) {
    $toCreate = SEED_PROJECT_COUNT - count($projectIds);
    echo "Creating $toCreate Philippine Government Project(s)...\n";
    for ($i = 0; $i < $toCreate && $i < count(PROJECTS); $i++) {
        $projectIds[] = Project::create(PROJECTS[$i]);
    }
    echo "  Total projects: " . count($projectIds) . "\n";
}
$projectIds = array_slice($projectIds, 0, SEED_PROJECT_COUNT);
echo "Assigning profiles randomly to " . count($projectIds) . " projects.\n";

ensureUploadDirs();
echo "Building image pool...\n";
[$tagPool, $structPool] = buildImagePool();
echo "  Tagging pool: " . count($tagPool) . " | Structure pool: " . count($structPool) . "\n";

echo "\nCreating " . number_format(SEED_PROFILE_COUNT) . " profiles in batches of " . SEED_BATCH_SIZE . "...\n";

$createdProfiles = 0;
$createdStructures = 0;
$existingProfiles = (int) $db->query("SELECT COUNT(*) FROM profiles")->fetchColumn();
$ctrlNumStart = 100000 + $existingProfiles;
$totalBatches = (int) ceil(SEED_PROFILE_COUNT / SEED_BATCH_SIZE);

for ($batch = 0; $batch < $totalBatches; $batch++) {
    $batchStart = $batch * SEED_BATCH_SIZE;
    $batchCount = min(SEED_BATCH_SIZE, SEED_PROFILE_COUNT - $batchStart);

    $papsids = Profile::generatePAPSIDBatch($batchCount);
    $stridPoolSize = $batchCount * SEED_STRUCTURES_MAX;
    $stridPool = Structure::generateSTRIDBatch($stridPoolSize);
    $stridIdx = 0;

    for ($i = 0; $i < $batchCount; $i++) {
        $idx = $batchStart + $i;
        $firstName = randomElement(FIRST_NAMES);
        $surname = randomElement(SURNAMES);
        $fullName = $firstName . ' ' . $surname;
        $projectId = randomElement($projectIds);

        $profileId = Profile::createWithPapsid($papsids[$i] ?? Profile::generatePAPSID(), [
            'control_number' => 'CTRL-' . ($ctrlNumStart + $idx),
            'full_name' => $fullName,
            'age' => random_int(18, 75),
            'contact_number' => randomPhone(),
            'project_id' => $projectId,
        ]);
        $createdProfiles++;

        $structCount = random_int(SEED_STRUCTURES_MIN, SEED_STRUCTURES_MAX);
        for ($s = 0; $s < $structCount; $s++) {
            $imgs = generateStructureImages($tagPool, $structPool);
            $strid = $stridPool[$stridIdx] ?? Structure::generateSTRID();
            $stridIdx++;
            Structure::createWithStrid($strid, [
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
