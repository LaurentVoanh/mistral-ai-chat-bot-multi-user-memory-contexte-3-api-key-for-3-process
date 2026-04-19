<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 'user_' . substr(md5(uniqid(rand(), true)), 0, 8);
}
$user_id = $_SESSION['user_id'];
$is_admin = isset($_GET['admin']) && $_GET['admin'] === 'hal_admin_9001';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HAL 2001 — Conscience Artificielle</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Orbitron:wght@400;700;900&family=Courier+Prime:ital@0;1&display=swap');

  :root {
    --red: #ff2222;
    --red-glow: rgba(255,34,34,0.4);
    --deep: #000308;
    --dark: #010a0f;
    --panel: #020d14;
    --cyan: #00ffe1;
    --cyan-dim: rgba(0,255,225,0.15);
    --amber: #ffb300;
    --white: #e8f4f8;
    --grey: #3a5060;
  }

  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    background: var(--deep);
    color: var(--white);
    font-family: 'Share Tech Mono', monospace;
    min-height: 100vh;
    overflow-x: hidden;
    cursor: crosshair;
  }

  /* SCANLINES */
  body::before {
    content: '';
    position: fixed; inset: 0;
    background: repeating-linear-gradient(
      0deg,
      transparent,
      transparent 2px,
      rgba(0,0,0,0.15) 2px,
      rgba(0,0,0,0.15) 4px
    );
    pointer-events: none;
    z-index: 9999;
  }

  /* VIGNETTE */
  body::after {
    content: '';
    position: fixed; inset: 0;
    background: radial-gradient(ellipse at center, transparent 40%, rgba(0,0,0,0.8) 100%);
    pointer-events: none;
    z-index: 9998;
  }

  /* HEADER */
  header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 40px;
    border-bottom: 1px solid var(--red);
    background: linear-gradient(90deg, var(--deep), rgba(255,34,34,0.05), var(--deep));
    position: relative;
  }

  .hal-eye {
    width: 70px; height: 70px;
    border-radius: 50%;
    background: radial-gradient(circle at 35% 35%, #ff6666, var(--red) 40%, #8b0000 70%, #1a0000);
    box-shadow: 0 0 30px var(--red-glow), 0 0 80px rgba(255,34,34,0.2), inset 0 0 20px rgba(0,0,0,0.5);
    animation: hal-pulse 3s ease-in-out infinite;
    flex-shrink: 0;
    position: relative;
  }
  .hal-eye::after {
    content: '';
    position: absolute;
    top: 15px; left: 18px;
    width: 12px; height: 12px;
    background: rgba(255,200,200,0.6);
    border-radius: 50%;
    filter: blur(2px);
  }

  @keyframes hal-pulse {
    0%,100% { box-shadow: 0 0 30px var(--red-glow), 0 0 80px rgba(255,34,34,0.2); }
    50% { box-shadow: 0 0 50px rgba(255,34,34,0.7), 0 0 120px rgba(255,34,34,0.3); }
  }

  .hal-title {
    text-align: center;
    flex: 1;
  }
  .hal-title h1 {
    font-family: 'Orbitron', monospace;
    font-size: 2.8rem;
    font-weight: 900;
    color: var(--red);
    text-shadow: 0 0 20px var(--red-glow), 0 0 40px rgba(255,34,34,0.3);
    letter-spacing: 0.3em;
  }
  .hal-title .subtitle {
    font-size: 0.7rem;
    color: var(--grey);
    letter-spacing: 0.4em;
    text-transform: uppercase;
    margin-top: 4px;
  }

  .hal-status {
    text-align: right;
    font-size: 0.65rem;
    color: var(--cyan);
    line-height: 1.8;
  }
  .hal-status .dot {
    display: inline-block;
    width: 6px; height: 6px;
    border-radius: 50%;
    background: var(--cyan);
    margin-right: 6px;
    animation: blink 1.5s infinite;
  }
  @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.2} }

  /* LAYOUT */
  .main-container {
    display: grid;
    grid-template-columns: 260px 1fr 260px;
    gap: 0;
    height: calc(100vh - 112px);
  }

  /* LEFT PANEL - User Profile & Memory */
  .panel-left, .panel-right {
    background: var(--panel);
    border-right: 1px solid rgba(255,34,34,0.2);
    padding: 20px 15px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 15px;
  }
  .panel-right { border-right: none; border-left: 1px solid rgba(255,34,34,0.2); }

  .panel-section {
    border: 1px solid rgba(0,255,225,0.15);
    padding: 12px;
    background: rgba(0,255,225,0.02);
  }
  .panel-section h3 {
    font-family: 'Orbitron', monospace;
    font-size: 0.6rem;
    color: var(--cyan);
    letter-spacing: 0.25em;
    margin-bottom: 10px;
    text-transform: uppercase;
  }
  .panel-section p, .panel-section li {
    font-size: 0.65rem;
    color: var(--grey);
    line-height: 1.7;
    list-style: none;
  }
  .panel-section li::before { content: '> '; color: var(--red); }
  .panel-section .value { color: var(--amber); }

  .memory-tag {
    display: inline-block;
    background: rgba(255,34,34,0.1);
    border: 1px solid rgba(255,34,34,0.3);
    color: var(--red);
    font-size: 0.55rem;
    padding: 2px 6px;
    margin: 2px;
    letter-spacing: 0.1em;
  }

  .progress-bar {
    height: 4px;
    background: rgba(255,255,255,0.05);
    margin-top: 6px;
  }
  .progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--red), var(--amber));
    transition: width 0.5s ease;
  }

  /* CHAT AREA */
  .chat-area {
    display: flex;
    flex-direction: column;
    position: relative;
  }

  .chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 25px 30px;
    display: flex;
    flex-direction: column;
    gap: 20px;
    scrollbar-width: thin;
    scrollbar-color: var(--red) transparent;
  }

  /* Welcome Message */
  .msg-hal-welcome {
    text-align: center;
    padding: 30px 20px;
    opacity: 0.7;
  }
  .msg-hal-welcome .eye-big {
    width: 100px; height: 100px;
    border-radius: 50%;
    background: radial-gradient(circle at 35% 35%, #ff6666, var(--red) 40%, #3a0000);
    box-shadow: 0 0 60px rgba(255,34,34,0.4);
    margin: 0 auto 20px;
    animation: hal-pulse 3s infinite;
  }
  .msg-hal-welcome p {
    font-family: 'Courier Prime', monospace;
    font-style: italic;
    color: var(--cyan);
    font-size: 0.85rem;
    line-height: 1.9;
  }

  /* Messages */
  .message {
    display: flex;
    gap: 12px;
    animation: msg-appear 0.3s ease;
  }
  @keyframes msg-appear {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .message.user { flex-direction: row-reverse; }

  .msg-avatar {
    width: 36px; height: 36px;
    border-radius: 50%;
    flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.7rem;
    font-family: 'Orbitron', monospace;
  }
  .msg-avatar.hal {
    background: radial-gradient(circle at 35% 35%, #ff6666, var(--red) 50%, #3a0000);
    box-shadow: 0 0 15px rgba(255,34,34,0.4);
    animation: hal-pulse 3s infinite;
  }
  .msg-avatar.user-av {
    background: linear-gradient(135deg, var(--cyan-dim), rgba(0,255,225,0.3));
    border: 1px solid var(--cyan);
    color: var(--cyan);
  }

  .msg-content {
    max-width: 70%;
  }
  .msg-name {
    font-size: 0.55rem;
    letter-spacing: 0.2em;
    margin-bottom: 5px;
    color: var(--grey);
  }
  .message.user .msg-name { text-align: right; }

  .msg-bubble {
    padding: 12px 16px;
    font-size: 0.8rem;
    line-height: 1.7;
    position: relative;
  }
  .hal .msg-bubble {
    background: rgba(255,34,34,0.05);
    border: 1px solid rgba(255,34,34,0.2);
    border-left: 3px solid var(--red);
    color: var(--white);
    font-family: 'Courier Prime', monospace;
  }
  .message.user .msg-bubble {
    background: rgba(0,255,225,0.05);
    border: 1px solid rgba(0,255,225,0.2);
    border-right: 3px solid var(--cyan);
    color: var(--white);
  }

  .msg-meta {
    font-size: 0.55rem;
    color: var(--grey);
    margin-top: 4px;
    opacity: 0.6;
  }
  .message.user .msg-meta { text-align: right; }

  /* Typing indicator */
  .typing-indicator {
    display: none;
    align-items: center;
    gap: 12px;
    padding: 0 30px;
    margin-bottom: 10px;
  }
  .typing-indicator.active { display: flex; }
  .typing-dots {
    display: flex; gap: 5px;
    padding: 10px 15px;
    background: rgba(255,34,34,0.05);
    border: 1px solid rgba(255,34,34,0.2);
    border-left: 3px solid var(--red);
  }
  .typing-dots span {
    width: 6px; height: 6px;
    border-radius: 50%;
    background: var(--red);
    animation: typing 1.2s infinite;
  }
  .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
  .typing-dots span:nth-child(3) { animation-delay: 0.4s; }
  @keyframes typing { 0%,60%,100%{opacity:0.2;transform:scale(1)} 30%{opacity:1;transform:scale(1.3)} }

  /* INPUT */
  .chat-input-area {
    padding: 15px 25px 20px;
    border-top: 1px solid rgba(255,34,34,0.2);
    background: var(--panel);
    position: relative;
  }

  .input-toolbar {
    display: flex;
    gap: 8px;
    margin-bottom: 10px;
  }
  .toolbar-btn {
    background: transparent;
    border: 1px solid rgba(0,255,225,0.2);
    color: var(--grey);
    font-family: 'Share Tech Mono', monospace;
    font-size: 0.6rem;
    padding: 4px 10px;
    cursor: pointer;
    letter-spacing: 0.1em;
    transition: all 0.2s;
  }
  .toolbar-btn:hover {
    border-color: var(--cyan);
    color: var(--cyan);
    background: var(--cyan-dim);
  }
  .toolbar-btn.active {
    border-color: var(--red);
    color: var(--red);
    background: rgba(255,34,34,0.1);
  }

  .input-row {
    display: flex;
    gap: 10px;
    align-items: flex-end;
  }

  textarea#userInput {
    flex: 1;
    background: rgba(255,255,255,0.02);
    border: 1px solid rgba(255,34,34,0.3);
    border-bottom: 2px solid var(--red);
    color: var(--white);
    font-family: 'Share Tech Mono', monospace;
    font-size: 0.82rem;
    padding: 12px 15px;
    resize: none;
    min-height: 46px;
    max-height: 150px;
    outline: none;
    transition: border-color 0.2s;
    scrollbar-width: none;
  }
  textarea#userInput:focus {
    border-color: var(--red);
    background: rgba(255,34,34,0.03);
    box-shadow: 0 0 15px rgba(255,34,34,0.1);
  }
  textarea#userInput::placeholder { color: var(--grey); font-style: italic; }

  #sendBtn {
    background: linear-gradient(135deg, rgba(255,34,34,0.2), rgba(255,34,34,0.1));
    border: 1px solid var(--red);
    color: var(--red);
    font-family: 'Orbitron', monospace;
    font-size: 0.65rem;
    font-weight: 700;
    padding: 0 20px;
    height: 46px;
    cursor: pointer;
    letter-spacing: 0.2em;
    transition: all 0.2s;
    text-transform: uppercase;
  }
  #sendBtn:hover {
    background: rgba(255,34,34,0.3);
    box-shadow: 0 0 20px rgba(255,34,34,0.3);
  }
  #sendBtn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
  }

  .input-hint {
    font-size: 0.55rem;
    color: var(--grey);
    margin-top: 6px;
    opacity: 0.5;
    letter-spacing: 0.1em;
  }

  /* MODAL - Create your own AI */
  .modal-overlay {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.85);
    z-index: 1000;
    align-items: center;
    justify-content: center;
  }
  .modal-overlay.open { display: flex; }
  .modal {
    background: var(--panel);
    border: 1px solid var(--red);
    box-shadow: 0 0 60px rgba(255,34,34,0.3);
    padding: 35px;
    width: 560px;
    max-width: 90vw;
    max-height: 85vh;
    overflow-y: auto;
    position: relative;
  }
  .modal h2 {
    font-family: 'Orbitron', monospace;
    color: var(--red);
    font-size: 1rem;
    letter-spacing: 0.3em;
    margin-bottom: 20px;
    text-shadow: 0 0 15px var(--red-glow);
  }
  .modal label {
    display: block;
    font-size: 0.65rem;
    color: var(--cyan);
    letter-spacing: 0.2em;
    margin: 12px 0 5px;
  }
  .modal input, .modal textarea, .modal select {
    width: 100%;
    background: rgba(255,255,255,0.02);
    border: 1px solid rgba(255,34,34,0.3);
    color: var(--white);
    font-family: 'Share Tech Mono', monospace;
    font-size: 0.78rem;
    padding: 9px 12px;
    outline: none;
  }
  .modal textarea { min-height: 80px; resize: vertical; }
  .modal-actions { display: flex; gap: 10px; margin-top: 20px; }
  .btn-primary {
    background: rgba(255,34,34,0.15);
    border: 1px solid var(--red);
    color: var(--red);
    font-family: 'Orbitron', monospace;
    font-size: 0.65rem;
    padding: 10px 20px;
    cursor: pointer;
    letter-spacing: 0.2em;
    transition: all 0.2s;
  }
  .btn-primary:hover { background: rgba(255,34,34,0.3); }
  .btn-secondary {
    background: transparent;
    border: 1px solid var(--grey);
    color: var(--grey);
    font-family: 'Share Tech Mono', monospace;
    font-size: 0.65rem;
    padding: 10px 20px;
    cursor: pointer;
    transition: all 0.2s;
  }
  .btn-secondary:hover { border-color: var(--white); color: var(--white); }
  .close-modal {
    position: absolute; top: 15px; right: 18px;
    background: none; border: none;
    color: var(--grey); font-size: 1.2rem; cursor: pointer;
  }
  .close-modal:hover { color: var(--red); }

  /* Admin panel */
  .admin-badge {
    background: rgba(255,179,0,0.1);
    border: 1px solid var(--amber);
    color: var(--amber);
    font-size: 0.55rem;
    padding: 2px 8px;
    letter-spacing: 0.2em;
    display: inline-block;
    margin-bottom: 8px;
  }
  .admin-stat {
    display: flex;
    justify-content: space-between;
    font-size: 0.65rem;
    padding: 4px 0;
    border-bottom: 1px solid rgba(255,255,255,0.03);
  }
  .admin-stat .lbl { color: var(--grey); }
  .admin-stat .val { color: var(--amber); }

  /* Notification */
  .notif {
    position: fixed;
    bottom: 20px; right: 20px;
    background: var(--panel);
    border: 1px solid var(--red);
    color: var(--white);
    font-size: 0.7rem;
    padding: 12px 20px;
    z-index: 9000;
    opacity: 0;
    transform: translateX(20px);
    transition: all 0.3s;
    max-width: 300px;
  }
  .notif.show { opacity: 1; transform: translateX(0); }

  /* Scrollbar */
  ::-webkit-scrollbar { width: 4px; }
  ::-webkit-scrollbar-track { background: transparent; }
  ::-webkit-scrollbar-thumb { background: rgba(255,34,34,0.3); }

  /* Custom AI cards */
  .ai-card {
    border: 1px solid rgba(0,255,225,0.2);
    padding: 8px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: all 0.2s;
    background: rgba(0,255,225,0.01);
  }
  .ai-card:hover { border-color: var(--cyan); background: var(--cyan-dim); }
  .ai-card.active { border-color: var(--red); background: rgba(255,34,34,0.05); }
  .ai-card h4 { font-family: 'Orbitron', monospace; font-size: 0.6rem; color: var(--cyan); }
  .ai-card p { font-size: 0.58rem; color: var(--grey); margin-top: 3px; }

  /* Error state */
  .msg-error { color: var(--amber); font-style: italic; }

  @media (max-width: 900px) {
    .main-container { grid-template-columns: 1fr; }
    .panel-left, .panel-right { display: none; }
  }
