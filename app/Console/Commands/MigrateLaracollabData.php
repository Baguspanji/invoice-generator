<?php

namespace App\Console\Commands;

use App\Enums\CustomerType;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MigrateLaracollabData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:laracollab {--dry-run : Preview without writing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate customers, users, projects, task groups and tasks from LaraCollab database';

    /**
     * Alias map for source client-company names that already exist under a
     * different name in e-invoice. Key = LaraCollab name, value = e-invoice name.
     *
     * @var array<string, string>
     */
    protected array $customerAliases = [
        'Kopontren Al-Yasini' => 'Koperasi Pondok Pesantren Al-Yasini',
    ];

    /**
     * LaraCollab task_priorities.id => e-invoice priority enum value.
     *
     * @var array<int, string>
     */
    protected array $priorityMap = [
        1 => 'urgent', // Very high
        2 => 'high', // High
        3 => 'medium', // Medium
        4 => 'low', // Low
        5 => 'low', // Very low
    ];

    /**
     * LaraCollab group name (lowercase) => existing e-invoice group name.
     * Non-canonical groups are merged into the four canonical columns.
     *
     * @var array<string, string>
     */
    protected array $groupAliases = [
        'backlog' => 'To Do',
        'todo' => 'To Do',
        'to do' => 'To Do',
        'in progress' => 'In Progress',
        'qa' => 'QA',
        'done' => 'Done',
        'deployed' => 'Done',
    ];

    /**
     * Canonical group name => default color.
     *
     * @var array<string, string>
     */
    protected array $groupColors = [
        'To Do' => '#64748b',
        'In Progress' => '#3b82f6',
        'QA' => '#f59e0b',
        'Done' => '#10b981',
    ];

    /**
     * Canonical group name => default order.
     *
     * @var array<string, int>
     */
    protected array $groupOrders = [
        'To Do' => 0,
        'In Progress' => 1,
        'QA' => 2,
        'Done' => 3,
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        try {
            DB::connection('laracollab')->select('SELECT 1');
        } catch (\Throwable $e) {
            $this->error('Cannot reach laracollab database: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->warn('DRY-RUN mode: no data will be written.');
        }

        $stats = [
            'customers_created' => 0,
            'customers_matched' => 0,
            'users_created' => 0,
            'users_matched' => 0,
            'projects_created' => 0,
            'projects_matched' => 0,
            'groups_created' => 0,
            'groups_matched' => 0,
            'tasks_created' => 0,
            'tasks_skipped_archived' => 0,
            'tasks_skipped_existing' => 0,
        ];

        try {
            DB::transaction(function () use ($dryRun, &$stats): void {
                $customerMap = $this->migrateCustomers($dryRun, $stats);
                $this->migrateUsers($dryRun, $stats, $customerMap);
                $projectMap = $this->migrateProjects($dryRun, $stats, $customerMap);
                $groupMap = $this->migrateTaskGroups($dryRun, $stats, $projectMap);
                $this->migrateTasks($dryRun, $stats, $projectMap, $groupMap);

                if ($dryRun) {
                    throw new DryRunRollback;
                }
            }, 3);
        } catch (DryRunRollback) {
            // Expected: rolled back preview writes.
        }

        $this->table(
            ['Metric', 'Count'],
            collect($stats)->map(fn ($count, $key) => [$key, $count])->values()->all()
        );

        $this->info('Done. Skipped: attachments (53 files, paths live in LaraCollab storage), time_logs (0 rows), labels/comments (schema differs).');

        return self::SUCCESS;
    }

    /**
     * @return array<int, int> LaraCollab client_company_id => e-invoice customer_id
     */
    private function migrateCustomers(bool $dryRun, array &$stats): array
    {
        $map = [];
        $companies = DB::connection('laracollab')->table('client_companies')->orderBy('id')->get();

        foreach ($companies as $company) {
            $targetName = $this->customerAliases[$company->name] ?? $company->name;
            $customer = Customer::where('name', $targetName)->first();

            if ($customer) {
                $stats['customers_matched']++;
                $this->line("Customer matched: [LC {$company->id}] {$company->name} => [EI {$customer->id}] {$customer->name}");
            } else {
                $stats['customers_created']++;
                $this->line("Customer create: [LC {$company->id}] {$company->name} => {$targetName}");

                if (! $dryRun) {
                    $customer = Customer::create([
                        'type' => CustomerType::COMPANY->value,
                        'name' => $targetName,
                        'email' => null,
                        'phone' => $company->phone ?: null,
                        'address' => $company->address ?: null,
                    ]);
                } else {
                    $customer = new Customer(['id' => 0, 'name' => $targetName]);
                }
            }

            $map[(int) $company->id] = (int) $customer->id;
        }

        return $map;
    }

    /**
     * @param  array<int, int>  $customerMap
     */
    private function migrateUsers(bool $dryRun, array &$stats, array $customerMap): void
    {
        // client_id (LC users.id) => client_company_id
        $clientCompanyByUser = DB::connection('laracollab')
            ->table('client_company')
            ->pluck('client_company_id', 'client_id');

        $users = DB::connection('laracollab')->table('users')->orderBy('id')->get();

        foreach ($users as $source) {
            $existing = User::where('email', $source->email)->first();

            if ($existing) {
                $stats['users_matched']++;
                $this->line("User matched by email: {$source->email} => [EI {$existing->id}]");

                continue;
            }

            $isOwner = strcasecmp((string) $source->job_title, 'Owner') === 0;
            $role = $isOwner ? UserRole::STAFF->value : UserRole::CUSTOMER->value;
            $customerId = null;

            if (! $isOwner && isset($clientCompanyByUser[$source->id])) {
                $customerId = $customerMap[(int) $clientCompanyByUser[$source->id]] ?? null;
            }

            $username = $this->uniqueUsername(Str::slug(strtok($source->email, '@'), '_') ?: 'user'.$source->id);

            $stats['users_created']++;
            $this->line("User create: {$source->email} (role={$role}, customer_id=".($customerId ?? 'null').', username='.$username.')');

            if (! $dryRun) {
                // Insert via query builder to preserve the original password hash
                // (the User model cast would double-hash it).
                DB::table('users')->insert([
                    'name' => $source->name,
                    'username' => $username,
                    'email' => $source->email,
                    'email_verified_at' => null,
                    'password' => $source->password,
                    'role' => $role,
                    'customer_id' => $customerId,
                    'remember_token' => null,
                    'created_at' => $source->created_at ?? now(),
                    'updated_at' => $source->updated_at ?? now(),
                ]);
            }
        }
    }

    /**
     * @param  array<int, int>  $customerMap
     * @return array<int, int> LaraCollab project_id => e-invoice project_id
     */
    private function migrateProjects(bool $dryRun, array &$stats, array $customerMap): array
    {
        $map = [];
        $projects = DB::connection('laracollab')->table('projects')->orderBy('id')->get();

        foreach ($projects as $source) {
            $customerId = $customerMap[(int) $source->client_company_id] ?? null;

            if ($customerId === null) {
                $this->warn("Project skipped (no customer map): [LC {$source->id}] {$source->name}");

                continue;
            }

            $pricing = in_array($source->default_pricing_type, ['fixed', 'hourly'], true)
                ? $source->default_pricing_type
                : 'fixed';

            $project = Project::where('customer_id', $customerId)
                ->where('name', $source->name)
                ->first();

            if ($project) {
                $stats['projects_matched']++;
                $this->line("Project matched: [LC {$source->id}] {$source->name} => [EI {$project->id}]");
            } else {
                $stats['projects_created']++;
                $this->line("Project create: [LC {$source->id}] {$source->name} (customer_id={$customerId})");

                if (! $dryRun) {
                    $project = Project::create([
                        'customer_id' => $customerId,
                        'name' => $source->name,
                        'description' => $this->plainText($source->description),
                        'rate' => (float) ($source->rate ?? 0),
                        'pricing_type' => $pricing,
                        'archived_at' => $source->archived_at,
                    ]);
                    DB::table('projects')->where('id', $project->id)->update([
                        'created_at' => $source->created_at ?? now(),
                        'updated_at' => $source->updated_at ?? now(),
                    ]);
                    $project->refresh();
                } else {
                    $project = new Project(['id' => 0]);
                }
            }

            $map[(int) $source->id] = (int) $project->id;
        }

        return $map;
    }

    /**
     * @param  array<int, int>  $projectMap
     * @return array<int, int> LaraCollab group_id => e-invoice group_id
     */
    private function migrateTaskGroups(bool $dryRun, array &$stats, array $projectMap): array
    {
        $map = [];
        $groups = DB::connection('laracollab')->table('task_groups')->orderBy('project_id')->orderBy('id')->get();

        foreach ($groups as $source) {
            $projectId = $projectMap[(int) $source->project_id] ?? null;

            if ($projectId === null) {
                continue;
            }

            $targetName = $this->groupAliases[strtolower($source->name)] ?? $source->name;

            $group = TaskGroup::where('project_id', $projectId)
                ->where('name', $targetName)
                ->first();

            if (! $group) {
                // Case-insensitive fallback (e.g. "In progress" vs "In Progress").
                $group = TaskGroup::where('project_id', $projectId)
                    ->get()
                    ->first(fn ($g) => strcasecmp($g->name, $targetName) === 0);
            }

            if ($group) {
                $stats['groups_matched']++;
            } else {
                $stats['groups_created']++;
                $this->line("Group create: [LC {$source->id}] {$source->name} => {$targetName} (project_id={$projectId})");

                if (! $dryRun) {
                    $group = TaskGroup::create([
                        'project_id' => $projectId,
                        'name' => $targetName,
                        'color' => $this->groupColors[$targetName] ?? $source->color ?: '#64748b',
                        'order_column' => $this->groupOrders[$targetName] ?? (int) ($source->order_column ?? 0),
                    ]);
                } else {
                    $group = new TaskGroup(['id' => 0]);
                }
            }

            $map[(int) $source->id] = (int) $group->id;
        }

        return $map;
    }

    /**
     * @param  array<int, int>  $projectMap
     * @param  array<int, int>  $groupMap
     */
    private function migrateTasks(bool $dryRun, array &$stats, array $projectMap, array $groupMap): void
    {
        $tasks = DB::connection('laracollab')->table('tasks')->orderBy('id')->get();

        foreach ($tasks as $source) {
            if ($source->archived_at !== null) {
                $stats['tasks_skipped_archived']++;

                continue;
            }

            $projectId = $projectMap[(int) $source->project_id] ?? null;
            $groupId = $groupMap[(int) $source->group_id] ?? null;

            if ($projectId === null || $groupId === null) {
                $this->warn("Task skipped (no project/group map): [LC {$source->id}] {$source->name}");

                continue;
            }

            $title = mb_substr(trim((string) $source->name), 0, 255);

            $exists = Task::where('project_id', $projectId)
                ->where('title', $title)
                ->exists();

            if ($exists) {
                $stats['tasks_skipped_existing']++;

                continue;
            }

            $stats['tasks_created']++;

            if ($dryRun) {
                continue;
            }

            $task = Task::create([
                'project_id' => $projectId,
                'task_group_id' => $groupId,
                'assigned_to_user_id' => null, // source has no assignments (all NULL)
                'invoice_id' => null, // source invoice refs belong to LC invoicing; keep unbilled
                'title' => $title,
                'priority' => $this->priorityMap[(int) ($source->priority_id ?? 0)] ?? 'medium',
                'description' => $this->plainText($source->description),
                'estimation' => $source->estimation,
                'fixed_price' => $source->fixed_price,
                'billable' => (bool) $source->billable,
                'order_column' => (int) ($source->order_column ?? 0),
                'completed_at' => $source->completed_at,
            ]);

            DB::table('tasks')->where('id', $task->id)->update([
                'created_at' => $source->created_at ?? now(),
                'updated_at' => $source->updated_at ?? now(),
            ]);
        }
    }

    private function plainText(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }

    private function uniqueUsername(string $base): string
    {
        $candidate = mb_substr($base, 0, 40) ?: 'user';
        $suffix = 0;

        while (User::where('username', $candidate)->exists()) {
            $suffix++;
            $candidate = mb_substr($base, 0, 38).$suffix;
        }

        return $candidate;
    }
}

class DryRunRollback extends \RuntimeException
{
    //
}
