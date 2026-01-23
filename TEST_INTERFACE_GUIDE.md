# Guide d'Utilisation de l'Interface de Test

🚀 **Interface de test complète pour le Chat Service Laravel**

---

## 📋 Prérequis

Avant de commencer, assurez-vous d'avoir:

1. ✅ **Base de données MySQL configurée** (via XAMPP)
2. ✅ **Migrations exécutées**: `php artisan migrate`
3. ✅ **Au moins 2 utilisateurs créés** (pour tester les conversations)
4. ✅ **Reverb en cours d'exécution**: `php artisan reverb:start`
5. ✅ **Serveur Laravel en cours d'exécution**: `php artisan serve`

---

## 🚀 Démarrage Rapide

### 1. Démarrer les serveurs

Ouvrez **3 terminaux** et exécutez:

**Terminal 1 - Serveur Laravel:**
```bash
php artisan serve
```
Le serveur démarre sur `http://localhost:8000`

**Terminal 2 - Serveur Reverb:**
```bash
php artisan reverb:start
```
Le serveur WebSocket démarre sur `ws://localhost:8080`

**Terminal 3 - Tinker (créer des utilisateurs):**
```bash
php artisan tinker
```

### 2. Créer des utilisateurs de test

Dans Tinker, exécutez:

```php
// Créer le premier utilisateur
$user1 = \App\Models\User::create([
    'name' => 'Alice Johnson',
    'email' => 'alice@example.com',
    'password' => bcrypt('password123')
]);

// Créer le deuxième utilisateur
$user2 = \App\Models\User::create([
    'name' => 'Bob Smith',
    'email' => 'bob@example.com',
    'password' => bcrypt('password123')
]);

// Créer un troisième utilisateur (optionnel)
$user3 = \App\Models\User::create([
    'name' => 'Charlie Brown',
    'email' => 'charlie@example.com',
    'password' => bcrypt('password123')
]);
```

**Sortez de Tinker avec:** `exit`

### 3. Accéder à l'interface de test

Ouvrez votre navigateur et accédez à:

```
http://localhost:8000/chat-test
```

---

## 🎯 Fonctionnalités de l'Interface

### 1. Page de Connexion

