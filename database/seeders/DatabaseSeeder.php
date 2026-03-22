<?php

namespace Database\Seeders;

use App\Actions\Organization\CreateOrganization;
use App\Actions\Projects\CreateEnvironment;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\GenerateToken;
use App\Models\Environment;
use App\Models\Organization;
use App\Models\OrganizationRole;
use App\Models\TelemetryRecord;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with realistic demo data.
     */
    public function run(): void
    {
        // ── Demo admin user ──────────────────────────────────────────────────
        $admin = User::factory()->create([
            'name' => 'Alice Admin',
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
        ]);

        $developer = User::factory()->create([
            'name' => 'Bob Developer',
            'email' => 'dev@example.com',
            'email_verified_at' => now(),
        ]);

        $viewer = User::factory()->create([
            'name' => 'Carol Viewer',
            'email' => 'viewer@example.com',
            'email_verified_at' => now(),
        ]);

        // ── Organization ─────────────────────────────────────────────────────
        $org = app(CreateOrganization::class)->handle($admin, [
            'name' => 'Acme Corp',
            'slug' => 'acme',
            'timezone' => 'UTC',
        ]);

        // Add developer and viewer members
        $devRole = OrganizationRole::where('organization_id', $org->id)->where('slug', 'developer')->first();
        $viewerRole = OrganizationRole::where('organization_id', $org->id)->where('slug', 'viewer')->first();

        $org->members()->create(['user_id' => $developer->id, 'organization_role_id' => $devRole->id]);
        $org->members()->create(['user_id' => $viewer->id, 'organization_role_id' => $viewerRole->id]);

        // ── Projects ─────────────────────────────────────────────────────────
        $apiProject = app(CreateProject::class)->handle($org, [
            'name' => 'API Service',
            'slug' => 'api-service',
            'description' => 'Core REST API backend.',
        ]);

        $webProject = app(CreateProject::class)->handle($org, [
            'name' => 'Web Frontend',
            'slug' => 'web-frontend',
            'description' => 'Marketing and dashboard frontend.',
        ]);

        // ── Environments + tokens ─────────────────────────────────────────────
        foreach ([$apiProject, $webProject] as $project) {
            foreach (['production' => 'prod', 'staging' => 'staging', 'development' => 'dev'] as $type => $slug) {
                $env = app(CreateEnvironment::class)->handle($project, [
                    'name' => ucfirst($type),
                    'slug' => $slug,
                    'type' => $type,
                ]);

                app(GenerateToken::class)->handle($env);

                // Seed some telemetry data for this environment
                $this->seedTelemetry($org->id, $project->id, $env->id, $type === 'production' ? 200 : 30);
            }
        }

        $this->command->info('Demo data seeded.');
        $this->command->line('  admin@example.com  /  password');
        $this->command->line('  dev@example.com    /  password');
        $this->command->line('  viewer@example.com /  password');
    }

    /**
     * Seed realistic telemetry records and extraction rows for demo purposes.
     */
    private function seedTelemetry(int $orgId, int $projectId, int $envId, int $count): void
    {
        $routes = [
            ['GET', '/api/users', 'users.index', 200],
            ['POST', '/api/users', 'users.store', 201],
            ['GET', '/api/orders', 'orders.index', 200],
            ['GET', '/api/orders/{id}', 'orders.show', 200],
            ['GET', '/api/orders/{id}', 'orders.show', 404],
            ['DELETE', '/api/orders/{id}', 'orders.destroy', 500],
        ];

        for ($i = 0; $i < $count; $i++) {
            $route = $routes[array_rand($routes)];
            $recordedAt = now()->subMinutes(random_int(1, 10080)); // up to 7 days ago

            $telemetry = TelemetryRecord::create([
                'organization_id' => $orgId,
                'project_id' => $projectId,
                'environment_id' => $envId,
                'record_type' => 'request',
                'trace_id' => fake()->uuid(),
                'group_key' => null,
                'execution_id' => null,
                'payload' => [],
                'recorded_at' => $recordedAt,
            ]);

            DB::table('extraction_requests')->insert([
                'telemetry_record_id' => $telemetry->id,
                'organization_id' => $orgId,
                'project_id' => $projectId,
                'environment_id' => $envId,
                'method' => $route[0],
                'url' => 'https://api.example.com'.$route[1],
                'route_name' => $route[2],
                'route_path' => $route[1],
                'status_code' => $route[3],
                'duration' => random_int(20, 800),
                'exceptions' => $route[3] >= 500 ? 1 : 0,
                'queries' => random_int(0, 10),
                'user' => random_int(0, 3) === 0 ? null : 'user-'.random_int(1, 50),
                'recorded_at' => $recordedAt,
            ]);
        }

        // Seed a few exceptions
        for ($i = 0; $i < (int) ($count / 10); $i++) {
            $recordedAt = now()->subMinutes(random_int(1, 10080));
            $telemetry = TelemetryRecord::create([
                'organization_id' => $orgId,
                'project_id' => $projectId,
                'environment_id' => $envId,
                'record_type' => 'exception',
                'trace_id' => fake()->uuid(),
                'group_key' => 'exc-group-'.random_int(1, 5),
                'execution_id' => null,
                'payload' => [],
                'recorded_at' => $recordedAt,
            ]);

            DB::table('extraction_exceptions')->insert([
                'telemetry_record_id' => $telemetry->id,
                'organization_id' => $orgId,
                'project_id' => $projectId,
                'environment_id' => $envId,
                'class' => fake()->randomElement(['RuntimeException', 'InvalidArgumentException', 'PDOException', 'TypeError']),
                'message' => fake()->sentence(),
                'file' => 'app/Services/SomeService.php',
                'line' => random_int(10, 300),
                'group_key' => 'exc-group-'.random_int(1, 5),
                'handled' => (bool) random_int(0, 1),
                'user' => null,
                'recorded_at' => $recordedAt,
            ]);
        }
    }
}
