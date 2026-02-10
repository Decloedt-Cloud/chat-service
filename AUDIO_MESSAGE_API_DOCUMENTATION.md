# Documentation API - Messages Vocaux

## Vue d'ensemble

Ce document décrit l'implémentation des messages vocaux dans le service de chat.

## Livrables

### Exemple de payload API pour l'envoi audio

#### Endpoint
```
POST /api/v1/conversations/{conversationId}/messages
```

#### Headers
```json
{
  "Authorization": "Bearer {your_token}",
  "X-Application-ID": "test-app-001",
  "X-Socket-ID": "{socket_id_optional}",
  "Content-Type": "multipart/form-data"
}
```

#### Body (FormData)
```javascript
const formData = new FormData();
formData.append('content', ''); // Optionnel : peut être vide pour un message vocal
formData.append('file', audioFile); // Le fichier audio (Blob ou File)
formData.append('type', 'audio'); // Type de message
formData.append('duration', 15); // Durée en secondes (entier)
```

#### Exemple complet en JavaScript
```javascript
async function sendVoiceMessage(conversationId, audioFile, duration) {
  const formData = new FormData();
  formData.append('content', '');
  formData.append('file', audioFile);
  formData.append('type', 'audio');
  formData.append('duration', duration);

  const response = await fetch(
    `http://localhost:8000/api/v1/conversations/${conversationId}/messages`,
    {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'X-Application-ID': 'test-app-001',
        // Ne PAS inclure Content-Type pour FormData (le navigateur le définit automatiquement)
      },
      body: formData
    }
  );

  return await response.json();
}
```

## Spécifications techniques

### Format audio supporté

- **MIME Types supportés**: `audio/mpeg`, `audio/mp3`, `audio/wav`, `audio/ogg`, `audio/mp4`, `audio/x-m4a`, `audio/aac`
- **Extensions recommandées**: `.mp3`, `.wav`, `.ogg`, `.m4a`, `.webm`
- **Format d'enregistrement Web**: `audio/webm;codecs=opus` (préféré) ou `audio/mp4` (fallback)

### Limites

- **Taille max**: 5 Mo (5,242,880 octets)
- **Durée max**: 300 secondes (5 minutes)
- **Formats acceptés**: MP3, WAV, OGG, M4A, WebM

### Validation backend

Les fichiers audio sont validés avec les règles suivantes :

```php
'file' => [
    'required',
    'file',
    'mimes:audio/mpeg,audio/mp3,audio/wav,audio/ogg,audio/mp4,audio/x-m4a,audio/aac',
    'max:5120', // 5MB max
],
'duration' => [
    'nullable',
    'integer',
    'min:0',
    'max:300', // Durée max 5 minutes
],
```

### Stockage

- **Répertoire**: `storage/app/public/chat-audios/{YYYY}/{MM}/`
- **Format URL publique**: `http://localhost:8000/storage/chat-audios/{YYYY}/{MM}/{filename}`
- **Nom de fichier**: `voice-message-{timestamp}.{extension}`

### Structure en base de données

```sql
-- Table messages
ALTER TABLE messages ADD COLUMN duration INT UNSIGNED NULL AFTER file_size;
```

- `type`: `'audio'` pour les messages vocaux
- `file_url`: URL publique du fichier audio
- `file_name`: Nom original du fichier
- `file_size`: Taille en octets
- `duration`: Durée en secondes (entier)

### Réponse API réussie

```json
{
  "success": true,
  "message": "Message envoyé",
  "data": {
    "id": 123,
    "conversation_id": 1,
    "user_id": 2,
    "content": "",
    "type": "audio",
    "file_url": "http://localhost:8000/storage/chat-audios/2026/01/voice-message-17051234567890.webm",
    "file_name": "voice-message-17051234567890.webm",
    "file_size": 524288,
    "duration": 15,
    "is_edited": false,
    "edited_at": null,
    "created_at": "2026-01-13T12:00:00.000000Z",
    "updated_at": "2026-01-13T12:00:00.000000Z"
  }
}
```

### Diffusion temps réel (WebSocket)

Le message audio est diffusé via Reverb/Pusher avec les données :

```json
{
  "message": {
    "id": 123,
    "conversation_id": 1,
    "user_id": 2,
    "content": "",
    "type": "audio",
    "file_url": "http://localhost:8000/storage/chat-audios/2026/01/voice-message-17051234567890.webm",
    "file_name": "voice-message-17051234567890.webm",
    "file_size": 524288,
    "duration": 15,
    "is_edited": false,
    "edited_at": null,
    "created_at": "2026-01-13T12:00:00.000000Z",
    "updated_at": "2026-01-13T12:00:00.000000Z"
  },
  "sender": {
    "id": 2,
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

## Implémentation Frontend

### Enregistrement audio (MediaRecorder API)

```javascript
// Variables d'état
let audioRecorder = null;
let audioChunks = [];
let isRecording = false;
let recordingStartTime = null;

