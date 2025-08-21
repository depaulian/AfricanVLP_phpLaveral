<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Carbon\Carbon;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating Super Admin users...');

        // Default Super Admin credentials
        $superAdmins = [
            [
                'first_name' => 'Super',
                'last_name' => 'Administrator',
                'email' => 'admin@auvlp.com',
                'password' => 'AdminAUVLP2024!',
                'status' => 'active',
                'is_admin' => true,
                'email_verified_at' => now(),
                'created' => now(),
                'modified' => now(),
            ],
            [
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'email' => 'sysadmin@auvlp.com',
                'password' => 'SysAdminAUVLP2024!',
                'status' => 'active',
                'is_admin' => true,
                'email_verified_at' => now(),
                'created' => now(),
                'modified' => now(),
            ],
        ];

        foreach ($superAdmins as $adminData) {
            // Check if user already exists
            $existingUser = User::where('email', $adminData['email'])->first();
            
            if ($existingUser) {
                $this->command->warn("User with email {$adminData['email']} already exists. Updating admin status...");
                
                // Update existing user to admin
                $existingUser->update([
                    'is_admin' => true,
                    'status' => 'active',
                    'modified' => now(),
                ]);
                
                $this->command->info("✓ Updated {$adminData['email']} to Admin");
            } else {
                // Hash the password
                $adminData['password'] = Hash::make($adminData['password']);
                
                // Create new admin
                $user = User::create($adminData);
                
                $this->command->info("✓ Created Admin: {$adminData['email']}");
            }
        }

        // Create additional admin users for testing
        $this->createTestAdmins();

        $this->command->info('Admin seeding completed!');
        $this->displayCredentials();
    }

    /**
     * Create test admin users
     */
    private function createTestAdmins(): void
    {
        $testAdmins = [
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'email' => 'admin.user@auvlp.com',
                'password' => 'AdminUser2024!',
                'status' => 'active',
                'is_admin' => true,
            ],
            [
                'first_name' => 'Content',
                'last_name' => 'Moderator',
                'email' => 'moderator@auvlp.com',
                'password' => 'Moderator2024!',
                'status' => 'active',
                'is_admin' => true,
            ],
            [
                'first_name' => 'Content',
                'last_name' => 'Editor',
                'email' => 'editor@auvlp.com',
                'password' => 'Editor2024!',
                'status' => 'active',
                'is_admin' => true,
            ],
        ];

        foreach ($testAdmins as $adminData) {
            $existingUser = User::where('email', $adminData['email'])->first();
            
            if (!$existingUser) {
                $adminData['password'] = Hash::make($adminData['password']);
                $adminData['email_verified_at'] = now();
                $adminData['created'] = now();
                $adminData['modified'] = now();
                
                User::create($adminData);
                $this->command->info("✓ Created Admin: {$adminData['email']}");
            }
        }
    }

    /**
     * Display admin credentials
     */
    private function displayCredentials(): void
    {
        $this->command->info('');
        $this->command->info('=== ADMIN CREDENTIALS ===');
        $this->command->info('');
        $this->command->info('🔐 SUPER ADMIN ACCOUNTS:');
        $this->command->info('Email: admin@auvlp.com');
        $this->command->info('Password: AdminAUVLP2024!');
        $this->command->info('');
        $this->command->info('Email: sysadmin@auvlp.com');
        $this->command->info('Password: SysAdminAUVLP2024!');
        $this->command->info('');
        $this->command->info('👤 OTHER ADMIN ACCOUNTS:');
        $this->command->info('Admin: admin.user@auvlp.com / AdminUser2024!');
        $this->command->info('Moderator: moderator@auvlp.com / Moderator2024!');
        $this->command->info('Editor: editor@auvlp.com / Editor2024!');
        $this->command->info('');
        $this->command->info('⚠️  IMPORTANT: Change these passwords in production!');
        $this->command->info('========================');
    }
}