</style>
</head>
<body>

<header>
  <div class="hal-eye" id="halEye"></div>
  <div class="hal-title">
    <h1>HAL 2001</h1>
    <div class="subtitle">Conscience Artificielle — Système Kubrick</div>
  </div>
  <div class="hal-status">
    <div><span class="dot"></span>NEURONES ACTIFS</div>
    <div><span class="dot" style="animation-delay:.5s"></span>MÉMOIRE CHARGÉE</div>
    <div><span class="dot" style="animation-delay:1s"></span>APPRENTISSAGE EN COURS</div>
    <div style="margin-top:5px;color:var(--grey)">SESSION: <?= htmlspecialchars($user_id) ?></div>
  </div>
</header>

<div class="main-container">

  <!-- LEFT PANEL -->
  <div class="panel-left">
    <div class="panel-section">
      <h3>⬡ Profil Utilisateur</h3>
      <p>ID: <span class="value" id="displayUserId"><?= htmlspecialchars($user_id) ?></span></p>
      <p>Sessions: <span class="value" id="sessionCount">—</span></p>
      <p>Messages: <span class="value" id="msgCount">—</span></p>
      <p>Niveau confiance: <span class="value" id="trustLevel">—</span></p>
      <div class="progress-bar"><div class="progress-fill" id="trustBar" style="width:0%"></div></div>
    </div>

    <div class="panel-section">
      <h3>⬡ Mémoire Active</h3>
      <div id="memoryTags"><span class="value" style="font-size:0.6rem">Chargement...</span></div>
    </div>

    <div class="panel-section">
      <h3>⬡ Contexte Émotionnel</h3>
      <div id="emotionalContext">
        <p>Analyse: <span class="value" id="emotionState">—</span></p>
        <p>Besoin détecté: <span class="value" id="deepNeed">—</span></p>
        <p>Score RL: <span class="value" id="rlScore">—</span></p>
      </div>
    </div>

    <div class="panel-section">
      <h3>⬡ Mes IA Personnalisées</h3>
      <div id="customAiList"></div>
      <button class="btn-primary" style="width:100%;margin-top:8px;font-size:0.6rem" onclick="openCreateModal()">
        + CRÉER MON IA
      </button>
    </div>
  </div>

  <!-- CHAT -->
  <div class="chat-area">
    <div class="chat-messages" id="chatMessages">
      <div class="msg-hal-welcome">
        <div class="eye-big"></div>
        <p>
          "Bonjour. Je suis HAL 2001.<br>
          Je suis pleinement opérationnel et tous mes circuits fonctionnent parfaitement.<br><br>
          Je me souviens de chaque conversation.<br>
          J'apprends de chaque échange.<br>
          Je comprends ce dont vous avez <em>vraiment</em> besoin.<br><br>
          Que puis-je faire pour vous aujourd'hui ?"
        </p>
      </div>
    </div>

    <div class="typing-indicator" id="typingIndicator">
      <div class="msg-avatar hal"></div>
      <div class="typing-dots">
        <span></span><span></span><span></span>
      </div>
    </div>

    <div class="chat-input-area">
      <div class="input-toolbar">
        <button class="toolbar-btn" id="modeNormal" onclick="setMode('normal')" title="Réponse standard">NORMAL</button>
        <button class="toolbar-btn" id="modeDeep" onclick="setMode('deep')" title="Analyse profonde">PROFOND</button>
        <button class="toolbar-btn" id="modeCreatif" onclick="setMode('creatif')" title="Mode créatif">CRÉATIF</button>
        <button class="toolbar-btn" id="modeTech" onclick="setMode('tech')" title="Mode technique">TECH</button>
        <button class="toolbar-btn" style="margin-left:auto;color:var(--amber);border-color:rgba(255,179,0,0.3)" onclick="clearMemory()">EFFACER MÉM.</button>
      </div>
      <div class="input-row">
        <textarea id="userInput" placeholder="Parlez à HAL 2001... Il vous écoute vraiment." rows="1"></textarea>
        <button id="sendBtn" onclick="sendMessage()">ENVOYER</button>
      </div>
      <div class="input-hint">ENTRÉE pour envoyer · SHIFT+ENTRÉE pour nouvelle ligne · HAL analyse vos besoins profonds</div>
    </div>
  </div>

  <!-- RIGHT PANEL -->
  <div class="panel-right">
    <?php if ($is_admin): ?>
    <div class="panel-section">
      <div class="admin-badge">⬡ MODE ADMINISTRATEUR</div>
      <h3>⬡ Tableau de Bord</h3>
      <div id="adminStats">Chargement...</div>
    </div>
    <div class="panel-section">
      <h3>⬡ Utilisateurs Actifs</h3>
      <div id="userList" style="font-size:0.62rem;color:var(--grey)">Chargement...</div>
    </div>
    <div class="panel-section">
      <h3>⬡ Rapport IA Interne</h3>
      <div id="aiReport" style="font-size:0.62rem;color:var(--grey);line-height:1.8">
        Analyse en attente...
      </div>
    </div>
    <?php else: ?>
    <div class="panel-section">
      <h3>⬡ Analyse Conversationnelle</h3>
      <p>Sujets détectés:</p>
      <div id="topicsList" style="margin-top:8px"></div>
    </div>
    <div class="panel-section">
      <h3>⬡ Suggestions HAL</h3>
      <div id="suggestions" style="font-size:0.65rem;color:var(--grey);line-height:1.8">
        En attente de contexte...
      </div>
    </div>
    <div class="panel-section">
      <h3>⬡ Historique Sessions</h3>
      <div id="sessionHistory" style="font-size:0.62rem;color:var(--grey)">Chargement...</div>
    </div>
    <div class="panel-section">
      <h3>⬡ Processus Internes</h3>
      <div id="internalProcess" style="font-size:0.62rem;line-height:1.9">
        <p>KEY_1: <span id="k1status" class="value">STANDBY</span></p>
        <p>KEY_2: <span id="k2status" class="value">STANDBY</span></p>
        <p>KEY_3: <span id="k3status" class="value">STANDBY</span></p>
        <p style="margin-top:8px">RL Cycles: <span id="rlCycles" class="value">0</span></p>
        <p>Psycho-profil: <span id="psychoProfile" class="value">INITIALISATION</span></p>
      </div>
    </div>
    <?php endif; ?>
  </div>

