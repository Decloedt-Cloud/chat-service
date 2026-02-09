<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ImportUsersFromSchool extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:school-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate users from formation_db_2.users to wayo_chat.users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting user migration from formation_db_2...');

        try {
            // Get all users from formation_db_2.users directly using default connection
            // assuming the DB user has permissions to access both databases
            $schoolUsers = DB::table('formation_db_2.users')->get();

            $bar = $this->output->createProgressBar(count($schoolUsers));
            $bar->start();

            foreach ($schoolUsers as $sUser) {
                // Map fields based on CodeIgniter typical structure or generic fallback
                $firstName = $sUser->first_name ?? '';
                $lastName = $sUser->last_name ?? '';
                
                if (!empty($firstName) || !empty($lastName)) {
                    $name = trim($firstName . ' ' . $lastName);
                } else {
                    $name = $sUser->name ?? 'Unknown User';
                }

                User::updateOrCreate(
                    ['email' => $sUser->email], // Match by email to avoid duplicates
                    [
                        'user_id' => $sUser->id, // Map school_db id to user_id (formerly wap_user_id)
                        'name' => $name,
                        'password' => $sUser->password ?? Hash::make('12345678'), // Use existing hash or default
                        // If you have role info, you can map it here too, but for now we keep it simple
                    ]
                );

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info('User migration completed successfully.');

        } catch (\Exception $e) {
            $this->error('Error during migration: ' . $e->getMessage());
            $this->info('Tip: Ensure "formation_db_2" database exists and the user table has id, email, first_name/last_name columns.');
            return 1;
        }

        return 0;
    }
}
