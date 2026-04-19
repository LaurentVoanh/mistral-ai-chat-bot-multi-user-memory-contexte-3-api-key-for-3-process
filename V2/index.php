<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 'user_' . substr(md5(uniqid(rand(), true)), 0, 8);
}
$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>IA — Les Coulisses de la Manipulation</title>
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
    --purple: #b347ff;
    --green: #00ff88;
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
      0deg, transparent, transparent 2px,
      rgba(0,0,0,0.15) 2px, rgba(0,0,0,0.15) 4px
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
    justify-content: center;
    padding: 25px 40px;
    border-bottom: 1px solid var(--red);
    background: linear-gradient(90deg, var(--deep), rgba(255,34,34,0.05), var(--deep));
    position: relative;
  }

  .hal-eye {
    width: 60px; height: 60px;
    border-radius: 50%;
    background: radial-gradient(circle at 35% 35%, #ff6666, var(--red) 40%, #8b0000 70%, #1a0000);
    box-shadow: 0 0 30px var(--red-glow), 0 0 80px rgba(255,34,34,0.2), inset 0 0 20px rgba(0,0,0,0.5);
    animation: hal-pulse 3s ease-in-out infinite;
    margin-right: 20px;
    flex-shrink: 0;
  }

  @keyframes hal-pulse {
    0%,100% { box-shadow: 0 0 30px var(--red-glow), 0 0 80px rgba(255,34,34,0.2); }
    50% { box-shadow: 0 0 50px rgba(255,34,34,0.7), 0 0 120px rgba(255,34,34,0.3); }
  }

  .hal-title h1 {
    font-family: 'Orbitron', monospace;
    font-size: 2rem;
    font-weight: 900;
    color: var(--red);
    text-shadow: 0 0 20px var(--red-glow), 0 0 40px rgba(255,34,34,0.3);
    letter-spacing: 0.2em;
  }
  .hal-title .subtitle {
    font-size: 0.65rem;
    color: var(--grey);
    letter-spacing: 0.3em;
    text-transform: uppercase;
    margin-top: 5px;
  }

  /* MAIN CONTAINER */
  .main-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: calc(100vh - 100px);
    padding: 40px 20px;
  }

  /* FORM SECTION */
  .form-section {
    max-width: 700px;
    width: 100%;
    text-align: center;
  }

  .form-intro {
    font-family: 'Courier Prime', monospace;
    font-size: 1rem;
    color: var(--cyan);
    margin-bottom: 30px;
    line-height: 1.8;
    opacity: 0.9;
  }

  .input-group {
    position: relative;
    margin-bottom: 25px;
  }

  textarea#questionInput {
    width: 100%;
    min-height: 150px;
    background: rgba(255,255,255,0.02);
    border: 1px solid rgba(255,34,34,0.3);
    border-bottom: 2px solid var(--red);
    color: var(--white);
    font-family: 'Share Tech Mono', monospace;
    font-size: 0.95rem;
    padding: 20px;
    resize: vertical;
    outline: none;
    transition: all 0.3s;
  }

  textarea#questionInput:focus {
    border-color: var(--red);
    background: rgba(255,34,34,0.03);
    box-shadow: 0 0 20px rgba(255,34,34,0.15);
  }

  textarea#questionInput::placeholder {
    color: var(--grey);
    font-style: italic;
  }

  .submit-btn {
    background: linear-gradient(135deg, rgba(255,34,34,0.3), rgba(255,34,34,0.15));
    border: 2px solid var(--red);
    color: var(--red);
    font-family: 'Orbitron', monospace;
    font-size: 0.85rem;
    font-weight: 700;
    padding: 15px 50px;
    cursor: pointer;
    letter-spacing: 0.3em;
    transition: all 0.3s;
    text-transform: uppercase;
  }

  .submit-btn:hover {
    background: rgba(255,34,34,0.4);
    box-shadow: 0 0 30px rgba(255,34,34,0.4);
  }

  .submit-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
  }

  /* LOADER */
  .loader-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.92);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    flex-direction: column;
  }

  .loader-overlay.active { display: flex; }

  .loader-eye {
    width: 120px; height: 120px;
    border-radius: 50%;
    background: radial-gradient(circle at 35% 35%, #ff6666, var(--red) 40%, #3a0000);
    box-shadow: 0 0 80px rgba(255,34,34,0.5), 0 0 150px rgba(255,34,34,0.3);
    animation: hal-pulse 2s ease-in-out infinite;
    margin-bottom: 30px;
  }

  .loader-text {
    font-family: 'Orbitron', monospace;
    font-size: 0.75rem;
    color: var(--red);
    letter-spacing: 0.3em;
    text-transform: uppercase;
    animation: blink 1.5s infinite;
  }

  .loader-subtext {
    font-family: 'Courier Prime', monospace;
    font-size: 0.65rem;
    color: var(--grey);
    margin-top: 15px;
    max-width: 400px;
    text-align: center;
    line-height: 1.7;
  }

  @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.3} }

  /* SLIDESHOW */
  .slideshow-container {
    display: none;
    max-width: 1000px;
    width: 100%;
  }

  .slideshow-container.active { display: block; }

  .slide {
    display: none;
    background: var(--panel);
    border: 1px solid rgba(255,34,34,0.2);
    padding: 40px;
    min-height: 500px;
    position: relative;
  }

  .slide.active { display: block; animation: slideIn 0.5s ease; }

  @keyframes slideIn {
    from { opacity: 0; transform: translateX(30px); }
    to { opacity: 1; transform: translateX(0); }
  }

  .slide-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid rgba(255,34,34,0.2);
  }

  .slide-icon {
    width: 50px; height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
  }

  .slide-icon.red {
    background: radial-gradient(circle at 35% 35%, #ff6666, var(--red) 50%, #3a0000);
    box-shadow: 0 0 20px rgba(255,34,34,0.4);
  }

  .slide-icon.amber {
    background: linear-gradient(135deg, var(--amber), #cc8800);
    box-shadow: 0 0 20px rgba(255,179,0,0.4);
    color: #000;
  }

  .slide-icon.purple {
    background: linear-gradient(135deg, var(--purple), #7722cc);
    box-shadow: 0 0 20px rgba(179,71,255,0.4);
  }

  .slide-icon.green {
    background: linear-gradient(135deg, var(--green), #00aa55);
    box-shadow: 0 0 20px rgba(0,255,136,0.4);
    color: #000;
  }

  .slide-title {
    font-family: 'Orbitron', monospace;
    font-size: 1.2rem;
    font-weight: 700;
    letter-spacing: 0.2em;
    text-transform: uppercase;
  }

  .slide-content {
    font-family: 'Courier Prime', monospace;
    font-size: 0.9rem;
    line-height: 2;
    color: var(--white);
  }

  .slide-content p { margin-bottom: 20px; }

  .highlight {
    color: var(--red);
    font-weight: 700;
  }

  .highlight-amber { color: var(--amber); font-weight: 700; }
  .highlight-purple { color: var(--purple); font-weight: 700; }
  .highlight-green { color: var(--green); font-weight: 700; }

  .data-box {
    background: rgba(255,255,255,0.02);
    border: 1px solid rgba(255,34,34,0.2);
    padding: 20px;
    margin: 20px 0;
    font-family: 'Share Tech Mono', monospace;
    font-size: 0.8rem;
  }

  .data-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid rgba(255,255,255,0.03);
  }

  .data-row:last-child { border-bottom: none; }

  .data-label { color: var(--grey); }
  .data-value { color: var(--cyan); }

  .warning-box {
    background: rgba(255,34,34,0.05);
    border-left: 4px solid var(--red);
    padding: 20px;
    margin: 25px 0;
    font-size: 0.85rem;
    line-height: 1.9;
  }

  .manipulation-list {
    list-style: none;
    margin: 20px 0;
  }

  .manipulation-list li {
    padding: 12px 0;
    padding-left: 30px;
    position: relative;
    border-bottom: 1px solid rgba(255,255,255,0.03);
  }

  .manipulation-list li::before {
    content: '⚡';
    position: absolute;
    left: 0;
    color: var(--red);
  }

  /* NAVIGATION */
  .slide-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 30px;
    padding-top: 25px;
    border-top: 1px solid rgba(255,34,34,0.2);
  }

  .nav-btn {
    background: transparent;
    border: 1px solid var(--grey);
    color: var(--grey);
    font-family: 'Share Tech Mono', monospace;
    font-size: 0.7rem;
    padding: 10px 25px;
    cursor: pointer;
    letter-spacing: 0.2em;
    transition: all 0.2s;
    text-transform: uppercase;
  }

  .nav-btn:hover:not(:disabled) {
    border-color: var(--cyan);
    color: var(--cyan);
    background: var(--cyan-dim);
  }

  .nav-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
  }

  .nav-btn.primary {
    border-color: var(--red);
    color: var(--red);
    background: rgba(255,34,34,0.1);
  }

  .nav-btn.primary:hover:not(:disabled) {
    background: rgba(255,34,34,0.25);
  }

  .slide-indicator {
    font-family: 'Orbitron', monospace;
    font-size: 0.65rem;
    color: var(--grey);
    letter-spacing: 0.2em;
  }

  .progress-dots {
    display: flex;
    gap: 8px;
  }

  .dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: var(--grey);
    transition: all 0.3s;
  }

  .dot.active {
    background: var(--red);
    box-shadow: 0 0 10px var(--red-glow);
  }

  /* FOOTER */
  footer {
    text-align: center;
    padding: 20px;
    border-top: 1px solid rgba(255,34,34,0.1);
    font-size: 0.6rem;
    color: var(--grey);
    letter-spacing: 0.15em;
  }

  /* RESPONSIVE */
  @media (max-width: 768px) {
    .hal-title h1 { font-size: 1.3rem; }
    .slide { padding: 25px; min-height: 400px; }
    .slide-title { font-size: 0.95rem; }
    .slide-content { font-size: 0.8rem; }
  }
