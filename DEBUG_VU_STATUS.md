# Guide de test et débogage - Statut "Vu"

## Préparation

1. **Démarrer les services :**
   ```powershell
   # Terminal 1
   php artisan serve
   
   # Terminal 2
   php artisan reverb:start
   ```

2. **Ouvrir le fichier de log Laravel :**
   ```powershell
   # Terminal 3 - Surveiller les logs en temps réel
   Get-Content storage\logs\laravel.log -Wait -Tail 20
   ```

3. **Ouvrir 2 navigateurs :**
   - **Navigateur A (User 1)** : http://localhost:8000/chat-test
   - **Navigateur B (User 2)** : http://localhost:8000/chat-test

4. **Ouvrir la console développeur (F12)** dans les deux navigateurs

## Test du statut "Vu"

### Étape 1 : User A envoie un message
```
User A:
- Se connecter avec user1@example.com / password
- Ouvrir la conversation avec User B
- Envoyer un message : "Hello World"

Résultat attendu:
- Le message s'affiche
- PAS de statut "Vu" à côté
```

### Étape 2 : User B ouvre la conversation
```
User B:
- Se connecter avec user2@example.com / password
- Ouvrir la conversation avec User A

Résultat attendu:
- Les messages s'affichent
- Console User B: Voir logs "📖 [READ] Marking conversation as read"
- Log Laravel: "[ConversationController] Conversation marked as read"
- Log Laravel: "[ConversationController] Broadcasting MessageRead via Pusher SDK"
- Log Laravel: "[ConversationController] MessageRead event broadcasted successfully"
```

### Étape 3 : User A voit "Vu"
```
User A:
- Ne PAS changer de page
- Ne PAS rafraîchir

Résultat attendu:
- Console User A: Voir logs "👁️ [READ EVENT] Événement message.read reçu!"
- Console User A: "✅ [READ EVENT] Stockage du statut 'Vu'"
- Console User A: "✅ [READ EVENT] Affichage du statut (conversation ouverte)"
- Le message affiche "Vu" en bleu
```

### Étape 4 : Le compteur augmente
```
User A:
- Attendre 60 secondes

Résultat attendu:
- "Vu" devient "1 min"
- Après 120 secondes : "2 min"
- Après 180 secondes : "3 min"
- etc.
```

## Points de contrôle (Checkpoints)

### ✅ Checkpoint 1 : WebSocket connecté
**User A console:**
```
✅ Connected to Reverb
🔔 [SUBSCRIBE ALL] Subscribing to all X conversations
✅ [SUBSCRIBE ALL] Subscribed to X channels
```

**User B console:**
```
✅ Connected to Reverb
🔔 [SUBSCRIBE ALL] Subscribing to all X conversations
✅ [SUBSCRIBE ALL] Subscribed to X channels
```

### ✅ Checkpoint 2 : Message envoyé
**User A console:**
```
📤 [SEND] Sending message with socket_id: ...
✅ [SEND] Message envoyé
🧹 [VU] Statuts effacés (nouveau message envoyé)
```

**Log Laravel:**
```
[MessageController] Broadcasting message
[MessageController] Pusher SDK broadcast sent successfully
```

### ✅ Checkpoint 3 : User B marque comme lu
**User B console:**
```
📖 [READ] Marking conversation as read: 1
```

**Log Laravel:**
```
[ConversationController] Conversation marked as read
  - conversation_id: 1
  - user_id: 2
  - read_at: 2026-01-09T14:30:45.123456Z
  - unread_count: 0
[ConversationController] Broadcasting MessageRead via Pusher SDK
  - channel: private-conversation.1.test-app-001
  - event: message.read
  - data: {
      conversation_id: 1,
      reader: {id: 2, name: "User 2"},
      read_at: "2026-01-09T14:30:45.123456Z"
    }
[ConversationController] MessageRead event broadcasted successfully
```

### ✅ Checkpoint 4 : User A reçoit l'événement
**User A console:**
```
👁️ [READ EVENT] ========================================
👁️ [READ EVENT] Événement message.read reçu!
👁️ [READ EVENT] Full data: {
  "conversation_id": 1,
  "reader": {
    "id": 2,
    "name": "User 2"
  },
  "read_at": "2026-01-09T14:30:45.123456Z"
}
👁️ [READ EVENT] conversation_id: 1
👁️ [READ EVENT] reader.id: 2
👁️ [READ EVENT] reader.name: User 2
👁️ [READ EVENT] read_at: 2026-01-09T14:30:45.123456Z
👁️ [READ EVENT] Current user ID: 1
👁️ [READ EVENT] Current conversation ID: 1
👁️ [READ EVENT] ========================================
✅ [READ EVENT] Traitement du statut "Vu"
💾 [READ EVENT] Statut stocké pour conversation 1
📱 [READ EVENT] Conversation ouverte? true
✅ [READ EVENT] Affichage immédiat (conversation ouverte)
✅ [VU] Statut existant trouvé, affichage immédiat: {readerId: 2, readerName: "User 2", readAt: "..."}
✅ [VU] Affiché: Vu
⏱️ [VU] Intervalle démarré (60s)
```

