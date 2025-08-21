<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use App\Models\User;
use App\Models\ProfileActivityLog;
use App\Services\ProfileAnalyticsService;
use App\Services\ProfileScoringService;
use App\Services\BehavioralAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;

class ProfileAnalyticsReportCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users with some activity
        $users = User::factory(5)->create();
        
        foreach ($users as $user) {
            ProfileActivityLog::factory(10)->create([
                'user_id' => $user->id,
            ]);
        }
    }

    public function test_can_generate_summary_report_json()
    {
        $exitCode = Artisan::call('profile:analytics-report', [
            '--type' => 'summary',
            '--period' => 'weekly',
            '--format' => 'json',
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Profile Analytics Report Generation', Artisan::output());
        $this->assertStringContainsString('generated successfully', Artisan::output());
    }

    public function test_can_generate_and_save_report()
    {
        Storage::fake('local');

        $exitCode = Artisan::call('profile:analytics-report', [
            '--type' => 'summary',
            '--period' => 'daily',
            '--format' => 'json',
            '--save' => true,
        ]);

        $this->assertEquals(0, $exitCode);
        
        // Check that a file was created
        $files = Storage::disk('local')->files('reports/profile-analytics');
        $this->assertNotEmpty($files);
        
        // Verify file content
        $fileContent = Storage::disk('local')->get($files[0]);
        $data = json_decode($fileContent, true);
        
        $this->assertArrayHasKey('metadata', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('summary', $data);
        $this->assertEquals('summary', $data['metadata']['report_type']);
        $this->assertEquals('daily', $data['metadata']['period']);
    }

    public function test_can_generate_csv_report()
    {
        Storage::fake('local');

        $exitCode = Artisan::call('profile:analytics-report', [
            '--type' => 'summary',
            '--period' => 'weekly',
            '--format' => 'csv',
            '--save' => true,
        ]);

        $this->assertEquals(0, $exitCode);
        
        $files = Storage::disk('local')->files('reports/profile-analytics');
        $this->assertNotEmpty($files);
        
        $fileContent = Storage::disk('local')->get($files[0]);
        $this->assertStringContainsString('user_id', $fileContent);
        $this->assertStringContainsString('user_name', $fileContent);
    }

    public function test_can_generate_html_report()
    {
        Storage::fake('local');

        $exitCode = Artisan::call('profile:analytics-report', [
            '--type' => 'summary',
            '--period' => 'monthly',
            '--format' => 'html',
            '--save' => true,
        ]);

        $this->assertEquals(0, $exitCode);
        
        $files = Storage::disk('local')->files('reports/profile-analytics');
        $this->assertNotEmpty($files);
        
        $fileContent = Storage::disk('local')->get($files[0]);
        $this->assertStringContainsString('<!DOCTYPE html>', $fileContent);
        $this->assertStringContainsString('Profile Analytics Report', $fileContent);
    }

    public function test_can_generate_report_for_specific_user()
    {
        $user = User::first();

        $exitCode = Artisan::call('profile:analytics-report', [
            '--type' => 'comprehensive',
            '--period' => 'weekly',
            '--format' => 'json',
            '--user' => $user->id,
        ]);

        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('generated successfully', $output);
    }

    public function test_validates_invalid_report_type()
    {
        $exitCode = Artisan::call('profile:analytics-report', [
            '--type' => 'invalid_type',
            '--period' => 'weekly',
            '--format' => 'json',
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Invalid report type', Artisan::output());
    }

    public function test_validates_invalid_period()
    {
        $exitCode = Artisan::call('profile:analytics-report', [
            '--type' => 'summary',
            '--period' => 'invalid_period',
            '--format' => 'json',
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Invalid period', Artisan::output());
    }

    public function test_validates_invalid_format()
    {
        $exitCode = Artisan::call('profile:analytics-report', [
            '--type' => 'summary',
            '--period' => 'weekly',
            '--format' => 'invalid_format',
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Invalid format', Artisan::output());
    }

    public function test_handles_no_data_gracefully()
    {
        // Remove all users and activity logs
        ProfileActivityLog::truncate();
        User::truncate();

        $exitCode = Artisan::call('profile:analytics-report', [
            '--type' => 'summary',
            '--period' => 'weekly',
            '--format' => 'json',
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('No data available', Artisan::output());
    }

    public function test_cleanup_command_works()
    {
        Storage::fake('local');
        
        // Create some old files
        Storage::disk('local')->put('reports/profile-analytics/old_report.json', '{"test": "data"}');
        
        // Manually set the file timestamp to be old (this is a limitation of the fake storage)
        $exitCode = Artisan::call('profile:cleanup-reports', [
            '--dry-run' => true,
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Profile Analytics Reports Cleanup', Artisan::output());
    }

    public function test_can_use_custom_output_path()
    {
        Storage::fake('local');

        $customPath = 'custom/path/my_report.json';

        $exitCode = Artisan::call('profile:analytics-report', [
            '--type' => 'summary',
            '--period' => 'daily',
            '--format' => 'json',
            '--save' => true,
            '--output' => $customPath,
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertTrue(Storage::disk('local')->exists('reports/profile-analytics/' . $customPath));
    }
}