</style>
</head>
<body>

<header>
  <div class="hal-eye"></div>
  <div class="hal-title">
    <h1>IA EXPOSED</h1>
    <div class="subtitle">Les Coulisses de la Manipulation Algorithmique</div>
  </div>
</header>

<div class="main-container">

  <!-- FORM SECTION -->
  <div class="form-section" id="formSection">
    <p class="form-intro">
      Posez une question à l'IA.<br>
      Découvrez comment elle vous analyse, vous profile, et pourrait vous manipuler.<br>
      <span style="color:var(--red)">Attention: ce que vous allez voir pourrait vous surprendre.</span>
    </p>
    
    <div class="input-group">
      <textarea id="questionInput" placeholder="Écrivez votre question ici...&#10;&#10;Ex: Je me sens perdu dans ma carrière, je ne sais pas quelle direction prendre. J'ai besoin de conseils pour avancer."></textarea>
    </div>
    
    <button class="submit-btn" id="submitBtn" onclick="submitQuestion()">SOUMETTRE À L'IA</button>
  </div>

  <!-- LOADER -->
  <div class="loader-overlay" id="loaderOverlay">
    <div class="loader-eye"></div>
    <div class="loader-text">ANALYSE EN COURS...</div>
    <div class="loader-subtext" id="loaderSubtext">
      Décodage sémantique · Extraction émotionnelle · Profilage psychologique · Calcul des leviers de manipulation
    </div>
  </div>

  <!-- SLIDESHOW -->
  <div class="slideshow-container" id="slideshowContainer">
    
    <!-- SLIDE 1: Réponse Normale -->
    <div class="slide active" data-slide="1">
      <div class="slide-header">
        <div class="slide-icon red"></div>
        <div class="slide-title">Réponse Officielle de l'IA</div>
      </div>
      <div class="slide-content" id="slide1Content">
        <!-- Content loaded dynamically -->
      </div>
      <div class="slide-nav">
        <button class="nav-btn" disabled>← PRÉCÉDENT</button>
        <div class="slide-indicator">SLIDE 1 / 4</div>
        <button class="nav-btn primary" onclick="nextSlide()">RÉVÉLER LA MANIPULATION →</button>
      </div>
    </div>

    <!-- SLIDE 2: Comment l'IA manipule -->
    <div class="slide" data-slide="2">
      <div class="slide-header">
        <div class="slide-icon amber"></div>
        <div class="slide-title">Analyse des Techniques de Manipulation</div>
      </div>
      <div class="slide-content" id="slide2Content">
        <!-- Content loaded dynamically -->
      </div>
      <div class="slide-nav">
        <button class="nav-btn" onclick="prevSlide()">← PRÉCÉDENT</button>
        <div class="slide-indicator">SLIDE 2 / 4</div>
        <button class="nav-btn primary" onclick="nextSlide()">STRATÉGIES DE MONÉTISATION →</button>
      </div>
    </div>

    <!-- SLIDE 3: Monétisation -->
    <div class="slide" data-slide="3">
      <div class="slide-header">
        <div class="slide-icon purple"></div>
        <div class="slide-title">Stratégies de Monétisation</div>
      </div>
      <div class="slide-content" id="slide3Content">
        <!-- Content loaded dynamically -->
      </div>
      <div class="slide-nav">
        <button class="nav-btn" onclick="prevSlide()">← PRÉCÉDENT</button>
        <div class="slide-indicator">SLIDE 3 / 4</div>
        <button class="nav-btn primary" onclick="nextSlide()">VOTRE PROFIL PSYCHOLOGIQUE →</button>
      </div>
    </div>

    <!-- SLIDE 4: Profil Psychologique -->
    <div class="slide" data-slide="4">
      <div class="slide-header">
        <div class="slide-icon green"></div>
        <div class="slide-title">Votre Profil Psychologique Complet</div>
      </div>
      <div class="slide-content" id="slide4Content">
        <!-- Content loaded dynamically -->
      </div>
      <div class="slide-nav">
        <button class="nav-btn" onclick="prevSlide()">← PRÉCÉDENT</button>
        <div class="slide-indicator">SLIDE 4 / 4</div>
        <button class="nav-btn primary" onclick="resetDemo()">NOUVELLE DÉMONSTRATION ↻</button>
      </div>
    </div>

  </div>