## Problèmes connus et solutions

### ❌ Problème : Pas de logs "👁️ [READ EVENT]"
**Cause possible :** User A n'est pas abonné au canal de la conversation

**Solution :**
```
Vérifier dans la console User A:
🔔 [SUBSCRIBE] Subscribing to channel: private-conversation.1.test-app-001
✅ [SUBSCRIBE] Successfully subscribed to: private-conversation.1.test-app-001
```

Si ce n'est PAS le cas:
- Vérifier que Reverb tourne
- Vérifier la configuration channels.php
- Recharger la page et reconnecter WebSocket
```

### ❌ Problème : Log Laravel "Failed to broadcast"
**Cause possible :** Erreur de configuration Reverb

**Solution :**
```
Vérifier la configuration dans config/broadcasting.php:
'reverb' => [
    'key' => env('REVERB_APP_KEY'),
    'secret' => env('REVERB_APP_SECRET'),
    'app_id' => env('REVERB_APP_ID'),
    'options' => [
        'host' => env('REVERB_HOST', '127.0.0.1'),
        'port' => env('REVERB_PORT', 8080),
        'scheme' => env('REVERB_SCHEME', 'http'),
        'useTLS' => env('REVERB_SCHEME', 'http') === 'https',
    ],
],
```

### ❌ Problème : Event reçu mais pas affiché
**Cause possible :** Condition dans le listener incorrecte

**Solution :**
```
Vérifier dans la console User A:
👁️ [READ EVENT] Conversation ouverte? true
  → Si false: Le listener stocke le statut mais ne l'affiche pas
  
Solution: Ouvrir la conversation avec User B dans User A
```

### ❌ Problème : Statut "Vu" n'apparaît PAS sur le message
**Cause possible :** Le message n'a pas l'élément read-status

**Solution :**
```
1. Ouvrir les DevTools (F12)
2. Sélectionner le message envoyé
3. Vérifier s'il y a un élément: <span id="read-status-123"></span>

Si NON: Le problème est dans appendMessage()
Vérifier que le message a data-message-id="123"
```

### ❌ Problème : "Vu" apparaît puis disparaît
**Cause possible :** clearAllSeenStatus() appelé par erreur

**Solution :**
```
Vérifier dans la console:
🧹 [VU] Statuts effacés (nouveau message envoyé)

Si ce log apparaît sans que User A envoie un message:
- Le problème est dans la logique de déclenchement
- Vérifier que clearAllSeenStatus() n'est appelé que dans send message
```

## Logs de débogage à vérifier

### Log Laravel (storage/logs/laravel.log)
```
✅ Doit contenir:
[ConversationController] Conversation marked as read
[ConversationController] Broadcasting MessageRead via Pusher SDK
[ConversationController] MessageRead event broadcasted successfully

❌ Ne doit PAS contenir:
[ConversationController] Failed to broadcast MessageRead
```

### Console JavaScript User A (expéditeur)
```
✅ Doit contenir:
👁️ [READ EVENT] Événement message.read reçu!
✅ [READ EVENT] Traitement du statut "Vu"
💾 [READ EVENT] Statut stocké pour conversation X
📱 [READ EVENT] Conversation ouverte? true
✅ [READ EVENT] Affichage immédiat (conversation ouverte)
✅ [VU] Affiché: Vu
⏱️ [VU] Intervalle démarré (60s)

⏱️ Puis toutes les 60s:
⏱️ [VU] Mise à jour automatique: 1 min
⏱️ [VU] Mise à jour automatique: 2 min
...
```

### Console JavaScript User B (lecteur)
```
✅ Doit contenir:
📖 [READ] Marking conversation as read: X
📖 [READ] Badge animation started
📖 [READ] API response: {success: true, message: "..."}
✅ [READ] Badge reset to 0 and conversation list rendered
```

## Test alternatif : Sans changer de conversation

### Scénario
```
1. User A envoie un message
2. User B ouvre la conversation (sans avoir été ouverte avant)
3. User A voit "Vu"
4. User B ferme la conversation
5. User A voit toujours "1 min", "2 min", etc.
```

### Résultat attendu
- Le statut "Vu" reste affiché
- Le compteur continue d'augmenter indéfiniment
- Le statut ne disparaît PAS quand User B ferme l'app