// Démarrer l'enregistrement
async function startRecording() {
  const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
  
  const mimeType = MediaRecorder.isTypeSupported('audio/webm;codecs=opus')
    ? 'audio/webm;codecs=opus'
    : 'audio/mp4';
  
  audioRecorder = new MediaRecorder(stream, { mimeType });
  audioChunks = [];
  
  audioRecorder.ondataavailable = (event) => {
    if (event.data.size > 0) {
      audioChunks.push(event.data);
    }
  };
  
  audioRecorder.onstop = () => {
    const blob = new Blob(audioChunks, { type: audioRecorder.mimeType });
    const duration = Math.round((Date.now() - recordingStartTime) / 1000);
    
    // Convertir en File
    const file = new File([blob], `voice-message-${Date.now()}.webm`, {
      type: mimeType,
      lastModified: Date.now()
    });
    
    // Envoyer au backend
    sendVoiceMessage(file, duration);
  };
  
  audioRecorder.start();
  isRecording = true;
  recordingStartTime = Date.now();
}

// Arrêter l'enregistrement
function stopRecording() {
  if (audioRecorder && audioRecorder.state !== 'inactive') {
    audioRecorder.stop();
  }
  isRecording = false;
}
```

### Prévisualisation avant envoi

```html
<!-- Prévisualisation audio -->
<div class="audio-preview">
  <audio controls id="audioPreview"></audio>
  <span class="audio-duration">0:15</span>
  <button onclick="cancelAudio()">✕</button>
  <button onclick="sendAudio()">Envoyer 📤</button>
</div>
```

### Affichage d'un message audio reçu

```javascript
function displayAudioMessage(message) {
  const duration = formatDuration(message.duration); // "0:15"
  
  return `
    <div class="audio-message">
      <audio controls src="${message.file_url}"></audio>
      <span class="duration-badge">🎤 ${duration}</span>
    </div>
  `;
}
```

## Séquence utilisateur

1. **Clic sur le bouton micro** 🎤
   - Demande d'autorisation d'accès au microphone (browser permission)

2. **Après autorisation** :
   - **Clic sur "Enregistrer"** → Démarre l'enregistrement
   - **Overlay d'enregistrement** affiché avec timer en temps réel
   - **Point rouge** clignotant pendant l'enregistrement

3. **Pendant l'enregistrement** :
   - Timer affiché : "0:00", "0:05", "0:10", etc.
   - Limite automatique à 5 minutes (300 secondes)

4. **Arrêt de l'enregistrement** :
   - Clic sur "Stop" → Arrête l'enregistrement
   - Création du Blob audio
   - Calcul de la durée

5. **Prévisualisation** :
   - Player audio affiché pour écouter le message avant envoi
   - Durée affichée
   - Bouton **Supprimer** ❌
   - Bouton **Envoyer** 📤

6. **Envoi explicite** :
   - Message NON envoyé automatiquement
   - Envoi uniquement après clic sur bouton "Envoyer"
   - Upload du fichier + durée via FormData

7. **Affichage dans le chat** :
   - Player audio avec contrôles
   - Badge durée : "🎤 0:15"
   - Style différent des messages texte/image

## Contraintes et conditions

✅ **Respecté** :
- Ne pas casser le chat existant
- Compatibilité avec messages texte et image existants
- Boutons d'upload image et micro coexistent
- Validation backend (format, taille, durée)
- Stockage séparé dans `storage/chat-audios`
- Diffusion temps réel WebSocket
- Pas d'envoi automatique (explicite uniquement)

## Fichiers modifiés

### Backend
1. `database/migrations/2026_01_13_120000_add_duration_to_messages_table.php` - Nouveau
2. `app/Models/Message.php` - Ajout `duration` dans fillable et casts
3. `app/Http/Requests/StoreMessageRequest.php` - Ajout validation duration et mimes audio
4. `app/Http/Controllers/Api/V1/MessageController.php` - Gestion fichiers audio

### Frontend
1. `resources/views/chat-test.blade.php` - Interface enregistrement + MediaRecorder API

### Stockage
1. `storage/app/public/chat-audios/` - Répertoire créé pour fichiers audio

## Instructions de déploiement

1. **Exécuter la migration** :
   ```bash
   php artisan migrate
   ```

2. **Vérifier le symlink** :
   ```bash
   php artisan storage:link
   ```

3. **Tester l'enregistrement** :
   - Accéder à la page de chat
   - Cliquer sur le bouton micro 🎤
   - Autoriser l'accès au microphone
   - Enregistrer un message
   - Prévisualiser
   - Envoyer

## Notes importantes

- Le navigateur doit supporter `MediaRecorder` API (Chrome, Firefox, Edge, Safari moderne)
- L'accès au microphone nécessite HTTPS ou localhost
- Les fichiers audio sont stockés dans le disque `public`
- La durée est en secondes (entier)
- Le format d'enregistrement dépend du support du navigateur