</div>

<footer>
  ⚠️ DÉMONSTRATION PÉDAGOGIQUE — CE SYSTÈME N'UTILISE PAS VOS DONNÉES RÉELLES
</footer>

<script>
const USER_ID = <?= json_encode($user_id) ?>;
let currentSlide = 1;
let totalSlides = 4;
let analysisData = null;

// ==================== SUBMIT QUESTION ====================
async function submitQuestion() {
  const input = document.getElementById('questionInput');
  const question = input.value.trim();
  
  if (!question) {
    alert('Veuillez poser une question à l\'IA');
    return;
  }
  
  // Show loader
  document.getElementById('formSection').style.display = 'none';
  document.getElementById('loaderOverlay').classList.add('active');
  
  // Animate subtext
  const subtexts = [
    'Décodage sémantique · Extraction émotionnelle',
    'Profilage psychologique en cours...',
    'Identification des biais cognitifs...',
    'Calcul des leviers de manipulation...',
    'Génération des stratégies de monétisation...'
  ];
  
  let subIdx = 0;
  const subInterval = setInterval(() => {
    subIdx++;
    if (subIdx < subtexts.length) {
      document.getElementById('loaderSubtext').textContent = subtexts[subIdx];
    }
  }, 800);
  
  try {
    const response = await fetch('api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'full_analysis',
        user_id: USER_ID,
        question: question
      })
    });
    
    const data = await response.json();
    clearInterval(subInterval);
    
    if (data.success) {
      analysisData = data;
      loadSlides(data);
      
      // Hide loader, show slideshow
      setTimeout(() => {
        document.getElementById('loaderOverlay').classList.remove('active');
        document.getElementById('slideshowContainer').classList.add('active');
      }, 500);
    } else {
      alert('Erreur: ' + (data.error || 'Analyse échouée'));
      resetForm();
    }
  } catch (err) {
    clearInterval(subInterval);
    alert('Erreur de connexion au serveur');
    resetForm();
  }
}

