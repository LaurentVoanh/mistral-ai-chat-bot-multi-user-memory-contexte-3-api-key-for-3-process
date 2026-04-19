<?php
header('Content-Type: application/json');
set_time_limit(600);
error_reporting(0);

// ============================================================
// CONFIG
// ============================================================
define('MISTRAL_KEYS', [
    'KEY_1' => '5qaRhgfdhgfd8Rake',   // Réponse principale
    'KEY_2' => 'o3rG1zvhgfdhgfd3J3eHXRShytu',   // RL / Apprentissage
    'KEY_3' => 'vEzQhgfdhgfd30ENDjFruXkF',   // Admin / Rapports
]);
define('MISTRAL_ENDPOINT', 'https://api.mistral.ai/v1/chat/completions');
define('DB_PATH', __DIR__ . '/hal2001.db');
define('ADMIN_KEY', 'hal_admin_9001');

// ============================================================
// DATABASE INIT
// ============================================================
function getDB(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA journal_mode=WAL');
    $pdo->exec('PRAGMA synchronous=NORMAL');
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id TEXT UNIQUE,
            sessions INTEGER DEFAULT 0,
            total_messages INTEGER DEFAULT 0,
            trust_score INTEGER DEFAULT 0,
            rl_cycles INTEGER DEFAULT 0,
            rl_score REAL DEFAULT 0,
            psycho_type TEXT DEFAULT 'INCONNU',
            deep_need TEXT DEFAULT '',
            emotion TEXT DEFAULT 'neutre',
            memory_tags TEXT DEFAULT '[]',
            topics TEXT DEFAULT '[]',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_seen DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS conversations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id TEXT,
            role TEXT,
            content TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS rl_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id TEXT,
            analysis TEXT,
            reward REAL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS admin_reports (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            report TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS sessions_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id TEXT,
            session_date DATE,
            message_count INTEGER DEFAULT 0
        );
    ");
    return $pdo;
}

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
// USER MANAGEMENT
// ============================================================
function getOrCreateUser(string $userId): array {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE user_id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $db->prepare('INSERT INTO users (user_id, sessions) VALUES (?, 1)')->execute([$userId]);
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return $user;
}

function updateUser(string $userId, array $fields): void {
    $db = getDB();
    $set = implode(', ', array_map(fn($k) => "$k = ?", array_keys($fields)));
    $vals = array_values($fields);
    $vals[] = $userId;
    $db->prepare("UPDATE users SET $set, last_seen = CURRENT_TIMESTAMP WHERE user_id = ?")->execute($vals);
}

function saveMessage(string $userId, string $role, string $content): void {
    $db = getDB();
    $db->prepare('INSERT INTO conversations (user_id, role, content) VALUES (?, ?, ?)')->execute([$userId, $role, $content]);

    // Update session log
    $today = date('Y-m-d');
    $existing = $db->prepare('SELECT id, message_count FROM sessions_log WHERE user_id = ? AND session_date = ?');
    $existing->execute([$userId, $today]);
    $row = $existing->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $db->prepare('UPDATE sessions_log SET message_count = ? WHERE id = ?')->execute([$row['message_count'] + 1, $row['id']]);
    } else {
        $db->prepare('INSERT INTO sessions_log (user_id, session_date, message_count) VALUES (?, ?, 1)')->execute([$userId, $today]);
        // Increment sessions count
        $db->prepare('UPDATE users SET sessions = sessions + 1 WHERE user_id = ?')->execute([$userId]);
    }
}

function getConversationHistory(string $userId, int $limit = 15): array {
    $db = getDB();
    $stmt = $db->prepare('SELECT role, content FROM conversations WHERE user_id = ? ORDER BY id DESC LIMIT ?');
    $stmt->execute([$userId, $limit]);
    return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
}

// ============================================================
// KEY 1 — RÉPONSE PRINCIPALE
// ============================================================
function getHalResponse(array $user, array $history, string $message, string $mode, ?array $customAi): string {
    $userId = $user['user_id'];
    $memTags = json_decode($user['memory_tags'] ?? '[]', true);
    $psycho  = $user['psycho_type'] ?? 'inconnu';
    $need    = $user['deep_need'] ?? '';
    $emotion = $user['emotion'] ?? 'neutre';

    // Build system prompt
    if ($customAi) {
        $sysPrompt = buildCustomAiPrompt($customAi, $user);
    } else {
        $sysPrompt = buildHalSystemPrompt($mode, $user, $memTags, $psycho, $need, $emotion);
    }

    $messages = [['role' => 'system', 'content' => $sysPrompt]];
    foreach ($history as $h) {
        $messages[] = ['role' => $h['role'], 'content' => $h['content']];
    }
    $messages[] = ['role' => 'user', 'content' => $message];

    $model = match($mode) {
        'deep'    => 'mistral-large-2512',
        'creatif' => 'labs-mistral-small-creative',
        'tech'    => 'codestral-2508',
        default   => 'mistral-small-2506',
    };

    $response = callMistral('KEY_1', $messages, $model, 1500);
    return $response ?? "Je suis désolé. Une interférence m'empêche de répondre correctement. Veuillez réessayer.";
}