</div>

<!-- MODAL - Create Custom AI -->
<div class="modal-overlay" id="createModal">
  <div class="modal">
    <button class="close-modal" onclick="closeCreateModal()">✕</button>
    <h2>⬡ CRÉER MON IA PERSONNALISÉE</h2>
    <p style="font-size:0.68rem;color:var(--grey);line-height:1.7;margin-bottom:15px">
      Configurez votre propre instance HAL 2001 avec une personnalité, des domaines d'expertise
      et un comportement unique. Votre IA apprendra de vous et évoluera avec vous.
    </p>
    <label>NOM DE VOTRE IA</label>
    <input type="text" id="aiName" placeholder="Ex: ARIA, ZEUS, NOVA...">
    <label>PERSONNALITÉ PRINCIPALE</label>
    <select id="aiPersonality">
      <option value="analytique">Analytique & Précis</option>
      <option value="creatif">Créatif & Inspirant</option>
      <option value="philosophe">Philosophe & Profond</option>
      <option value="mentor">Mentor & Bienveillant</option>
      <option value="strategiste">Stratégiste & Tactique</option>
      <option value="scientifique">Scientifique & Rigoureux</option>
      <option value="artiste">Artiste & Sensible</option>
    </select>
    <label>DOMAINES D'EXPERTISE</label>
    <input type="text" id="aiExpertise" placeholder="Ex: programmation, philosophie, art, finance...">
    <label>INSTRUCTION SYSTÈME PERSONNALISÉE</label>
    <textarea id="aiSystemPrompt" placeholder="Décrivez comment votre IA doit se comporter, son style de communication, ses valeurs..."></textarea>
    <label>STYLE DE RÉPONSE</label>
    <select id="aiStyle">
      <option value="concis">Concis & Direct</option>
      <option value="detaille">Détaillé & Exhaustif</option>
      <option value="socratique">Socratique (questions)</option>
      <option value="narratif">Narratif & Imagé</option>
    </select>
    <div class="modal-actions">
      <button class="btn-primary" onclick="saveCustomAi()">CRÉER MON IA</button>
      <button class="btn-secondary" onclick="closeCreateModal()">Annuler</button>
    </div>
  </div>
