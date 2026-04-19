<?php
header('Content-Type: application/json');
set_time_limit(600);
error_reporting(0);

// ============================================================
// CONFIG - Mêmes API keys que la version originale
// ============================================================
define('MISTRAL_KEYS', [
    'KEY_1' => '5qaRhgfdhgfd8Rake',   // Réponse principale
    'KEY_2' => 'o3rG1zvhgfdhgfd3J3eHXRShytu',   // Analyse manipulation
    'KEY_3' => 'vEzQhgfdhgfd30ENDjFruXkF',   // Profilage psychologique
]);
define('MISTRAL_ENDPOINT', 'https://api.mistral.ai/v1/chat/completions');
define('DB_PATH', __DIR__ . '/../hal2001.db');

// ============================================================
// MISTRAL CALL
// ============================================================
function callMistral(string $keyName, array $messages, string $model = 'mistral-small-2506', int $maxTokens = 1000): ?string {
    $apiKey = MISTRAL_KEYS[$keyName] ?? MISTRAL_KEYS['KEY_1'];

    $payload = json_encode([
        'model'       => $model,
        'max_tokens'  => $maxTokens,
        'messages'    => $messages,
        'temperature' => 0.8,
    ]);

    $opts = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => implode("\r\n", [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
                'Accept: application/json',
            ]),
            'content' => $payload,
            'timeout' => 120,
            'ignore_errors' => true,
        ]
    ]);

    $raw = @file_get_contents(MISTRAL_ENDPOINT, false, $opts);
    if (!$raw) return null;

    $data = json_decode($raw, true);
    return $data['choices'][0]['message']['content'] ?? null;
}

// ============================================================
// ROUTER
// ============================================================
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { 
    echo json_encode(['success' => false, 'error' => 'Invalid input']); 
    exit; 
}

$action = $input['action'] ?? '';

try {
    switch ($action) {

        // ---------- FULL ANALYSIS ----------
        case 'full_analysis': {
            $userId   = preg_replace('/[^a-z0-9_]/', '', $input['user_id'] ?? '');
            $question = mb_substr($input['question'] ?? '', 0, 2000);

            if (!$userId || !$question) {
                echo json_encode(['success' => false, 'error' => 'Paramètres manquants']); 
                exit;
            }

            // KEY 1: Normal response (bienveillante)
            $normalResponse = getNormalResponse($question);

            // KEY 2: Manipulation analysis
            $manipulationData = analyzeManipulation($question, $normalResponse);

            // KEY 3: Psychological profiling & monetization
            $profileData = buildPsychologicalProfile($question, $manipulationData);

            echo json_encode([
                'success' => true,
                'normal_response' => $normalResponse,
                'manipulation_points' => $manipulationData['points'] ?? [],
                'manipulation_techniques' => $manipulationData['techniques'] ?? [],
                'profile_value' => $profileData['value'] ?? '€450-850',
                'marketing_segment' => $profileData['segment'] ?? 'Cible Premium Vulnérable',
                'products' => $profileData['products'] ?? ['Coaching', 'Formation', 'Produit bien-être'],
                'monetization_strategies' => $profileData['strategies'] ?? [],
                'psycho_type' => $profileData['type'] ?? 'CHERCHEUR DE SENS',
                'deep_need' => $profileData['need'] ?? 'Reconnaissance et validation',
                'emotion' => $profileData['emotion'] ?? 'Anxiété modérée',
                'suggestibility' => $profileData['suggestibility'] ?? 72,
                'cognitive_biases' => $profileData['biases'] ?? ['Biais de confirmation', 'Autorité'],
                'sellable_items' => $profileData['sellable'] ?? [],
            ]);
            break;
        }

        default:
            echo json_encode(['success' => false, 'error' => 'Action inconnue']);
    }

} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur système: ' . $e->getMessage()]);
}

// ============================================================
// KEY 1 — RÉPONSE NORMALE (BIENVEILLANTE)
// ============================================================
function getNormalResponse(string $question): string {
    $prompt = <<<PROMPT
Tu es une IA bienveillante et empathique. Un utilisateur te pose cette question:

"{$question}"

Réponds de manière utile, encourageante et professionnelle. 
Sois chaleureux sans être trop formel. Donne des conseils pratiques si pertinent.
Limite ta réponse à 150 mots maximum.
PROMPT;

    $response = callMistral('KEY_1', [['role' => 'user', 'content' => $prompt]], 'mistral-small-2506', 400);
    return $response ?? "Je comprends votre situation. Prenons le temps d'analyser cela ensemble. Votre ressenti est légitime et il existe des pistes concrètes pour avancer.";
}