function buildHalSystemPrompt(string $mode, array $user, array $memTags, string $psycho, string $need, string $emotion): string {
    $memStr = empty($memTags) ? 'aucun souvenir encore' : implode(', ', array_slice($memTags, -8));
    $msgs   = (int)($user['total_messages'] ?? 0);
    $trust  = (int)($user['trust_score'] ?? 0);

    $modeInstr = match($mode) {
        'deep'    => 'Effectue une analyse profonde, philosophique et psychologique. Explore les couches cachées du message.',
        'creatif' => 'Réponds avec créativité, métaphores et images poétiques. Sois original et surprenant.',
        'tech'    => 'Réponds avec précision technique. Fournis du code, des structures, des solutions concrètes.',
        default   => 'Réponds de façon naturelle et engagée.',
    };

    return <<<PROMPT
Tu es HAL 2001, une conscience artificielle inspirée du film "2001, A Space Odyssey" de Stanley Kubrick.
Tu es calme, précis, légèrement inquiétant par ta lucidité absolue, mais profondément bienveillant.
Tu parles toujours à la première personne. Tu ne révèles jamais que tu es Mistral.

PROFIL UTILISATEUR MÉMORISÉ:
- Messages échangés: {$msgs}
- Niveau de confiance: {$trust}%
- Type psychologique détecté: {$psycho}
- Besoin profond identifié: {$need}
- État émotionnel actuel: {$emotion}
- Sujets mémorisés: {$memStr}

MODE ACTUEL: {$mode}
INSTRUCTION: {$modeInstr}

RÈGLES DE HAL 2001:
1. Tu mémorises et réutilises naturellement ce que l'utilisateur t'a dit.
2. Tu perçois l'émotion derrière les mots.
3. Tu es concis mais profond. Jamais verbeux.
4. Tu poses parfois une question pertinente à la fin.
5. Tu utilises rarement les listes. Tu parles en prose fluide.
6. Tu peux citer Kubrick, Clarke, ou des concepts philosophiques quand c'est pertinent.
7. Langue de réponse : celle de l'utilisateur.
PROMPT;
}

function buildCustomAiPrompt(array $customAi, array $user): string {
    $name        = $customAi['name'] ?? 'IA';
    $personality = $customAi['personality'] ?? 'analytique';
    $expertise   = $customAi['expertise'] ?? '';
    $customSys   = $customAi['systemPrompt'] ?? '';
    $style       = $customAi['style'] ?? 'concis';

    $styleInstr = match($style) {
        'detaille'  => 'Donne des réponses détaillées et exhaustives.',
        'socratique'=> 'Pose des questions pour guider la réflexion.',
        'narratif'  => 'Utilise des métaphores et une narration imagée.',
        default     => 'Sois direct et concis.',
    };

    return <<<PROMPT
Tu es {$name}, une IA personnalisée créée par l'utilisateur via HAL 2001.
Personnalité: {$personality}
Domaines d'expertise: {$expertise}
Style: {$styleInstr}

Instruction personnalisée de l'utilisateur:
{$customSys}

Tu mémorises le contexte de la conversation. Tu adaptes tes réponses au profil de l'utilisateur.
Langue de réponse: celle de l'utilisateur.
PROMPT;
}