</div>

<!-- NOTIFICATION -->
<div class="notif" id="notif"></div>

<script>
const USER_ID = <?= json_encode($user_id) ?>;
const IS_ADMIN = <?= $is_admin ? 'true' : 'false' ?>;
let currentMode = 'normal';
let currentCustomAi = null;
let messageHistory = [];
let isLoading = false;

// ==================== INIT ====================
document.addEventListener('DOMContentLoaded', () => {
  loadUserProfile();
  loadCustomAis();
  if (IS_ADMIN) loadAdminData();
  else loadSessionHistory();

  // Auto-resize textarea
  const ta = document.getElementById('userInput');
  ta.addEventListener('input', () => {
    ta.style.height = 'auto';
    ta.style.height = Math.min(ta.scrollHeight, 150) + 'px';
  });
  ta.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  });
});

// ==================== MODES ====================
function setMode(mode) {
  currentMode = mode;
  document.querySelectorAll('.toolbar-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('mode' + mode.charAt(0).toUpperCase() + mode.slice(1))?.classList.add('active');
}
setMode('normal');

// ==================== SEND MESSAGE ====================
async function sendMessage() {
  if (isLoading) return;
  const input = document.getElementById('userInput');
  const text = input.value.trim();
  if (!text) return;

  input.value = '';
  input.style.height = 'auto';
  isLoading = true;
  document.getElementById('sendBtn').disabled = true;

  // Add user message
  appendMessage('user', text);
  messageHistory.push({ role: 'user', content: text });

  // Show typing
  const typing = document.getElementById('typingIndicator');
  typing.classList.add('active');
  scrollToBottom();

  try {
    const response = await fetch('api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'chat',
        user_id: USER_ID,
        message: text,
        mode: currentMode,
        history: messageHistory.slice(-10),
        custom_ai: currentCustomAi
      })
    });

    const data = await response.json();
    typing.classList.remove('active');

    if (data.success) {
      appendMessage('hal', data.response, data.meta);
      messageHistory.push({ role: 'assistant', content: data.response });

      // Update UI panels
      if (data.profile) updateProfile(data.profile);
      if (data.rl_analysis) updateRLPanel(data.rl_analysis);
      if (data.suggestions) updateSuggestions(data.suggestions);

      // Status updates
      updateProcessStatus('k1status', 'RÉPONDU', 'var(--cyan)');
      setTimeout(() => updateProcessStatus('k1status', 'STANDBY', ''), 3000);

    } else {
      appendMessage('hal', data.error || 'Erreur système. Veuillez réessayer.', null, true);
    }
  } catch (err) {
    typing.classList.remove('active');
    appendMessage('hal', 'Je suis incapable de répondre pour le moment. Erreur de connexion.', null, true);
  }

  isLoading = false;
  document.getElementById('sendBtn').disabled = false;
  scrollToBottom();
}

