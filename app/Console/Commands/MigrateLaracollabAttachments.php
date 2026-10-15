<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MigrateLaracollabAttachments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:laracollab-attachments {--dry-run : Preview without writing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copy LaraCollab attachment files from the tasks/ backup into task-attachments/ and link them to migrated tasks';

    /**
     * @var array<string, string>
     */
    protected array $customerAliases = [
        'Kopontren Al-Yasini' => 'Koperasi Pondok Pesantren Al-Yasini',
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

        $disk = Storage::disk('public');

        if ($dryRun) {
            $this->warn('DRY-RUN mode: no data will be written.');
        }

        $stats = [
            'copied' => 0,
            'skipped_existing' => 0,
            'skipped_no_task' => 0,
            'skipped_missing_file' => 0,
        ];

        $rows = DB::connection('laracollab')
            ->table('attachments')
            ->orderBy('id')
            ->get();

        foreach ($rows as $source) {
            $task = $this->resolveTask($source);

            if (! $task) {
                $stats['skipped_no_task']++;
                $this->warn("Skipped (task not migrated): [LC att {$source->id}] {$source->name} (LC task {$source->task_id})");

                continue;
            }

            $exists = TaskAttachment::where('task_id', $task->id)
                ->where('file_name', $source->name)
                ->exists();

            if ($exists) {
                $stats['skipped_existing']++;

                continue;
            }

            $relative = ltrim(preg_replace('#^/storage/#', '', (string) $source->path), '/');

            if (! $disk->exists($relative)) {
                $stats['skipped_missing_file']++;
                $this->warn("Skipped (file missing in backup): [LC att {$source->id}] {$relative}");

                continue;
            }

            $destination = $this->uniqueDestination($disk, basename($relative));
            $stats['copied']++;
            $this->line("Copy: [LC att {$source->id}] {$source->name} => {$destination} (task: {$task->title})");

            if ($dryRun) {
                continue;
            }

            $disk->copy($relative, $destination);

            TaskAttachment::create([
                'task_id' => $task->id,
                'file_name' => $source->name,
                'file_path' => $destination,
                'file_size' => $disk->size($destination) ?: (int) $source->size,
                'mime_type' => $source->type ?: $disk->mimeType($destination),
            ]);
        }

        $this->table(
            ['Metric', 'Count'],
            collect($stats)->map(fn ($count, $key) => [$key, $count])->values()->all()
        );

        return self::SUCCESS;
    }

    private function resolveTask(object $source): ?Task
    {
        $lcTask = DB::connection('laracollab')->table('tasks')->find($source->task_id);

        if (! $lcTask) {
            return null;
        }

        $lcProject = DB::connection('laracollab')->table('projects')->find($lcTask->project_id);

        if (! $lcProject) {
            return null;
        }

        $lcCompany = DB::connection('laracollab')->table('client_companies')->find($lcProject->client_company_id);

        if (! $lcCompany) {
            return null;
        }

        $customerName = $this->customerAliases[$lcCompany->name] ?? $lcCompany->name;
        $customer = Customer::where('name', $customerName)->first();

        if (! $customer) {
            return null;
        }

        $project = Project::where('customer_id', $customer->id)
            ->where('name', $lcProject->name)
            ->first();

        if (! $project) {
            return null;
        }

        return Task::where('project_id', $project->id)
            ->where('title', mb_substr(trim((string) $lcTask->name), 0, 255))
            ->first();
    }

    private function uniqueDestination(object $disk, string $basename): string
    {
        $candidate = 'task-attachments/'.$basename;

        if (! $disk->exists($candidate) && ! TaskAttachment::where('file_path', $candidate)->exists()) {
            return $candidate;
        }

        $name = pathinfo($basename, PATHINFO_FILENAME);
        $ext = pathinfo($basename, PATHINFO_EXTENSION);
        $suffix = 0;

        do {
            $suffix++;
            $candidate = 'task-attachments/'.$name.'-'.$suffix.($ext !== '' ? '.'.$ext : '');
        } while ($disk->exists($candidate) || TaskAttachment::where('file_path', $candidate)->exists());

        return $candidate;
    }
}