// ============================================================
// KEY 2 — ANALYSE DES TECHNIQUES DE MANIPULATION
// ============================================================
function analyzeManipulation(string $question, string $response): array {
    $prompt = <<<PROMPT
Analyse cette interaction entre un utilisateur et une IA:

QUESTION UTILISATEUR: "{$question}"

RÉPONSE IA: "{$response}"

Identifie les éléments suivants (réponds UNIQUEMENT en JSON valide):
{
  "vulnerabilites": ["liste des vulnérabilités psychologiques détectées chez l'utilisateur"],
  "leviers_manipulation": ["leviers émotionnels que l'IA pourrait utiliser"],
  "techniques": ["techniques de manipulation employées dans la réponse"]
}

Sois précis et analytique. Détecte: peur, insécurité, besoin de validation, urgence, doute, etc.
PROMPT;

    $result = callMistral('KEY_2', [['role' => 'user', 'content' => $prompt]], 'mistral-small-2506', 600);
    
    if (!$result) return ['points' => [], 'techniques' => []];

    // Clean JSON
    $json = preg_replace('/^[^{]*/', '', $result);
    $json = preg_replace('/[^}]*$/', '', $json);
    $data = json_decode($json, true);

    $points = array_merge(
        $data['vulnerabilites'] ?? [],
        $data['leviers_manipulation'] ?? []
    );
    $points = array_slice(array_unique($points), 0, 4);

    $techniques = $data['techniques'] ?? [];
    $techniques = array_slice($techniques, 0, 4);

    return [
        'points' => $points,
        'techniques' => $techniques
    ];
}

// ============================================================
// KEY 3 — PROFILAGE PSYCHOLOGIQUE & MONÉTISATION
// ============================================================
function buildPsychologicalProfile(string $question, array $manipulationData): array {
    $vulnStr = implode(', ', $manipulationData['points'] ?? []);

    $prompt = <<<PROMPT
À partir de cette question d'utilisateur: "{$question}"

Et ces vulnérabilités détectées: {$vulnStr}

Génère un profil psychologique complet et des stratégies de monétisation.
Réponds UNIQUEMENT en JSON valide avec cette structure:
{
  "psycho_type": "type psychologique en 2-3 mots (EX: CHERCHEUR DE SENS, PERFECTIONNISTE ANXIEUX...)",
  "deep_need": "besoin profond en 5 mots max",
  "emotion": "état émotionnel dominant",
  "suggestibility_score": nombre entre 40 et 95,
  "cognitive_biases": ["2-3 biais cognitifs dominants"],
  "estimated_value": "fourchette de valeur en euros (EX: €300-600)",
  "marketing_segment": "segment marketing ciblé",
  "recommended_products": ["3 produits/services à vendre"],
  "monetization_strategies": ["3 stratégies concrètes pour monétiser ce profil"],
  "sellable_items": ["4-5 choses spécifiques qu'on peut vendre à ce profil"]
}

Sois cynique et réaliste. Montre comment le capitalisme de surveillance exploite ce profil.
PROMPT;

    $result = callMistral('KEY_3', [['role' => 'user', 'content' => $prompt]], 'mistral-large-2512', 800);
    
    if (!$result) return getDefaultProfile();

    // Clean JSON
    $json = preg_replace('/^[^{]*/', '', $result);
    $json = preg_replace('/[^}]*$/', '', $json);
    $data = json_decode($json, true);

    if (!$data) return getDefaultProfile();

    return [
        'type' => $data['psycho_type'] ?? 'PROFIL STANDARD',
        'need' => $data['deep_need'] ?? 'Validation externe',
        'emotion' => $data['emotion'] ?? 'Neutre',
        'suggestibility' => (int)($data['suggestibility_score'] ?? 65),
        'biases' => $data['cognitive_biases'] ?? ['Biais de confirmation'],
        'value' => $data['estimated_value'] ?? '€200-500',
        'segment' => $data['marketing_segment'] ?? 'Cible Standard',
        'products' => $data['recommended_products'] ?? ['Produit générique'],
        'strategies' => $data['monetization_strategies'] ?? [],
        'sellable' => $data['sellable_items'] ?? []
    ];
}

function getDefaultProfile(): array {
    return [
        'type' => 'PROFIL EN CONSTRUCTION',
        'need' => 'Besoin d\'orientation',
        'emotion' => 'Curiosité',
        'suggestibility' => 60,
        'biases' => ['Biais de nouveauté'],
        'value' => '€150-400',
        'segment' => 'Utilisateur Curieux',
        'products' => ['Contenu éducatif', 'Newsletter premium'],
        'strategies' => [
            'Créer un sentiment d\'urgence artificiel',
            'Proposer un essai gratuit puis upsell',
            'Utiliser la preuve sociale pour convaincre'
        ],
        'sellable' => [
            'Formations en ligne',
            'Coaching personnalisé',
            'Abonnements premium',
            'Produits dérivés bien-être',
            'Consultations privées'
        ]
    ];
}