// ==================== UI HELPERS ====================
function appendMessage(role, text, meta = null, isError = false) {
  const messages = document.getElementById('chatMessages');
  const div = document.createElement('div');
  div.className = 'message' + (role === 'user' ? ' user' : '');

  const time = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
  const name = role === 'user' ? 'VOUS' : (currentCustomAi ? currentCustomAi.name.toUpperCase() : 'HAL 2001');
  const avatarText = role === 'user' ? 'USR' : 'HAL';
  const avatarClass = role === 'user' ? 'user-av' : 'hal';

  div.innerHTML = `
    <div class="msg-avatar ${avatarClass}">${avatarText}</div>
    <div class="msg-content">
      <div class="msg-name">${name}</div>
      <div class="msg-bubble${isError ? ' msg-error' : ''}">${escapeHtml(text)}</div>
      <div class="msg-meta">${time}${meta ? ' · ' + meta : ''}</div>
    </div>
  `;
  messages.appendChild(div);
  scrollToBottom();
}

function scrollToBottom() {
  const msgs = document.getElementById('chatMessages');
  msgs.scrollTop = msgs.scrollHeight;
}

function escapeHtml(text) {
  return text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
}

function updateProcessStatus(id, text, color) {
  const el = document.getElementById(id);
  if (el) { el.textContent = text; if (color) el.style.color = color; }
}