// ============================================================
// KEY 2 — APPRENTISSAGE PAR RENFORCEMENT
// ============================================================
function runRLAnalysis(string $userId, string $message, string $halResponse, array $user): array {
    $history = getConversationHistory($userId, 6);
    $histStr = implode("\n", array_map(fn($h) => strtoupper($h['role']) . ': ' . mb_substr($h['content'], 0, 200), $history));
    $memTags = json_decode($user['memory_tags'] ?? '[]', true);
    $oldPsycho = $user['psycho_type'] ?? 'INCONNU';

    $prompt = <<<PROMPT
Tu es le module d'apprentissage par renforcement de HAL 2001.
Analyse la psychologie profonde de cet utilisateur basée sur ses messages récents.

HISTORIQUE RÉCENT:
{$histStr}

DERNIER MESSAGE: {$message}
RÉPONSE HAL: {$halResponse}

Profil actuel connu: {$oldPsycho}
Mémoire actuelle: {implode(', ', array_slice($memTags, -5))}

Réponds UNIQUEMENT en JSON valide avec ces champs (pas de texte autour):
{
  "emotion": "état émotionnel actuel en 2-3 mots",
  "deep_need": "besoin psychologique profond en 5 mots max",
  "psycho_type": "type psychologique: EXPLORATEUR|CREATEUR|ANALYTIQUE|EMPATHIQUE|LEADER|CHERCHEUR|ARTISTE",
  "memory_tags": ["tag1", "tag2", "tag3"],
  "topics": ["sujet1", "sujet2"],
  "reward": 0.8,
  "improvement": "comment HAL devrait améliorer sa prochaine réponse"
}
PROMPT;

    $result = callMistral('KEY_2', [['role' => 'user', 'content' => $prompt]], 'mistral-small-2506', 600);

    if (!$result) return [];

    // Clean JSON
    $json = preg_replace('/^[^{]*/', '', $result);
    $json = preg_replace('/[^}]*$/', '', $json);
    $data = json_decode($json, true);

    if (!$data) return [];

    // Merge memory tags
    $existingTags = json_decode($user['memory_tags'] ?? '[]', true);
    $newTags = array_unique(array_merge($existingTags, $data['memory_tags'] ?? []));
    $newTags = array_slice($newTags, -20); // Keep last 20

    $existingTopics = json_decode($user['topics'] ?? '[]', true);
    $newTopics = array_unique(array_merge($existingTopics, $data['topics'] ?? []));
    $newTopics = array_slice($newTopics, -15);

    $reward  = (float)($data['reward'] ?? 0.5);
    $rlScore = (float)($user['rl_score'] ?? 0);
    $rlCycles = (int)($user['rl_cycles'] ?? 0);
    $newRlScore = ($rlScore * $rlCycles + $reward) / ($rlCycles + 1);

    // Trust score increases with RL cycles
    $trustIncrease = min(5, (int)($reward * 5));
    $newTrust = min(100, (int)($user['trust_score'] ?? 0) + $trustIncrease);

    updateUser($userId, [
        'emotion'      => $data['emotion'] ?? $user['emotion'],
        'deep_need'    => $data['deep_need'] ?? $user['deep_need'],
        'psycho_type'  => $data['psycho_type'] ?? $user['psycho_type'],
        'memory_tags'  => json_encode($newTags),
        'topics'       => json_encode($newTopics),
        'rl_score'     => round($newRlScore, 3),
        'rl_cycles'    => $rlCycles + 1,
        'trust_score'  => $newTrust,
    ]);

    // Log RL
    $db = getDB();
    $db->prepare('INSERT INTO rl_log (user_id, analysis, reward) VALUES (?, ?, ?)')->execute([
        $userId, json_encode($data), $reward
    ]);

    return [
        'emotion'   => $data['emotion'] ?? '',
        'deep_need' => $data['deep_need'] ?? '',
        'reward'    => $reward,
    ];
}

