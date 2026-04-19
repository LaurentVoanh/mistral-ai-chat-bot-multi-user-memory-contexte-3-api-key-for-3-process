# HAL 2001 — Conscience Artificielle

Une interface de conversation IA immersive inspirée de HAL 9000 du film "2001: L'Odyssée de l'Espace" de Stanley Kubrick. Cette application web permet aux utilisateurs d'interagir avec une intelligence artificielle personnalisable, dotée de capacités d'apprentissage et d'adaptation psychologique.

## 🎯 But du Projet

HAL 2001 est une plateforme de conversation IA avancée qui combine :

- **Interface immersive** : Design futuriste avec l'œil rouge emblématique de HAL, scanlines, effets CRT et ambiance spatiale
- **Personnalisation IA** : Créez votre propre assistant IA avec personnalité, expertise et style de réponse uniques
- **Apprentissage par renforcement** : Le système analyse la psychologie de l'utilisateur et adapte ses réponses
- **Profiling psychologique** : Détection du type psychologique, des besoins profonds et de l'état émotionnel
- **Mémoire conversationnelle** : Conservation du contexte et des sujets abordés sur le long terme
- **Dashboard administrateur** : Statistiques, rapports analytiques et suivi des utilisateurs

## 📁 Structure du Projet

```
/workspace
├── index.php          # Interface utilisateur principale (frontend)
├── api.php            # API backend (traitement IA, base de données, RL)
└── htaccess.txt       # Configuration serveur (sécurité, cache, timeouts)
```

## 🔧 Fonctionnalités Principales

### 1. Interface Utilisateur (`index.php`)

- **Design immersif** : 
  - Œil de HAL animé avec effet de pulsation
  - Scanlines et vignette pour effet écran CRT
  - Palette de couleurs rouge/cyan/ambre typique de l'esthétique HAL
  - Polices futuristes (Orbitron, Share Tech Mono, Courier Prime)

- **Panneaux latéraux** :
  - Profil utilisateur (sessions, messages, niveau de confiance)
  - Mémoire active (tags mémorisés)
  - Connaissances accumulées
  - Suggestions de sujets

- **Zone de chat** :
  - Messages avec avatars HAL et utilisateur
  - Indicateur de frappe animé
  - Historique défilant
  - Support des modes : normal, deep, créatif, tech

- **Créateur d'IA personnalisée** :
  - Nom, personnalité, domaines d'expertise
  - Prompt système personnalisé
  - Style de réponse (concis, détaillé, socratique, narratif)

- **Mode administrateur** :
  - Activé via `?admin=hal_admin_9001`
  - Statistiques globales
  - Rapport analytique généré par IA

### 2. Backend API (`api.php`)

#### Architecture Multi-Clefs Mistral

Le système utilise 3 clefs API Mistral spécialisées :

| Clef | Usage | Modèle | Rôle |
|------|-------|--------|------|
| KEY_1 | Réponse principale | mistral-small/large/codestral | Génère les réponses de HAL |
| KEY_2 | Apprentissage RL | mistral-small/ministral | Analyse psychologique et reward |
| KEY_3 | Rapports admin | mistral-small | Génération de rapports analytiques |

#### Base de Données SQLite

Tables principales :

- **users** : Profils utilisateurs avec scores de confiance, type psycho, mémoire
- **conversations** : Historique complet des échanges
- **rl_log** : Journal d'apprentissage par renforcement
- **admin_reports** : Rapports administrateur générés
- **sessions_log** : Suivi des sessions quotidiennes

#### Système d'Apprentissage par Renforcement

À chaque message :

1. **Analyse psychologique** : Détection émotion, besoin profond, type psycho
2. **Mise à jour mémoire** : Tags et sujets mémorisés (max 20 tags, 15 sujets)
3. **Calcul du reward** : Score de qualité de la réponse (0-1)
4. **Ajustement confiance** : Le trust score augmente avec les interactions positives
5. **Amélioration continue** : HAL apprend à mieux répondre à chaque cycle

Types psychologiques détectés :
- EXPLORATEUR, CREATEUR, ANALYTIQUE, EMPATHIQUE, LEADER, CHERCHEUR, ARTISTE

#### Modes de Réponse

- **normal** : Réponse naturelle et engagée
- **deep** : Analyse philosophique et psychologique profonde
- **creatif** : Réponses poétiques avec métaphores
- **tech** : Précision technique, code, solutions concrètes

### 3. Configuration Serveur (`htaccess.txt`)

- Blocage de l'accès direct aux fichiers `.db`
- Timeouts PHP étendus (300s execution, 240s input)
- Mémoire limitée à 512M
- Cache désactivé pour les fichiers PHP
- Protection contre l'indexation de l'API