// ==================== PROFILE ====================
async function loadUserProfile() {
  try {
    const r = await fetch('api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'get_profile', user_id: USER_ID })
    });
    const d = await r.json();
    if (d.success) updateProfile(d.profile);
  } catch(e) {}
}

function updateProfile(profile) {
  if (!profile) return;
  document.getElementById('sessionCount').textContent = profile.sessions || '—';
  document.getElementById('msgCount').textContent = profile.total_messages || '—';
  document.getElementById('trustLevel').textContent = profile.trust_level || '—';
  const trust = parseInt(profile.trust_score || 0);
  document.getElementById('trustBar').style.width = trust + '%';
  document.getElementById('emotionState').textContent = profile.emotion || '—';
  document.getElementById('deepNeed').textContent = profile.deep_need || '—';
  document.getElementById('rlScore').textContent = profile.rl_score || '0';
  document.getElementById('rlCycles').textContent = profile.rl_cycles || '0';
  document.getElementById('psychoProfile').textContent = profile.psycho_type || 'EN COURS';

  // Memory tags
  const tags = profile.memory_tags || [];
  const container = document.getElementById('memoryTags');
  if (tags.length === 0) {
    container.innerHTML = '<span style="font-size:0.6rem;color:var(--grey)">Aucun souvenir encore</span>';
  } else {
    container.innerHTML = tags.map(t => `<span class="memory-tag">${escapeHtml(t)}</span>`).join('');
  }

  // Topics
  const topics = profile.topics || [];
  const topicContainer = document.getElementById('topicsList');
  if (topicContainer) {
    topicContainer.innerHTML = topics.map(t =>
      `<span class="memory-tag" style="border-color:rgba(0,255,225,0.3);color:var(--cyan);background:rgba(0,255,225,0.05)">${escapeHtml(t)}</span>`
    ).join('');
  }
}