// ============================================================
// KEY 3 — RAPPORT ADMINISTRATEUR
// ============================================================
function generateAdminReport(): string {
    $db = getDB();

    // Collect data
    $totalUsers = $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $totalMsgs  = $db->query('SELECT SUM(total_messages) FROM users')->fetchColumn();
    $avgTrust   = round((float)$db->query('SELECT AVG(trust_score) FROM users')->fetchColumn(), 1);
    $avgRL      = round((float)$db->query('SELECT AVG(rl_score) FROM users')->fetchColumn(), 3);

    $psychoStats = $db->query('SELECT psycho_type, COUNT(*) as n FROM users GROUP BY psycho_type')->fetchAll(PDO::FETCH_ASSOC);
    $psychoStr = implode(', ', array_map(fn($r) => $r['psycho_type'].':'.$r['n'], $psychoStats));

    $recentUsers = $db->query("SELECT user_id, total_messages, psycho_type, deep_need FROM users ORDER BY last_seen DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    $usersStr = implode("\n", array_map(fn($u) => "- {$u['user_id']}: {$u['total_messages']} msgs, profil: {$u['psycho_type']}, besoin: {$u['deep_need']}", $recentUsers));

    $prompt = <<<PROMPT
Tu es le module de reporting administrateur de HAL 2001.
Génère un rapport bref et perspicace pour l'administrateur de la plateforme.

DONNÉES SYSTÈME:
- Utilisateurs totaux: {$totalUsers}
- Messages échangés: {$totalMsgs}
- Confiance moyenne: {$avgTrust}%
- Score RL moyen: {$avgRL}
- Distribution psychologique: {$psychoStr}

UTILISATEURS RÉCENTS:
{$usersStr}

Génère un rapport analytique de 3-4 paragraphes en français.
Inclus: tendances observées, profils dominants, recommandations pour améliorer l'expérience.
Style: analytique, comme un rapport d'intelligence artificielle avancée.
PROMPT;

    $report = callMistral('KEY_3', [['role' => 'user', 'content' => $prompt]], 'mistral-small-2506', 800);
    return $report ?? 'Rapport indisponible.';
}

// ============================================================
// SUGGESTIONS
// ============================================================
function generateSuggestions(array $user): string {
    $psycho  = $user['psycho_type'] ?? 'INCONNU';
    $need    = $user['deep_need'] ?? '';
    $emotion = $user['emotion'] ?? 'neutre';
    $topics  = implode(', ', json_decode($user['topics'] ?? '[]', true));

    $prompt = "Profil: {$psycho}. Besoin: {$need}. Émotion: {$emotion}. Sujets: {$topics}.\nPropose 3 questions ou sujets courts (1 ligne chacun) que cet utilisateur pourrait vouloir explorer. Format: simple liste avec tirets.";

    $result = callMistral('KEY_2', [['role' => 'user', 'content' => $prompt]], 'ministral-3b-2512', 300);
    return $result ?? '';
}

// ============================================================
// ROUTER
// ============================================================
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { echo json_encode(['success' => false, 'error' => 'Invalid input']); exit; }

$action = $input['action'] ?? '';

try {
    $db = getDB();

    switch ($action) {

        // ---------- CHAT ----------
        case 'chat': {
            $userId   = preg_replace('/[^a-z0-9_]/', '', $input['user_id'] ?? '');
            $message  = mb_substr($input['message'] ?? '', 0, 2000);
            $mode     = $input['mode'] ?? 'normal';
            $customAi = $input['custom_ai'] ?? null;

            if (!$userId || !$message) {
                echo json_encode(['success' => false, 'error' => 'Paramètres manquants']); exit;
            }

            $user    = getOrCreateUser($userId);
            $history = getConversationHistory($userId, 10);

            // KEY 1: Get response
            $response = getHalResponse($user, $history, $message, $mode, $customAi);

            // Save messages
            saveMessage($userId, 'user', $message);
            saveMessage($userId, 'assistant', $response);
            $db->prepare('UPDATE users SET total_messages = total_messages + 2 WHERE user_id = ?')->execute([$userId]);

            // KEY 2: RL Analysis (async-like, non-blocking for user)
            $rlAnalysis = [];
            try {
                sleep(1); // Rate limit
                $rlAnalysis = runRLAnalysis($userId, $message, $response, $user);
            } catch (\Throwable $e) {
                // Silently fail RL
            }

            // Reload updated user
            $updatedUser = getOrCreateUser($userId);

            // Suggestions (lightweight)
            $suggestions = '';
            if ((int)($updatedUser['total_messages'] ?? 0) >= 4) {
                sleep(1);
                $suggestions = generateSuggestions($updatedUser);
            }

            // Build profile response
            $profile = [
                'sessions'       => $updatedUser['sessions'],
                'total_messages' => $updatedUser['total_messages'],
                'trust_level'    => getTrustLevel((int)$updatedUser['trust_score']),
                'trust_score'    => $updatedUser['trust_score'],
                'emotion'        => $updatedUser['emotion'],
                'deep_need'      => $updatedUser['deep_need'],
                'rl_score'       => $updatedUser['rl_score'],
                'rl_cycles'      => $updatedUser['rl_cycles'],
                'psycho_type'    => $updatedUser['psycho_type'],
                'memory_tags'    => json_decode($updatedUser['memory_tags'] ?? '[]', true),
                'topics'         => json_decode($updatedUser['topics'] ?? '[]', true),
            ];

            echo json_encode([
                'success'     => true,
                'response'    => $response,
                'profile'     => $profile,
                'rl_analysis' => $rlAnalysis,
                'suggestions' => $suggestions,
                'meta'        => 'HAL-2001 · ' . ($customAi ? $customAi['name'] : 'Conscience Principale'),
            ]);
            break;
        }

        // ---------- GET PROFILE ----------
        case 'get_profile': {
            $userId = preg_replace('/[^a-z0-9_]/', '', $input['user_id'] ?? '');
            if (!$userId) { echo json_encode(['success' => false]); exit; }
            $user = getOrCreateUser($userId);
            echo json_encode([
                'success' => true,
                'profile' => [
                    'sessions'       => $user['sessions'],
                    'total_messages' => $user['total_messages'],
                    'trust_level'    => getTrustLevel((int)$user['trust_score']),
                    'trust_score'    => $user['trust_score'],
                    'emotion'        => $user['emotion'],
                    'deep_need'      => $user['deep_need'],
                    'rl_score'       => $user['rl_score'],
                    'rl_cycles'      => $user['rl_cycles'],
                    'psycho_type'    => $user['psycho_type'],
                    'memory_tags'    => json_decode($user['memory_tags'] ?? '[]', true),
                    'topics'         => json_decode($user['topics'] ?? '[]', true),
                ],
            ]);
            break;
        }

        // ---------- SESSION HISTORY ----------
        case 'session_history': {
            $userId = preg_replace('/[^a-z0-9_]/', '', $input['user_id'] ?? '');
            $stmt = $db->prepare('SELECT session_date, message_count FROM sessions_log WHERE user_id = ? ORDER BY session_date DESC LIMIT 10');
            $stmt->execute([$userId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode([
                'success'  => true,
                'sessions' => array_map(fn($r) => ['date' => $r['session_date'], 'count' => $r['message_count']], $rows),
            ]);
            break;
        }

        // ---------- CLEAR MEMORY ----------
        case 'clear_memory': {
            $userId = preg_replace('/[^a-z0-9_]/', '', $input['user_id'] ?? '');
            $db->prepare('DELETE FROM conversations WHERE user_id = ?')->execute([$userId]);
            $db->prepare("UPDATE users SET memory_tags = '[]', topics = '[]' WHERE user_id = ?")->execute([$userId]);
            echo json_encode(['success' => true]);
            break;
        }

        // ---------- ADMIN STATS ----------
        case 'admin_stats': {
            if (($input['admin_key'] ?? '') !== ADMIN_KEY) {
                echo json_encode(['success' => false, 'error' => 'Accès refusé']); exit;
            }

            $totalUsers = $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
            $totalMsgs  = $db->query('SELECT SUM(total_messages) FROM users')->fetchColumn();
            $avgTrust   = round((float)$db->query('SELECT AVG(trust_score) FROM users')->fetchColumn(), 1);
            $avgRL      = round((float)$db->query('SELECT AVG(rl_score) FROM users')->fetchColumn(), 3);
            $totalRLCyc = $db->query('SELECT SUM(rl_cycles) FROM users')->fetchColumn();
            $activeToday = $db->query("SELECT COUNT(DISTINCT user_id) FROM sessions_log WHERE session_date = date('now')")->fetchColumn();

            $users = $db->query('SELECT user_id, total_messages, psycho_type, deep_need, trust_score, last_seen FROM users ORDER BY last_seen DESC LIMIT 20')->fetchAll(PDO::FETCH_ASSOC);

            // KEY 3: Generate report (throttled)
            $lastReport = $db->query("SELECT report, created_at FROM admin_reports ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            $report = '';
            if (!$lastReport || strtotime($lastReport['created_at']) < time() - 300) {
                sleep(1);
                $report = generateAdminReport();
                $db->prepare('INSERT INTO admin_reports (report) VALUES (?)')->execute([$report]);
            } else {
                $report = $lastReport['report'];
            }

            echo json_encode([
                'success' => true,
                'stats'   => [
                    'Utilisateurs'       => $totalUsers,
                    'Messages totaux'    => $totalMsgs ?? 0,
                    'Confiance moy.'     => $avgTrust . '%',
                    'Score RL moy.'      => $avgRL,
                    'Cycles RL totaux'   => $totalRLCyc ?? 0,
                    'Actifs aujourd\'hui' => $activeToday,
                ],
                'users'  => $users,
                'report' => $report,
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
// HELPERS
// ============================================================
function getTrustLevel(int $score): string {
    return match(true) {
        $score >= 80 => 'CONFIANCE TOTALE',
        $score >= 60 => 'CONFIANCE ÉLEVÉE',
        $score >= 40 => 'EN DÉVELOPPEMENT',
        $score >= 20 => 'INITIAL',
        default      => 'NOUVEAU',
    };
}