![Login Page](https://via.placeholder.com/400x300?text=Login+Page)

**Champs:**
- **Email**: Adresse email de l'utilisateur
- **Password**: Mot de passe (password123)
- **Device Name**: Nom de l'appareil (ex: web-test, mobile, etc.)

**Exemple:**
```
Email: alice@example.com
Password: password123
Device Name: web-test
```

Cliquez sur **"Se Connecter"**

---

### 2. Page de Chat

![Chat Page](https://via.placeholder.com/800x500?text=Chat+Page)

#### Header
- **Statut de connexion**:
  - 🟢 ✅ Connecté - WebSocket actif
  - 🟡 ⚡ Déconnecté - WebSocket inactif
  - 🔴 ❌ Erreur - Erreur de connexion
- **Informations utilisateur**: Affiche le nom de l'utilisateur connecté
- **Bouton Déconnexion**: Se déconnecte de la session

#### Double-cliquez sur le statut de connexion pour **ouvrir la configuration**

---

### 3. Configuration WebSocket

![Config Modal](https://via.placeholder.com/400x300?text=Configuration)

**Double-cliquez** sur le badge de statut de connexion pour ouvrir la configuration.

**Paramètres:**
- **Reverb Key**: Votre clé Reverb (depuis `.env`)
- **Reverb Host**: Hôte Reverb (default: localhost)
- **Reverb Port**: Port Reverb (default: 8080)
- **Application ID**: ID de l'application (default: default)
- **API Base URL**: URL de base de l'API (default: http://localhost:8000)

**Après modification:**
1. Cliquez sur **"Sauvegarder"**
2. L'interface se reconnecte automatiquement
3. La configuration est sauvegardée dans le navigateur (localStorage)

---

### 4. Liste des Conversations

![Conversations List](https://via.placeholder.com/300x400?text=Conversations)

**Actions:**
- **+ Nouvelle**: Créer une nouvelle conversation
- **Clic sur une conversation**: Ouvrir la conversation et charger les messages

**Informations affichées:**
- Nom de la conversation
- Dernier message
- Badge rouge si messages non lus

---

### 5. Créer une Conversation

1. Cliquez sur **"+ Nouvelle"**
2. Sélectionnez un utilisateur dans la liste
3. La conversation est créée automatiquement

**Note:** Pour le moment, seuls des utilisateurs de test sont disponibles. Vous pouvez modifier le code pour ajouter un endpoint de liste d'utilisateurs.

---

### 6. Zone de Chat

![Chat Area](https://via.placeholder.com/500x600?text=Chat+Area)

**Composants:**
- **En-tête**: Nom et description de la conversation
- **Messages**: Historique des messages
- **Indicateur de frappe** (à implémenter): Affiche quand quelqu'un tape
- **Zone de saisie**: Champ pour écrire des messages

**Couleurs des messages:**
- 🟢 Vert clair (droite): Messages envoyés par vous
- ⚪ Blanc (gauche): Messages reçus

**Informations par message:**
- Nom de l'expéditeur
- Contenu du message
- Badge "Modifié" si le message a été édité
- Heure d'envoi

---

### 7. Envoyer un Message

1. Sélectionnez une conversation
2. Tapez votre message dans la zone de saisie
3. Appuyez sur **Entrée** ou cliquez sur **"Envoyer"**

**Le message apparaît instantanément** via WebSocket !

---

## 🧪 Scénarios de Test

### Scénario 1: Test de Base (Utilisateur Seul)

1. Connectez-vous en tant qu'Alice
2. Créez une conversation avec Bob
3. Envoyez quelques messages
4. Vérifiez que les messages apparaissent

**Résultat attendu:**
- ✅ Messages envoyés s'affichent à droite (vert)
- ✅ Statut de connexion: ✅ Connecté
- ✅ WebSocket reçoit l'événement `message.sent`

---

### Scénario 2: Temps Réel (2 Navigateurs)

**Objectif:** Tester la réception des messages en temps réel

1. **Ouvrez 2 fenêtres de navigateur différentes**
2. **Fenêtre 1:** Connectez-vous en tant qu'Alice
3. **Fenêtre 2:** Connectez-vous en tant que Bob
4. **Fenêtre 1:** Créez une conversation avec Bob
5. **Fenêtre 2:** La conversation apparaît automatiquement (après rechargement)
6. **Fenêtre 1:** Sélectionnez la conversation avec Bob
7. **Fenêtre 2:** Sélectionnez la conversation avec Alice
8. **Fenêtre 1:** Envoyez un message: "Salut Bob !"
9. **Fenêtre 2:** ✅ Le message apparaît **instantanément** !

**Résultat attendu:**
- ✅ Message reçu en temps réel (sans rafraîchissement)
- ✅ Les deux utilisateurs peuvent converser
- ✅ WebSocket fonctionne correctement

---

### Scénario 3: Messages Non Lus

1. Connectez-vous en tant qu'Alice
2. Créez une conversation avec Bob
3. Déconnectez-vous
4. Connectez-vous en tant que Bob
5. Envoyez 3 messages à Alice
6. Déconnectez-vous
7. Reconnectez-vous en tant qu'Alice

**Résultat attendu:**
- ✅ Badge rouge avec le chiffre "3" sur la conversation
- ✅ Messages non lus comptés correctement
- ✅ Après clic, les messages sont marqués comme lus (badge disparaît)

---

### Scénario 4: Configuration Reverb

1. Connectez-vous (si WebSocket ne se connecte pas)
2. Double-cliquez sur le statut de connexion (❌ Erreur ou ⚡ Déconnecté)
3. Vérifiez la configuration:
   - Reverb Key: `your-reverb-key` (vérifiez `.env`)
   - Reverb Host: `localhost`
   - Reverb Port: `8080`
   - API Base URL: `http://localhost:8000`
4. Corrigez si nécessaire
5. Sauvegardez

**Pour trouver votre Reverb Key:**
Ouvrez le fichier `.env` et cherchez:
```env
REVERB_APP_KEY=your-reverb-key-here
```

---

### Scénario 5: Test Multi-Device

1. **Ordinateur:** Connectez-vous en tant qu'Alice
2. **Téléphone (via réseau local):** Connectez-vous en tant que Bob
   - Utilisez l'IP de votre ordinateur: `http://192.168.x.x:8000/chat-test`
3. Créez et utilisez une conversation

**Résultat attendu:**
- ✅ Messages synchronisés entre les appareils
- ✅ Temps réel fonctionnel sur tous les appareils

---

## 🔧 Dépannage

### Problème: "Erreur de connexion" WebSocket

**Symptôme:** Statut affiche ❌ Erreur

**Solutions:**

1. **Vérifiez que Reverb tourne:**
   ```bash
   php artisan reverb:start
   ```
   Devrait afficher: `Server started on ws://0.0.0.0:8080`

2. **Vérifiez la configuration:**
   - Double-cliquez sur le statut de connexion
   - Vérifiez que les paramètres correspondent à votre `.env`

3. **Vérifiez votre fichier `.env`:**
   ```env
   BROADCAST_CONNECTION=reverb
   REVERB_APP_ID=chat-service
   REVERB_APP_KEY=your-actual-key-here
   REVERB_APP_SECRET=your-actual-secret-here
   REVERB_HOST=0.0.0.0
   REVERB_PORT=8080
   REVERB_SCHEME=http
   ```

4. **Vérifiez la console du navigateur (F12):**
   - Regardez les erreurs dans l'onglet Console
   - Messages d'erreur communs:
     - `Failed to connect to ws://localhost:8080`
     - `401 Unauthorized` → Vérifiez le token

---

### Problème: "Les identifiants fournis sont incorrects"

**Symptôme:** Impossible de se connecter

**Solutions:**

1. **Vérifiez les identifiants:**
   ```php
   // Dans Tinker, lister les utilisateurs
   \App\Models\User::all()
   ```

2. **Réinitialisez un mot de passe:**
   ```php
   // Dans Tinker
   $user = \App\Models\User::where('email', 'alice@example.com')->first();
   $user->password = bcrypt('password123');
   $user->save();
   ```

3. **Créez un nouvel utilisateur:**
   ```php
   // Dans Tinker
   \App\Models\User::create([
       'name' => 'New User',
       'email' => 'new@example.com',
       'password' => bcrypt('password123')
   ]);
   ```

---

### Problème: "Aucune conversation"

**Symptôme:** Liste des conversations vide

**Solutions:**

1. **Créez une conversation:**
   - Cliquez sur "+ Nouvelle"
   - Sélectionnez un utilisateur

2. **Vérifiez dans la base de données:**
   ```sql
   SELECT * FROM conversations;
   ```

3. **Vérifiez que vous êtes participant:**
   ```sql
   SELECT * FROM conversation_participants WHERE user_id = 1;
   ```

---

### Problème: Messages non reçus en temps réel

**Symptôme:** Les messages ne s'affichent qu'après rafraîchissement

**Solutions:**

1. **Vérifiez le statut WebSocket:**
   - Doit afficher ✅ Connecté (vert)
   - Pas ❌ Erreur ou ⚡ Déconnecté

2. **Vérifiez la console du navigateur:**
   - Doit afficher: `✅ Connected to Reverb`
   - Doit afficher: `✅ Subscribed to channel: private-conversation.X.default`

3. **Vérifiez les logs Laravel:**
   ```bash
   tail -f storage/logs/laravel.log
   ```
   Cherchez les erreurs d'autorisation

4. **Vérifiez que Reverb diffuse l'événement:**
   - Dans `MessageController.php`, vérifiez que:
   ```php
   broadcast(new MessageSent($message))->toOthers();
   ```

---

### Problème: CORS Errors

**Symptôme:** Erreurs CORS dans la console du navigateur

**Solutions:**

1. **Vérifiez `config/cors.php`:**
   ```php
   'allowed_origins' => [
       'http://localhost:3000',
       'http://localhost:8000',
       'http://127.0.0.1:8000',
   ],
   ```

2. **Ajoutez votre origine:**
   - Si vous utilisez une autre URL, ajoutez-la

3. **Rafraîchissez la configuration:**
   ```bash
   php artisan config:clear
   ```

---

## 📊 Console du Navigateur

Ouvrez la console du navigateur (**F12**) pour voir les logs:

### Logs Réussis:

```
✅ Connected to Reverb
✅ Subscribed to channel: private-conversation.1.default
📨 New message received: {message: {...}, sender: {...}, app_id: "default"}
```

### Logs d'Erreur:

```
❌ Reverb error: Error: Connection failed
❌ Subscription error: 403 Forbidden
```

### Logs Réseau:

Dans l'onglet **Network**, regardez:
- **broadcasting/auth**: Doit retourner 200 (pas 401 ou 403)
- **Reverb connection**: Doit montrer une connexion WebSocket établie

---

## 🎓 Conseils Avancés

### 1. Tester avec Postman en parallèle

1. Ouvrez l'interface de test dans le navigateur
2. Ouvrez Postman
3. Connectez-vous via Postman et obtenez le token
4. Envoyez des messages via Postman:
   ```
   POST http://localhost:8000/api/v1/conversations/1/messages
   Headers: Authorization: Bearer {token}, X-Application-ID: default
   Body: {"content": "Message depuis Postman", "type": "text"}
   ```
5. Le message apparaît instantanément dans l'interface de test !

### 2. Tester plusieurs utilisateurs simultanément

Utilisez les **fenêtres de navigation privée**:

- **Fenêtre 1 (normale):** Alice
- **Fenêtre 2 (privée):** Bob
- **Fenêtre 3 (privée):** Charlie

Cela simule 3 utilisateurs différents sur le même navigateur.

### 3. Vérifier les messages dans la base de données

```sql
-- Voir tous les messages
SELECT m.*, u.name as sender_name
FROM messages m
JOIN users u ON m.user_id = u.id
ORDER BY m.created_at DESC;

-- Voir les messages non lus d'un utilisateur
SELECT cp.*, c.name as conversation_name
FROM conversation_participants cp
JOIN conversations c ON cp.conversation_id = c.id
WHERE cp.user_id = 1 AND cp.unread_count > 0;
```

---

## 🚀 Prochaines Étapes

Une fois que vous avez testé l'interface:

1. ✅ Vérifiez que le temps réel fonctionne
2. ✅ Testez avec plusieurs utilisateurs
3. ✅ Testez sur différents appareils
4. ✅ Testez la création de conversations
5. ✅ Testez les messages non lus

**Vous êtes maintenant prêt à intégrer ce service dans votre application frontend !**

---

## 📚 Documentation Complète

Pour plus d'informations:

- **Guide complet:** `COMPREHENSIVE_GUIDE.md`
- **Exemples API:** `POSTMAN_EXAMPLES_COMPLETE.md`
- **Résumé du projet:** `PROJECT_SUMMARY.md`

---

**🎉 Bon testing !**

*Interface de test version 1.0 - Créée le 7 janvier 2026*

















