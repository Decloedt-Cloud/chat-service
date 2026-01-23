<?php
// sync_from_wapback_db.php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "=== SYNCING USERS FROM WAPBACK DB ===\n\n";

// Wapback DB Credentials
$host = '127.0.0.1';
$db   = 'wap_new';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "Connected to Wapback DB.\n";
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Fetch all Wapback users
$stmt = $pdo->query("SELECT id, name, email, photo_profil FROM users");
$wapUsers = $stmt->fetchAll();

$count = 0;
$updated = 0;

foreach ($wapUsers as $wapUser) {
    $count++;
    $wapId = $wapUser['id'];
    
    // Fetch details based on role tables
    $avatar = $wapUser['photo_profil']; // Default from users table
    $gender = null;
    $role = null;
    
    try {
        // Check Intervenants
        $stmt = $pdo->prepare("SELECT photo_profil, sexe FROM intervenants WHERE user_id = ?");
        $stmt->execute([$wapId]);
        $intervenant = $stmt->fetch();
        
        if ($intervenant) {
            $role = 'intervenant';
            if (!empty($intervenant['photo_profil'])) {
                $avatar = $intervenant['photo_profil'];
            }
            $gender = $intervenant['sexe'];
        } else {
            // Check Clients
            try {
                $stmt = $pdo->prepare("SELECT sexe FROM clients WHERE user_id = ?");
                $stmt->execute([$wapId]);
                $client = $stmt->fetch();
                
                if ($client) {
                    $role = 'client';
                    $gender = $client['sexe'];
                    
                    // Try to get photo from clients table if it exists
                     try {
                        $stmt = $pdo->prepare("SELECT photo_profil FROM clients WHERE user_id = ?");
                        $stmt->execute([$wapId]);
                        $clientPhoto = $stmt->fetch();
                        if ($clientPhoto && !empty($clientPhoto['photo_profil'])) {
                            $avatar = $clientPhoto['photo_profil'];
                        }
                    } catch (\Exception $e) {
                        // Column doesn't exist, ignore
                    }
                }
            } catch (\Exception $e) {
                // Table doesn't exist? Unlikely
            }
        }

    } catch (\Exception $e) {
        echo "Error fetching details for user $wapId: " . $e->getMessage() . "\n";
    }

    // Process Avatar URL
    if ($avatar) {
        if (!str_starts_with($avatar, 'http')) {
            $storageUrl = 'http://localhost/WAP/Wapback/public/storage/';
            $avatar = $storageUrl . ltrim($avatar, '/');
        }

        // --- FIX BROKEN URLS ---
        // Some URLs in DB are missing 'intervenant_attachments/' or 'client_attachments/'
        // Fix Intervenant URLs
        if (strpos($avatar, '/storage/intervenant') !== false && strpos($avatar, '/storage/intervenant_attachments') === false) {
            $avatar = str_replace('/storage/intervenant', '/storage/intervenant_attachments/intervenant', $avatar);
        }
        // Fix Client URLs
        if (strpos($avatar, '/storage/client') !== false && strpos($avatar, '/storage/client_attachments') === false) {
            $avatar = str_replace('/storage/client', '/storage/client_attachments/client', $avatar);
        }
        // -----------------------
    }

    // Find local user
    // Try by wap_user_id first, then email
    $localUser = User::where('wap_user_id', $wapId)->first();
    if (!$localUser) {
        $localUser = User::where('email', $wapUser['email'])->first();
    }

    if ($localUser) {
        $needsSave = false;
        
        // Update wap_user_id if missing
        if (!$localUser->wap_user_id) {
            $localUser->wap_user_id = $wapId;
            $needsSave = true;
        }

        // Update Avatar
        if ($avatar && $localUser->avatar !== $avatar) {
            $localUser->avatar = $avatar;
            $needsSave = true;
            echo "Updated avatar for {$localUser->name}\n";
        }

        // Update Gender
        if ($gender && $localUser->gender !== $gender) {
            $localUser->gender = $gender;
            $needsSave = true;
            echo "Updated gender for {$localUser->name} (New: $gender)\n";
        }

        if ($needsSave) {
            $localUser->save();
            $updated++;
        }
    }
}

echo "\nProcessed $count users. Updated $updated users.\n";