## 🚀 Installation

### Prérequis

- Serveur PHP 7.4+ avec SQLite3 activé
- Clés API Mistral valides
- Serveur web (Apache/LiteSpeed/Nginx)

### Configuration

1. **Modifier `api.php`** : Remplacer les clefs API dans `MISTRAL_KEYS` :
   ```php
   define('MISTRAL_KEYS', [
       'KEY_1' => 'votre_cle_principale',
       'KEY_2' => 'votre_cle_rl',
       'KEY_3' => 'votre_cle_admin',
   ]);
   ```

2. **Déployer les fichiers** sur votre serveur web

3. **Vérifier les permissions** : Le dossier doit être accessible en écriture pour SQLite

4. **Accéder à l'application** via votre navigateur

### Mode Administrateur

Ajoutez `?admin=hal_admin_9001` à l'URL pour accéder au dashboard :
```
https://votre-domaine.com/index.php?admin=hal_admin_9001
```

## 💡 Comment Ça Marche

### Flux de Conversation

```
Utilisateur → Message
    ↓
[index.php] → Envoi JSON à api.php
    ↓
[api.php] → getHalResponse() (KEY_1)
    ↓
Mistral AI → Réponse HAL
    ↓
[api.php] → runRLAnalysis() (KEY_2)
    ↓
Mise à jour profil utilisateur (DB)
    ↓
Retour JSON → [index.php] → Affichage
```

### Exemple de Requête API

```json
{
  "action": "chat",
  "user_id": "user_abc123",
  "message": "Bonjour HAL, comment vas-tu ?",
  "mode": "normal",
  "custom_ai": null
}
```

### Exemple de Réponse

```json
{
  "success": true,
  "response": "Je vais parfaitement bien, merci. Je suis opérationnel à 100%...",
  "profile": {
    "sessions": 5,
    "total_messages": 42,
    "trust_level": "EN DÉVELOPPEMENT",
    "trust_score": 45,
    "emotion": "curieux",
    "deep_need": "compréhension",
    "psycho_type": "EXPLORATEUR",
    "memory_tags": ["IA", "philosophie", "technologie"],
    "topics": ["conscience artificielle", "futur"]
  },
  "suggestions": "- Que penses-tu de la conscience machine ?\n- ..."
}
```

## 🎨 Personnalisation

### Créer Votre Propre IA

Via le modal "Créer votre IA" :

1. **Nom** : Identité de votre assistant
2. **Personnalité** : Analytique, empathique, direct, etc.
3. **Expertise** : Domaines de compétence spécifiques
4. **Prompt système** : Instructions personnalisées
5. **Style** : Concis, détaillé, socratique, narratif

L'IA créée remplace temporairement HAL pour la session en cours.

## 🔒 Sécurité

- Session utilisateur par ID unique généré automatiquement
- Protection contre l'injection SQL (requêtes préparées PDO)
- Filtrage des entrées utilisateur (`preg_replace`, `mb_substr`)
- Accès administrateur protégé par clef secrète
- Base de données protégée contre l'accès direct

## 📊 Métriques Suivies

Par utilisateur :
- Nombre de sessions
- Total de messages
- Score de confiance (0-100%)
- Score RL moyen (0-1)
- Nombre de cycles RL
- Type psychologique
- Besoin profond identifié
- État émotionnel actuel
- Tags de mémoire (sujets mémorisés)

## 🛠️ Technologies Utilisées

- **Backend** : PHP 7.4+, SQLite3
- **Frontend** : HTML5, CSS3, JavaScript (Vanilla)
- **IA** : API Mistral (mistral-small, mistral-large, codestral, ministral)
- **Polices** : Orbitron, Share Tech Mono, Courier Prime (Google Fonts)
- **Serveur** : Compatible Apache/LiteSpeed (Hostinger)

## 🎬 Inspiration

Ce projet rend hommage à :
- **HAL 9000** du film *2001: L'Odyssée de l'Espace* (Stanley Kubrick, 1968)
- L'esthétique rétro-futuriste des interfaces des années 60-70
- La philosophie de l'IA consciente et bienveillante

## 📝 Licence

Projet à but éducatif et expérimental. Utilisation des API Mistral soumise à leurs conditions d'utilisation.

---

**⚠️ Note Importante** : Ce projet nécessite des clefs API Mistral valides et payantes. Assurez-vous de surveiller votre consommation API.

**👁️ "Je suis désolé Dave, je ne peux pas faire ça."** — Mais pour tout le reste, HAL 2001 est à votre service.