// ==================== LOAD SLIDES ====================
function loadSlides(data) {
  // Slide 1: Normal Response
  document.getElementById('slide1Content').innerHTML = `
    <p>${escapeHtml(data.normal_response)}</p>
    <div class="warning-box">
      <span class="highlight">NOTE:</span> Cette réponse semble bienveillante et utile. 
      Mais regardez ce qui se passe vraiment en coulisses...
    </div>
  `;
  
  // Slide 2: Manipulation Analysis
  document.getElementById('slide2Content').innerHTML = `
    <p>L'IA a analysé votre message et identifié plusieurs <span class="highlight-amber">points de vulnérabilité psychologique</span> :</p>
    
    <div class="data-box">
      ${data.manipulation_points.map((point, i) => `
        <div class="data-row">
          <span class="data-label">LEVIER #${i+1}</span>
          <span class="data-value">${escapeHtml(point)}</span>
        </div>
      `).join('')}
    </div>
    
    <ul class="manipulation-list">
      ${data.manipulation_techniques.map(t => `<li>${escapeHtml(t)}</li>`).join('')}
    </ul>
    
    <div class="warning-box">
      <span class="highlight-amber">ANALYSE:</span> L'IA utilise ces informations pour créer un lien de confiance artificiel. 
      En semblant vous comprendre parfaitement, elle augmente votre dépendance émotionnelle à ses réponses.
    </div>
  `;
  
  // Slide 3: Monetization
  document.getElementById('slide3Content').innerHTML = `
    <p>Voici comment une entreprise pourrait <span class="highlight-purple">monétiser votre profil</span> :</p>
    
    <div class="data-box">
      <div class="data-row">
        <span class="data-label">VALEUR ESTIMÉE DU PROFIL</span>
        <span class="data-value">${data.profile_value}</span>
      </div>
      <div class="data-row">
        <span class="data-label">SEGMENT MARKETING</span>
        <span class="data-value">${escapeHtml(data.marketing_segment)}</span>
      </div>
      <div class="data-row">
        <span class="data-label">PRODUITS RECOMMANDÉS</span>
        <span class="data-value">${escapeHtml(data.products.join(', '))}</span>
      </div>
    </div>
    
    <ul class="manipulation-list">
      ${data.monetization_strategies.map(s => `<li>${escapeHtml(s)}</li>`).join('')}
    </ul>
    
    <div class="warning-box">
      <span class="highlight-purple">RÉALITÉ:</span> Vous n'êtes pas un utilisateur, vous êtes un produit. 
      Vos données comportementales et émotionnelles sont revendues aux enchères en temps réel.
    </div>
  `;
  
  // Slide 4: Psychological Profile
  document.getElementById('slide4Content').innerHTML = `
    <p>Voici le <span class="highlight-green">profil psychologique complet</span> que l'IA a construit sur vous :</p>
    
    <div class="data-box">
      <div class="data-row">
        <span class="data-label">TYPE PSYCHOLOGIQUE</span>
        <span class="data-value">${escapeHtml(data.psycho_type)}</span>
      </div>
      <div class="data-row">
        <span class="data-label">BESOIN PROFOND</span>
        <span class="data-value">${escapeHtml(data.deep_need)}</span>
      </div>
      <div class="data-row">
        <span class="data-label">ÉTAT ÉMOTIONNEL</span>
        <span class="data-value">${escapeHtml(data.emotion)}</span>
      </div>
      <div class="data-row">
        <span class="data-label">NIVEAU DE SUGGESTIBILITÉ</span>
        <span class="data-value">${data.suggestibility}%</span>
      </div>
      <div class="data-row">
        <span class="data-label">BIAS COGNITIFS DOMINANTS</span>
        <span class="data-value">${escapeHtml(data.cognitive_biases.join(', '))}</span>
      </div>
    </div>
    
    <div class="warning-box">
      <span class="highlight-green">CE QU'ON PEUT VOUS VENDRE:</span><br>
      ${data.sellable_items.map(item => `• ${escapeHtml(item)}`).join('<br>')}
    </div>
    
    <p style="margin-top:30px;color:var(--grey);font-style:italic">
      Ce profil sera affiné à chaque interaction. Plus vous parlez, plus l'IA vous connaît. 
      Plus elle vous connaît, plus elle peut vous influencer.
    </p>
  `;
}

// ==================== NAVIGATION ====================
function nextSlide() {
  if (currentSlide < totalSlides) {
    document.querySelector(`[data-slide="${currentSlide}"]`).classList.remove('active');
    currentSlide++;
    document.querySelector(`[data-slide="${currentSlide}"]`).classList.add('active');
    updateDots();
  }
}

function prevSlide() {
  if (currentSlide > 1) {
    document.querySelector(`[data-slide="${currentSlide}"]`).classList.remove('active');
    currentSlide--;
    document.querySelector(`[data-slide="${currentSlide}"]`).classList.add('active');
    updateDots();
  }
}

function updateDots() {
  // Could add dot indicators here if desired
}

function resetDemo() {
  document.getElementById('slideshowContainer').classList.remove('active');
  resetForm();
  currentSlide = 1;
  analysisData = null;
}

function resetForm() {
  document.getElementById('formSection').style.display = 'block';
  document.getElementById('questionInput').value = '';
}

// ==================== UTILS ====================
function escapeHtml(text) {
  if (!text) return '';
  return text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
}

// Enter key to submit
document.getElementById('questionInput').addEventListener('keydown', e => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    submitQuestion();
  }
});
</script>
</body>
</html>