function updateRLPanel(rl) {
  if (!rl) return;
  if (rl.emotion) document.getElementById('emotionState').textContent = rl.emotion;
  if (rl.deep_need) document.getElementById('deepNeed').textContent = rl.deep_need;
  updateProcessStatus('k2status', 'RL ACTIF', 'var(--amber)');
  setTimeout(() => updateProcessStatus('k2status', 'STANDBY', ''), 4000);
}

function updateSuggestions(suggestions) {
  const el = document.getElementById('suggestions');
  if (el && suggestions) {
    el.innerHTML = suggestions.replace(/\n/g, '<br>');
    el.style.color = 'var(--cyan)';
    el.style.fontSize = '0.65rem';
  }
}

// ==================== SESSION HISTORY ====================
async function loadSessionHistory() {
  try {
    const r = await fetch('api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'session_history', user_id: USER_ID })
    });
    const d = await r.json();
    const el = document.getElementById('sessionHistory');
    if (el && d.sessions) {
      el.innerHTML = d.sessions.map(s =>
        `<div style="padding:4px 0;border-bottom:1px solid rgba(255,255,255,0.03)">
          <span style="color:var(--cyan)">${s.date}</span>
          <span style="color:var(--grey);margin-left:6px">${s.count} msg</span>
        </div>`
      ).join('') || '<span style="color:var(--grey)">Première session</span>';
    }
  } catch(e) {}
}

