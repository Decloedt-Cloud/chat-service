<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "=== Création des utilisateurs de test ===\n";

// Créer Alice
$alice = User::firstOrCreate(
    ['email' => 'alice@test.com'],
    [
        'name' => 'Alice',
        'password' => bcrypt('password123')
    ]
);
echo "✅ Alice créé (ID: {$alice->id})\n";

// Créer Bob
$bob = User::firstOrCreate(
    ['email' => 'bob@test.com'],
    [
        'name' => 'Bob',
        'password' => bcrypt('password123')
    ]
);
echo "✅ Bob créé (ID: {$bob->id})\n";

echo "\nUtilisateurs prêts pour les tests !\n";
echo "Email: alice@test.com, Password: password123\n";
echo "Email: bob@test.com, Password: password123\n";

