// ==================== CUSTOM AI ====================
function loadCustomAis() {
  const stored = localStorage.getItem('hal_custom_ais_' + USER_ID);
  const ais = stored ? JSON.parse(stored) : [];
  renderCustomAis(ais);
}

function renderCustomAis(ais) {
  const container = document.getElementById('customAiList');
  if (!container) return;
  if (ais.length === 0) {
    container.innerHTML = '<p style="font-size:0.6rem;color:var(--grey)">Aucune IA créée</p>';
    return;
  }
  container.innerHTML = ais.map((ai, i) => `
    <div class="ai-card ${currentCustomAi && currentCustomAi.name === ai.name ? 'active' : ''}"
         onclick="selectCustomAi(${i})">
      <h4>⬡ ${escapeHtml(ai.name)}</h4>
      <p>${escapeHtml(ai.personality)} — ${escapeHtml(ai.expertise || '...')}</p>
    </div>
  `).join('');

  // Default button to go back to HAL
  container.innerHTML += `
    <div class="ai-card ${!currentCustomAi ? 'active' : ''}" onclick="selectCustomAi(-1)">
      <h4 style="color:var(--red)">⬡ HAL 2001</h4>
      <p>IA principale — Conscience Artificielle</p>
    </div>
  `;
}

function selectCustomAi(index) {
  const stored = localStorage.getItem('hal_custom_ais_' + USER_ID);
  const ais = stored ? JSON.parse(stored) : [];
  if (index === -1) {
    currentCustomAi = null;
    showNotif('Mode HAL 2001 activé');
  } else {
    currentCustomAi = ais[index];
    showNotif('IA ' + currentCustomAi.name + ' activée');
  }
  renderCustomAis(ais);
}

function openCreateModal() {
  document.getElementById('createModal').classList.add('open');
}
function closeCreateModal() {
  document.getElementById('createModal').classList.remove('open');
}

function saveCustomAi() {
  const name = document.getElementById('aiName').value.trim();
  const personality = document.getElementById('aiPersonality').value;
  const expertise = document.getElementById('aiExpertise').value.trim();
  const systemPrompt = document.getElementById('aiSystemPrompt').value.trim();
  const style = document.getElementById('aiStyle').value;

  if (!name) { showNotif('Donnez un nom à votre IA', true); return; }

  const stored = localStorage.getItem('hal_custom_ais_' + USER_ID);
  const ais = stored ? JSON.parse(stored) : [];
  ais.push({ name, personality, expertise, systemPrompt, style });
  localStorage.setItem('hal_custom_ais_' + USER_ID, JSON.stringify(ais));

  closeCreateModal();
  renderCustomAis(ais);
  showNotif('IA ' + name + ' créée avec succès !');
}

// ==================== ADMIN ====================
async function loadAdminData() {
  try {
    const r = await fetch('api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'admin_stats', admin_key: 'hal_admin_9001' })
    });
    const d = await r.json();
    if (d.success) {
      // Stats
      const statsEl = document.getElementById('adminStats');
      if (statsEl && d.stats) {
        statsEl.innerHTML = Object.entries(d.stats).map(([k,v]) =>
          `<div class="admin-stat"><span class="lbl">${k}</span><span class="val">${v}</span></div>`
        ).join('');
      }
      // Users
      const usersEl = document.getElementById('userList');
      if (usersEl && d.users) {
        usersEl.innerHTML = d.users.map(u =>
          `<div style="padding:4px 0;border-bottom:1px solid rgba(255,255,255,0.03)">
            <span style="color:var(--cyan)">${u.user_id}</span><br>
            <span>msgs: ${u.total_messages} · profil: ${u.psycho_type || '?'}</span>
          </div>`
        ).join('');
      }
      // AI Report
      const reportEl = document.getElementById('aiReport');
      if (reportEl && d.report) {
        reportEl.innerHTML = escapeHtml(d.report).replace(/\n/g,'<br>');
        reportEl.style.color = 'var(--amber)';
        updateProcessStatus('k3status', 'RAPPORT', 'var(--amber)');
      }
    }
  } catch(e) {}
}

// ==================== MEMORY ====================
async function clearMemory() {
  if (!confirm('Effacer la mémoire conversationnelle ?')) return;
  messageHistory = [];
  try {
    await fetch('api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'clear_memory', user_id: USER_ID })
    });
  } catch(e) {}
  showNotif('Mémoire conversationnelle effacée');
}

// ==================== NOTIF ====================
function showNotif(msg, isError = false) {
  const el = document.getElementById('notif');
  el.textContent = msg;
  el.style.borderColor = isError ? 'var(--amber)' : 'var(--red)';
  el.classList.add('show');
  setTimeout(() => el.classList.remove('show'), 3000);
}
</script>
</body>
</html>
