<?php

namespace Database\Seeders;

use App\Enums\DocumentationActivityAction;
use App\Enums\DocumentationPageStatus;
use App\Models\Customer;
use App\Models\DocumentationActivity;
use App\Models\DocumentationPage;
use App\Models\DocumentationSpace;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Membuat satu project contoh lengkap dengan task group, task, dan workspace
 * dokumentasi yang "penuh": kategori, halaman bersarang (dengan tabel, gambar,
 * code block, dan diagram Mermaid), version history, tautan task, lampiran,
 * komentar, serta riwayat aktivitas.
 *
 * Idempotent: dilewati bila project contoh sudah ada. Jalankan dengan:
 *   php artisan db:seed --class=DocumentationDemoSeeder
 */
class DocumentationDemoSeeder extends Seeder
{
    private const PROJECT_NAME = 'Aplikasi E-Invoice IceSum';

    /**
     * PNG 1x1 transparan sebagai berkas lampiran contoh.
     */
    private const PLACEHOLDER_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    /**
     * @var array<string, User>
     */
    private array $users = [];

    /**
     * @var array<string, Task>
     */
    private array $tasks = [];

    /**
     * @var array<string, int>
     */
    private array $categories = [];

    public function run(): void
    {
        if (Project::where('name', self::PROJECT_NAME)->exists()) {
            $this->command?->warn('Project contoh sudah ada — DocumentationDemoSeeder dilewati.');

            return;
        }

        $this->users = [
            'admin' => $this->firstOrCreateUser('Admin', 'admin', 'admin@admin.com'),
            'dev' => $this->firstOrCreateUser('Bagus Panji', 'bagus.panji', 'dev@example.com'),
            'qa' => $this->firstOrCreateUser('Rani Puspita', 'rani.puspita', 'qa@example.com'),
        ];

        $customer = Customer::factory()->company()->create(['name' => 'PT Nusantara Digital']);

        $project = Project::factory()->create([
            'customer_id' => $customer->id,
            'name' => self::PROJECT_NAME,
            'description' => 'Aplikasi manajemen project, task, dan e-invoice untuk klien korporat.',
            'rate' => 150000,
            'pricing_type' => 'hourly',
        ]);

        $project->users()->sync(collect($this->users)->pluck('id')->all());

        $this->seedTaskGroups($project);
        $space = $this->seedSpace($project);
        $this->seedCategories($space);
        $this->seedPages($space);

        $this->command?->info('Project contoh "'.self::PROJECT_NAME.'" beserta dokumentasi lengkap berhasil dibuat.');
    }

    private function firstOrCreateUser(string $name, string $username, string $email): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'username' => $username,
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );
    }

    private function seedTaskGroups(Project $project): void
    {
        $groups = [
            ['name' => 'To Do', 'color' => '#64748b'],
            ['name' => 'In Progress', 'color' => '#2563eb'],
            ['name' => 'QA', 'color' => '#f59e0b'],
            ['name' => 'Done', 'color' => '#22c55e'],
        ];

        $tasks = [
            ['group' => 'Done', 'title' => 'Setup repository & CI', 'priority' => 'medium', 'user' => 'dev', 'estimation' => 6, 'completed' => true, 'billable' => true],
            ['group' => 'Done', 'title' => 'Desain skema database', 'priority' => 'high', 'user' => 'dev', 'estimation' => 8, 'completed' => true, 'billable' => true],
            ['group' => 'Done', 'title' => 'Export PDF invoice', 'priority' => 'low', 'user' => 'dev', 'estimation' => 4, 'completed' => true, 'billable' => false],
            ['group' => 'In Progress', 'title' => 'CRUD Invoice', 'priority' => 'high', 'user' => 'dev', 'estimation' => 16, 'completed' => false, 'billable' => true],
            ['group' => 'In Progress', 'title' => 'Notifikasi email', 'priority' => 'medium', 'user' => 'qa', 'estimation' => 6, 'completed' => false, 'billable' => false],
            ['group' => 'QA', 'title' => 'Dashboard KPI', 'priority' => 'medium', 'user' => 'qa', 'estimation' => 8, 'completed' => false, 'billable' => false],
            ['group' => 'To Do', 'title' => 'Integrasi payment gateway', 'priority' => 'urgent', 'user' => 'dev', 'estimation' => 12, 'completed' => false, 'billable' => true],
            ['group' => 'To Do', 'title' => 'Portal customer', 'priority' => 'medium', 'user' => 'qa', 'estimation' => 10, 'completed' => false, 'billable' => false],
            ['group' => 'To Do', 'title' => 'Audit keamanan', 'priority' => 'high', 'user' => 'qa', 'estimation' => 5, 'completed' => false, 'billable' => false],
        ];

        $createdGroups = collect($groups)->mapWithKeys(fn (array $group, int $index): array => [
            $group['name'] => $project->taskGroups()->create([
                'name' => $group['name'],
                'color' => $group['color'],
                'order_column' => $index,
            ]),
        ]);

        foreach ($tasks as $index => $definition) {
            $task = $project->tasks()->create([
                'task_group_id' => $createdGroups[$definition['group']]->id,
                'assigned_to_user_id' => $this->users[$definition['user']]->id,
                'title' => $definition['title'],
                'priority' => $definition['priority'],
                'description' => 'Task contoh untuk kebutuhan demonstrasi dokumentasi.',
                'estimation' => $definition['estimation'],
                'billable' => $definition['billable'],
                'order_column' => $index,
                'completed_at' => $definition['completed'] ? now()->subDays(3) : null,
            ]);

            $this->tasks[$definition['title']] = $task;
        }
    }

    private function seedSpace(Project $project): DocumentationSpace
    {
        $space = $project->documentationSpace()->create([
            'name' => 'Dokumentasi '.$project->name,
            'description' => 'Pusat dokumentasi teknis, arsitektur, API, dan proses bisnis aplikasi.',
            'created_by' => $this->users['admin']->id,
        ]);

        $space->forceFill([
            'created_at' => now()->subDays(30),
            'updated_at' => now()->subDay(),
        ])->save();

        return $space;
    }

    private function seedCategories(DocumentationSpace $space): void
    {
        $categories = ['Architecture', 'API', 'Database', 'Business Process', 'Deployment'];

        foreach ($categories as $index => $name) {
            $category = $space->categories()->create([
                'name' => $name,
                'slug' => Str::slug($name),
                'order_column' => $index,
            ]);

            $this->categories[$name] = $category->id;
        }
    }

    private function seedPages(DocumentationSpace $space): void
    {
        foreach ($this->pageDefinitions() as $index => $definition) {
            $author = $this->users[$definition['author']];
            $createdAt = now()->subDays($definition['age_days']);
            $updatedAt = now()->subDays($definition['updated_days_ago']);
            $status = $definition['status'];
            $publishedAt = $status === DocumentationPageStatus::PUBLISHED
                ? now()->subDays($definition['published_days_ago'] ?? $definition['updated_days_ago'])
                : null;

            $parentId = null;

            if (! empty($definition['parent'])) {
                $parentId = DocumentationPage::query()
                    ->where('space_id', $space->id)
                    ->where('title', $definition['parent'])
                    ->value('id');
            }

            $versions = $definition['versions'];
            $lockVersion = count($versions) + 1;

            $page = $space->pages()->create([
                'category_id' => $this->categories[$definition['category']] ?? null,
                'parent_id' => $parentId,
                'title' => $definition['title'],
                'slug' => Str::slug($definition['title']),
                'content' => $definition['content'],
                'status' => $status,
                'created_by' => $author->id,
                'updated_by' => $author->id,
                'published_at' => $publishedAt,
                'lock_version' => $lockVersion,
                'order_column' => $index + 1,
            ]);

            $page->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ])->save();

            $this->seedVersions($page, $versions, $author, $createdAt);
            $this->seedTaskLinks($page, $definition['tasks'] ?? [], $author, $createdAt);
            $this->seedAttachments($page, $definition['attachments'] ?? [], $author, $updatedAt);
            $this->seedComments($page, $definition['comments'] ?? [], $publishedAt ?? $updatedAt);
            $this->seedActivities($page, $definition, $author, $createdAt, $updatedAt, $publishedAt);
        }
    }

    /**
     * @param  array<int, array{summary: string, days_ago: int}>  $versions
     */
    private function seedVersions(DocumentationPage $page, array $versions, User $author, Carbon $createdAt): void
    {
        $lastIndex = count($versions) - 1;

        foreach ($versions as $index => $version) {
            $snapshot = $page->versions()->create([
                'version_number' => $index + 1,
                'title' => $page->title,
                'content' => $index === $lastIndex
                    ? $page->content
                    : $this->doc([$this->paragraph('Draft versi '.($index + 1).' — '.$version['summary'])]),
                'status' => $page->status,
                'change_summary' => $version['summary'],
                'created_by' => $author->id,
            ]);

            $snapshot->forceFill(['created_at' => now()->subDays($version['days_ago'])])->save();
        }
    }

    /**
     * @param  array<int, string>  $titles
     */
    private function seedTaskLinks(DocumentationPage $page, array $titles, User $author, Carbon $createdAt): void
    {
        foreach ($titles as $title) {
            $task = $this->tasks[$title] ?? null;

            if ($task === null) {
                continue;
            }

            $page->tasks()->syncWithoutDetaching([
                $task->id => ['created_by' => $author->id],
            ]);
        }
    }

    /**
     * @param  array<int, array{name: string, image?: bool}>  $attachments
     */
    private function seedAttachments(DocumentationPage $page, array $attachments, User $author, Carbon $at): void
    {
        foreach ($attachments as $definition) {
            $fileName = $definition['name'];
            $path = 'documentation-attachments/demo-'.Str::uuid()->toString().'-'.$fileName;
            $binary = base64_decode(self::PLACEHOLDER_PNG, true) ?: '';

            Storage::disk('public')->put($path, $binary);

            $attachment = $page->attachments()->create([
                'uploaded_by' => $author->id,
                'disk' => 'public',
                'file_path' => $path,
                'file_name' => $fileName,
                'file_size' => max(strlen($binary), 1),
                'mime_type' => 'image/png',
            ]);

            $attachment->forceFill(['created_at' => $at, 'updated_at' => $at])->save();
        }
    }

    /**
     * @param  array<int, array{user: string, body: string, days_ago: int}>  $comments
     */
    private function seedComments(DocumentationPage $page, array $comments, Carbon $fallbackAt): void
    {
        foreach ($comments as $definition) {
            $comment = $page->comments()->create([
                'user_id' => $this->users[$definition['user']]->id,
                'body' => $definition['body'],
            ]);

            $at = now()->subDays($definition['days_ago']);

            $comment->forceFill(['created_at' => $at, 'updated_at' => $at])->save();
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function seedActivities(
        DocumentationPage $page,
        array $definition,
        User $author,
        Carbon $createdAt,
        Carbon $updatedAt,
        ?Carbon $publishedAt,
    ): void {
        $this->recordActivity($page, $author, DocumentationActivityAction::CREATED, 'Membuat halaman «'.$page->title.'»', $createdAt);

        if ($updatedAt->greaterThan($createdAt)) {
            $this->recordActivity($page, $author, DocumentationActivityAction::UPDATED, 'Memperbarui konten halaman', $updatedAt->copy()->subMinutes(30));
        }

        if (in_array($page->status, [DocumentationPageStatus::IN_REVIEW, DocumentationPageStatus::PUBLISHED, DocumentationPageStatus::CHANGES_REQUESTED], true)) {
            $this->recordActivity($page, $author, DocumentationActivityAction::SUBMITTED_FOR_REVIEW, 'Mengirim halaman untuk review', $updatedAt->copy()->subDay());
        }

        if ($publishedAt !== null) {
            $this->recordActivity($page, $author, DocumentationActivityAction::PUBLISHED, 'Mempublikasikan halaman', $publishedAt);
        }

        foreach ($definition['tasks'] ?? [] as $title) {
            $task = $this->tasks[$title] ?? null;

            if ($task === null) {
                continue;
            }

            $this->recordActivity($page, $author, DocumentationActivityAction::TASK_LINKED, 'Menautkan task «'.$title.'»', $updatedAt->copy()->subHours(4), ['task_id' => $task->id]);
        }

        foreach ($definition['attachments'] ?? [] as $attachment) {
            $this->recordActivity($page, $author, DocumentationActivityAction::ATTACHMENT_ADDED, 'Menambahkan lampiran «'.$attachment['name'].'»', $updatedAt->copy()->subHours(2));
        }

        foreach ($definition['extra_activities'] ?? [] as $extra) {
            $this->recordActivity(
                $page,
                $this->users[$extra['user']],
                $extra['action'],
                $extra['description'],
                $updatedAt->copy()->subHours(1),
            );
        }
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function recordActivity(
        DocumentationPage $page,
        ?User $user,
        DocumentationActivityAction $action,
        string $description,
        Carbon $at,
        array $properties = [],
    ): void {
        $activity = DocumentationActivity::create([
            'space_id' => $page->space_id,
            'page_id' => $page->id,
            'user_id' => $user?->id,
            'action' => $action->value,
            'description' => $description,
            'properties' => $properties === [] ? null : $properties,
        ]);

        $activity->forceFill(['created_at' => $at])->save();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pageDefinitions(): array
    {
        $published = DocumentationPageStatus::PUBLISHED;
        $inReview = DocumentationPageStatus::IN_REVIEW;
        $draft = DocumentationPageStatus::DRAFT;

        return [
            [
                'title' => 'Project Overview',
                'category' => 'Business Process',
                'status' => $published,
                'author' => 'admin',
                'age_days' => 30,
                'updated_days_ago' => 2,
                'published_days_ago' => 25,
                'content' => $this->overviewContent(),
                'versions' => [
                    ['summary' => 'Draft awal overview', 'days_ago' => 28],
                    ['summary' => 'Menambahkan ruang lingkup & milestone', 'days_ago' => 12],
                    ['summary' => 'Perbarui daftar tim', 'days_ago' => 2],
                ],
                'tasks' => ['Setup repository & CI', 'Desain skema database'],
                'attachments' => [
                    ['name' => 'roadmap-2026.png'],
                ],
                'comments' => [
                    ['user' => 'qa', 'body' => 'Bagian milestone sebaiknya ditambah status risiko tiap item.', 'days_ago' => 3],
                    ['user' => 'dev', 'body' => 'Sudah saya tambahkan kolom status. Mohon dicek.', 'days_ago' => 2],
                ],
            ],
            [
                'title' => 'Arsitektur Sistem',
                'category' => 'Architecture',
                'status' => $published,
                'author' => 'dev',
                'age_days' => 26,
                'updated_days_ago' => 4,
                'published_days_ago' => 20,
                'content' => $this->architectureContent(),
                'versions' => [
                    ['summary' => 'Draft arsitektur high level', 'days_ago' => 24],
                    ['summary' => 'Menambahkan diagram alur & lapisan aplikasi', 'days_ago' => 10],
                    ['summary' => 'Menambahkan contoh routing', 'days_ago' => 4],
                ],
                'tasks' => ['Desain skema database', 'CRUD Invoice'],
                'attachments' => [
                    ['name' => 'arsitektur-sistem.png'],
                ],
                'comments' => [
                    ['user' => 'qa', 'body' => 'Diagram Mermaid-nya sudah tampil rapi di pratinjau. 👍', 'days_ago' => 5],
                    ['user' => 'admin', 'body' => 'Tolong tambahkan bagian keamanan sebelum rilis.', 'days_ago' => 4],
                ],
            ],
            [
                'title' => 'Backend (Laravel)',
                'category' => 'Architecture',
                'status' => $published,
                'author' => 'dev',
                'parent' => 'Arsitektur Sistem',
                'age_days' => 20,
                'updated_days_ago' => 6,
                'published_days_ago' => 15,
                'content' => $this->backendContent(),
                'versions' => [
                    ['summary' => 'Draft backend', 'days_ago' => 18],
                    ['summary' => 'Menambahkan pola service', 'days_ago' => 6],
                ],
                'tasks' => ['CRUD Invoice'],
                'comments' => [
                    ['user' => 'qa', 'body' => 'Pola service-nya konsisten dengan modul invoice.', 'days_ago' => 6],
                ],
            ],
            [
                'title' => 'Frontend (Vue 3 + Blade)',
                'category' => 'Architecture',
                'status' => $inReview,
                'author' => 'dev',
                'parent' => 'Arsitektur Sistem',
                'age_days' => 14,
                'updated_days_ago' => 1,
                'content' => $this->frontendContent(),
                'versions' => [
                    ['summary' => 'Draft frontend', 'days_ago' => 12],
                    ['summary' => 'Menambahkan pola SFC + data-config', 'days_ago' => 1],
                ],
                'tasks' => ['Dashboard KPI', 'Portal customer'],
                'comments' => [
                    ['user' => 'qa', 'body' => 'Mohon tambahkan catatan soal dark mode.', 'days_ago' => 1],
                ],
                'extra_activities' => [
                    ['user' => 'admin', 'action' => DocumentationActivityAction::CHANGES_REQUESTED, 'description' => 'Meminta revisi sebelum dipublikasikan'],
                ],
            ],
            [
                'title' => 'Desain Database',
                'category' => 'Database',
                'status' => $published,
                'author' => 'dev',
                'age_days' => 22,
                'updated_days_ago' => 7,
                'published_days_ago' => 18,
                'content' => $this->databaseContent(),
                'versions' => [
                    ['summary' => 'Draft skema', 'days_ago' => 20],
                    ['summary' => 'Menambahkan ERD dan contoh query', 'days_ago' => 7],
                ],
                'tasks' => ['Desain skema database'],
                'attachments' => [
                    ['name' => 'erd-invoice.png'],
                ],
                'comments' => [
                    ['user' => 'admin', 'body' => 'Pastikan kolom audit (created_by/updated_by) konsisten.', 'days_ago' => 8],
                    ['user' => 'dev', 'body' => 'Sudah konsisten di semua tabel utama.', 'days_ago' => 7],
                ],
            ],
            [
                'title' => 'Referensi API',
                'category' => 'API',
                'status' => $inReview,
                'author' => 'dev',
                'age_days' => 18,
                'updated_days_ago' => 2,
                'content' => $this->apiContent(),
                'versions' => [
                    ['summary' => 'Draft referensi API', 'days_ago' => 16],
                    ['summary' => 'Menambahkan contoh request & response', 'days_ago' => 2],
                ],
                'tasks' => ['Integrasi payment gateway'],
                'comments' => [
                    ['user' => 'qa', 'body' => 'Contoh response error perlu ditambahkan juga.', 'days_ago' => 2],
                ],
            ],
            [
                'title' => 'Autentikasi',
                'category' => 'API',
                'status' => $draft,
                'author' => 'dev',
                'parent' => 'Referensi API',
                'age_days' => 9,
                'updated_days_ago' => 3,
                'content' => $this->authContent(),
                'versions' => [
                    ['summary' => 'Draft autentikasi', 'days_ago' => 9],
                ],
            ],
            [
                'title' => 'Endpoint Invoice',
                'category' => 'API',
                'status' => $published,
                'author' => 'dev',
                'parent' => 'Referensi API',
                'age_days' => 15,
                'updated_days_ago' => 5,
                'published_days_ago' => 12,
                'content' => $this->invoiceEndpointContent(),
                'versions' => [
                    ['summary' => 'Draft endpoint invoice', 'days_ago' => 13],
                    ['summary' => 'Menambahkan contoh payload', 'days_ago' => 5],
                ],
                'tasks' => ['CRUD Invoice', 'Export PDF invoice'],
                'comments' => [
                    ['user' => 'qa', 'body' => 'Validasi status invoice sudah sesuai alur.', 'days_ago' => 5],
                ],
            ],
            [
                'title' => 'Business Process',
                'category' => 'Business Process',
                'status' => $published,
                'author' => 'qa',
                'age_days' => 24,
                'updated_days_ago' => 3,
                'published_days_ago' => 19,
                'content' => $this->businessProcessContent(),
                'versions' => [
                    ['summary' => 'Draft proses bisnis', 'days_ago' => 22],
                    ['summary' => 'Menambahkan checklist QA', 'days_ago' => 3],
                ],
                'tasks' => ['Notifikasi email', 'Dashboard KPI'],
                'comments' => [
                    ['user' => 'admin', 'body' => 'Alur sudah sesuai, tinggal penyesuaian istilah.', 'days_ago' => 3],
                ],
            ],
            [
                'title' => 'Panduan Deployment',
                'category' => 'Deployment',
                'status' => $draft,
                'author' => 'dev',
                'age_days' => 8,
                'updated_days_ago' => 1,
                'content' => $this->deploymentContent(),
                'versions' => [
                    ['summary' => 'Draft panduan deploy', 'days_ago' => 8],
                    ['summary' => 'Menambahkan langkah rollback', 'days_ago' => 1],
                ],
                'tasks' => ['Audit keamanan'],
            ],
            [
                'title' => 'Setup Lingkungan Pengembangan',
                'category' => 'Deployment',
                'status' => $published,
                'author' => 'dev',
                'age_days' => 21,
                'updated_days_ago' => 9,
                'published_days_ago' => 17,
                'content' => $this->setupContent(),
                'versions' => [
                    ['summary' => 'Draft setup', 'days_ago' => 21],
                    ['summary' => 'Menambahkan variabel lingkungan', 'days_ago' => 9],
                ],
                'tasks' => ['Setup repository & CI'],
                'comments' => [
                    ['user' => 'qa', 'body' => 'Perintah seeding-nya sudah berhasil dijalankan.', 'days_ago' => 9],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function overviewContent(): array
    {
        return $this->doc([
            $this->heading(1, 'Project Overview'),
            $this->paragraphNodes([
                $this->text('IceSum adalah aplikasi manajemen '),
                $this->bold('project, task, dan e-invoice'),
                $this->text(' untuk klien korporat. Dokumen ini merangkum tujuan, ruang lingkup, dan tim. Lihat '),
                $this->link('Referensi API', '/documentation/pages'),
                $this->text(' untuk detail teknis.'),
            ]),
            $this->heading(2, 'Tujuan'),
            $this->bulletList([
                'Menyatukan pengelolaan project dan penagihan dalam satu aplikasi.',
                'Menyediakan dokumentasi teknis yang selalu sinkron dengan task.',
                'Mempercepat pembuatan invoice dari task yang billable.',
            ]),
            $this->heading(2, 'Ruang Lingkup'),
            $this->checklist([
                ['text' => 'Manajemen project & task', 'checked' => true],
                ['text' => 'Invoice & export PDF', 'checked' => true],
                ['text' => 'Portal customer', 'checked' => false],
                ['text' => 'Pembayaran online', 'checked' => false],
            ]),
            $this->heading(2, 'Tim'),
            $this->table(
                ['Peran', 'Nama', 'Fokus'],
                [
                    ['Tech Lead', 'Bagus Panji', 'Arsitektur & backend'],
                    ['QA Engineer', 'Rani Puspita', 'Pengujian & proses bisnis'],
                    ['Project Owner', 'Admin', 'Prioritas & rilis'],
                ],
            ),
            $this->heading(2, 'Milestone'),
            $this->table(
                ['Milestone', 'Target', 'Status'],
                [
                    ['MVP Invoice', '2026-08', 'Selesai'],
                    ['Portal Customer', '2026-11', 'Berjalan'],
                    ['Pembayaran Online', '2027-01', 'Direncanakan'],
                ],
            ),
            $this->blockquote('Dokumen ini diperbarui setiap kali ada perubahan ruang lingkup yang disetujui.'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function architectureContent(): array
    {
        return $this->doc([
            $this->heading(1, 'Arsitektur Sistem'),
            $this->paragraph('Aplikasi memakai Laravel sebagai backend dan Vue 3 (SFC di dalam shell Blade) untuk antarmuka interaktif.'),
            $this->mermaid("flowchart LR\n    Browser -->|HTTP| Laravel\n    Laravel --> MySQL\n    Laravel --> Queue\n    Queue --> Mail"),
            $this->heading(2, 'Lapisan Aplikasi'),
            $this->orderedList([
                'Controller menerima request dan mengembalikan Blade atau JSON.',
                'Service menangani aturan bisnis, transaksi, dan perhitungan.',
                'Model menangani relasi dan cast data.',
            ]),
            $this->heading(2, 'Contoh Routing'),
            $this->code('php', "Route::middleware('role:staff')->group(function () {\n    Route::get('documentation/pages/{page}', [PageController::class, 'show']);\n});"),
            $this->heading(2, 'Komponen Utama'),
            $this->table(
                ['Komponen', 'Teknologi', 'Tanggung jawab'],
                [
                    ['Backend', 'Laravel 13', 'REST/JSON, bisnis, persistensi'],
                    ['Frontend', 'Vue 3 + Vite', 'Interaksi editor & board'],
                    ['Database', 'MySQL 8', 'Penyimpanan data'],
                    ['Queue', 'Laravel Queue', 'Email & job berat'],
                ],
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function backendContent(): array
    {
        return $this->doc([
            $this->heading(1, 'Backend (Laravel)'),
            $this->paragraph('Backend mengikuti pola existing: controller tipis, logika di service, dan validasi lewat $request->validate().'),
            $this->heading(2, 'Pola Service'),
            $this->code('php', "\$invoice = \$bookingService->create(\$data, \$user);\n\$bookingService->markPaid(\$invoice);"),
            $this->heading(2, 'Konvensi'),
            $this->bulletList([
                'Gunakan DB::transaction untuk operasi multi-tabel.',
                'Gunakan enum untuk status.',
                'Selalu periksa otorisasi di sisi server.',
            ]),
            $this->checklist([
                ['text' => 'Unit test untuk service', 'checked' => true],
                ['text' => 'Feature test untuk endpoint', 'checked' => true],
            ]),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function frontendContent(): array
    {
        return $this->doc([
            $this->heading(1, 'Frontend (Vue 3 + Blade)'),
            $this->paragraph('Setiap fitur interaktif dimuat sebagai SFC Vue di dalam shell Blade, memakai data-config untuk konfigurasi awal.'),
            $this->heading(2, 'Entry & Mounting'),
            $this->code('js', "import { createApp } from 'vue';\nimport BoardApp from './components/BoardApp.vue';\n\ncreateApp(BoardApp, { config }).mount('#board-app');"),
            $this->heading(2, 'Catatan'),
            $this->bulletList([
                'Kelas Tailwind harus literal di komponen, bukan dari payload JSON.',
                'Gunakan fetch dengan X-CSRF-TOKEN untuk aksi JSON.',
            ]),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function databaseContent(): array
    {
        return $this->doc([
            $this->heading(1, 'Desain Database'),
            $this->paragraph('Skema memakai relasi foreign key dengan cascade pada data turunan.'),
            $this->heading(2, 'Tabel Utama'),
            $this->table(
                ['Tabel', 'Fungsi', 'Relasi'],
                [
                    ['projects', 'Data project', 'belongsTo customers'],
                    ['tasks', 'Pekerjaan per project', 'belongsTo projects, task_groups'],
                    ['invoices', 'Tagihan', 'belongsTo customers'],
                    ['invoice_items', 'Rincian tagihan', 'belongsTo invoices'],
                ],
            ),
            $this->heading(2, 'ERD'),
            $this->mermaid("erDiagram\n    CUSTOMERS ||--o{ PROJECTS : has\n    CUSTOMERS ||--o{ INVOICES : billed\n    PROJECTS ||--o{ TASKS : contains\n    INVOICES ||--o{ INVOICE_ITEMS : contains"),
            $this->heading(2, 'Contoh Query'),
            $this->code('sql', "SELECT p.name, COUNT(t.id) AS total_tasks\nFROM projects p\nLEFT JOIN tasks t ON t.project_id = p.id\nGROUP BY p.id;"),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function apiContent(): array
    {
        return $this->doc([
            $this->heading(1, 'Referensi API'),
            $this->paragraph('Endpoint JSON memakai session guard dan CSRF (bukan token API), konsisten dengan pola aplikasi.'),
            $this->heading(2, 'Konvensi'),
            $this->bulletList([
                'Nama route dengan titik, binding memakai ID.',
                'Response sukses dibungkus { success, data }.',
                'Kode status 422 untuk validasi, 409 untuk konflik.',
            ]),
            $this->heading(2, 'Daftar Endpoint'),
            $this->table(
                ['Method', 'Endpoint', 'Deskripsi'],
                [
                    ['GET', '/projects', 'Daftar project'],
                    ['POST', '/projects/{project}/tasks', 'Tambah task'],
                    ['PATCH', '/invoices/{invoice}', 'Perbarui invoice'],
                    ['POST', '/invoices/{invoice}/paid', 'Tandai lunas'],
                ],
            ),
            $this->heading(2, 'Contoh Request'),
            $this->code('http', "PATCH /invoices/42\nContent-Type: application/json\nX-CSRF-TOKEN: ..."),
            $this->heading(2, 'Contoh Response'),
            $this->code('json', "{\n  \"success\": true,\n  \"data\": { \"id\": 42, \"status\": \"paid\" }\n}"),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function authContent(): array
    {
        return $this->doc([
            $this->heading(1, 'Autentikasi'),
            $this->paragraph('Autentikasi memakai session guard bawaan Laravel dengan role staff dan customer.'),
            $this->heading(2, 'Alur Login'),
            $this->orderedList([
                'Pengguna mengirim kredensial.',
                'Sistem memverifikasi dan membuat sesi.',
                'Middleware role menentukan area yang dapat diakses.',
            ]),
            $this->paragraph('Catatan: dokumen ini masih draft dan belum ditinjau.'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function invoiceEndpointContent(): array
    {
        return $this->doc([
            $this->heading(1, 'Endpoint Invoice'),
            $this->paragraph('Endpoint untuk mengelola invoice, item, status, dan ekspor PDF.'),
            $this->heading(2, 'Detail'),
            $this->table(
                ['Method', 'Endpoint', 'Deskripsi'],
                [
                    ['GET', '/invoices', 'Daftar invoice'],
                    ['GET', '/invoices/{invoice}/pdf', 'Unduh PDF'],
                    ['GET', '/invoices/{invoice}/export', 'Ekspor spreadsheet'],
                    ['POST', '/invoices/{invoice}/paid', 'Tandai lunas'],
                ],
            ),
            $this->heading(2, 'Contoh Payload'),
            $this->code('json', "{\n  \"customer_id\": 7,\n  \"items\": [\n    { \"description\": \"Development\", \"amount\": 2500000 }\n  ]\n}"),
            $this->checklist([
                ['text' => 'Validasi item tidak kosong', 'checked' => true],
                ['text' => 'Perhitungan total & pajak', 'checked' => true],
                ['text' => 'Riwayat status', 'checked' => false],
            ]),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function businessProcessContent(): array
    {
        return $this->doc([
            $this->heading(1, 'Business Process'),
            $this->paragraph('Alur utama dari task selesai hingga invoice terkirim ke customer.'),
            $this->heading(2, 'Alur Invoice'),
            $this->mermaid("flowchart TD\n    A[Task selesai] --> B{Terkumpul?}\n    B -->|Ya| C[Generate invoice]\n    B -->|Belum| A\n    C --> D[Kirim ke customer]\n    D --> E[Dibayar]"),
            $this->heading(2, 'Langkah'),
            $this->orderedList([
                'Tandai task billable sebagai selesai.',
                'Pilih customer dan periode penagihan.',
                'Generate invoice dari task yang belum tertagih.',
                'Kirim invoice dan pantau status pembayaran.',
            ]),
            $this->heading(2, 'Checklist QA'),
            $this->checklist([
                ['text' => 'Total invoice sesuai task', 'checked' => true],
                ['text' => 'Data customer benar', 'checked' => true],
                ['text' => 'PDF tampil rapi', 'checked' => true],
                ['text' => 'Status berpindah dengan benar', 'checked' => false],
            ]),
            $this->blockquote('Invoice yang sudah dibayar tidak dapat dibatalkan.'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function deploymentContent(): array
    {
        return $this->doc([
            $this->heading(1, 'Panduan Deployment'),
            $this->paragraph('Langkah rilis ke lingkungan produksi beserta prosedur rollback.'),
            $this->heading(2, 'Prasyarat'),
            $this->checklist([
                ['text' => 'Semua test hijau', 'checked' => true],
                ['text' => 'Migration sudah ditinjau', 'checked' => true],
                ['text' => 'Backup database', 'checked' => false],
            ]),
            $this->heading(2, 'Langkah Deploy'),
            $this->orderedList([
                'Aktifkan mode maintenance.',
                'Pull kode dan jalankan composer install --no-dev.',
                'Jalankan migrate --force dan npm run build.',
                'Matikan mode maintenance.',
            ]),
            $this->code('bash', "php artisan down\nphp artisan migrate --force\nnpm run build\nphp artisan up"),
            $this->heading(2, 'Rollback'),
            $this->mermaid("flowchart LR\n    Detect[Deteksi masalah] --> Decide{Berat?}\n    Decide -->|Ringan| Hotfix\n    Decide -->|Berat| Rollback"),
            $this->paragraph('Dokumen ini masih draft dan belum ditinjau.'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function setupContent(): array
    {
        return $this->doc([
            $this->heading(1, 'Setup Lingkungan Pengembangan'),
            $this->paragraph('Cara menyiapkan proyek di mesin lokal.'),
            $this->heading(2, 'Perintah'),
            $this->code('bash', "composer install\ncp .env.example .env\nphp artisan key:generate\nphp artisan migrate --seed\nnpm install && npm run dev"),
            $this->heading(2, 'Variabel Lingkungan'),
            $this->table(
                ['Variabel', 'Deskripsi'],
                [
                    ['DB_DATABASE', 'Nama database lokal'],
                    ['APP_URL', 'URL aplikasi (mis. https://e-invoice.test)'],
                    ['QUEUE_CONNECTION', 'Driver queue (database)'],
                ],
            ),
            $this->blockquote('Jalankan php artisan db:seed --class=DocumentationDemoSeeder untuk data contoh dokumentasi.'),
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<string, mixed>
     */
    private function doc(array $blocks): array
    {
        return ['type' => 'doc', 'content' => $blocks];
    }

    private function heading(int $level, string $text): array
    {
        return ['type' => 'heading', 'attrs' => ['level' => $level], 'content' => [$this->text($text)]];
    }

    private function paragraph(string $text): array
    {
        return ['type' => 'paragraph', 'content' => [$this->text($text)]];
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array<string, mixed>
     */
    private function paragraphNodes(array $nodes): array
    {
        return ['type' => 'paragraph', 'content' => $nodes];
    }

    /**
     * @return array<string, mixed>
     */
    private function text(string $text): array
    {
        return ['type' => 'text', 'text' => $text];
    }

    /**
     * @return array<string, mixed>
     */
    private function bold(string $text): array
    {
        return ['type' => 'text', 'text' => $text, 'marks' => [['type' => 'bold']]];
    }

    /**
     * @return array<string, mixed>
     */
    private function link(string $text, string $href): array
    {
        return ['type' => 'text', 'text' => $text, 'marks' => [['type' => 'link', 'attrs' => ['href' => $href]]]];
    }

    /**
     * @param  array<int, string>  $items
     * @return array<string, mixed>
     */
    private function bulletList(array $items): array
    {
        return [
            'type' => 'bulletList',
            'content' => array_map(fn (string $item): array => [
                'type' => 'listItem',
                'content' => [$this->paragraph($item)],
            ], $items),
        ];
    }

    /**
     * @param  array<int, string>  $items
     * @return array<string, mixed>
     */
    private function orderedList(array $items): array
    {
        return [
            'type' => 'orderedList',
            'content' => array_map(fn (string $item): array => [
                'type' => 'listItem',
                'content' => [$this->paragraph($item)],
            ], $items),
        ];
    }

    /**
     * @param  array<int, array{text: string, checked: bool}>  $items
     * @return array<string, mixed>
     */
    private function checklist(array $items): array
    {
        return [
            'type' => 'taskList',
            'content' => array_map(fn (array $item): array => [
                'type' => 'taskItem',
                'attrs' => ['checked' => $item['checked']],
                'content' => [$this->paragraph($item['text'])],
            ], $items),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function code(string $language, string $code): array
    {
        return [
            'type' => 'codeBlock',
            'attrs' => ['language' => $language],
            'content' => [$this->text($code)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mermaid(string $code): array
    {
        return $this->code('mermaid', $code);
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, string>>  $rows
     * @return array<string, mixed>
     */
    private function table(array $headers, array $rows): array
    {
        $content = [$this->tableRow($headers, true)];

        foreach ($rows as $row) {
            $content[] = $this->tableRow($row, false);
        }

        return ['type' => 'table', 'content' => $content];
    }

    /**
     * @param  array<int, string>  $cells
     * @return array<string, mixed>
     */
    private function tableRow(array $cells, bool $header): array
    {
        return [
            'type' => 'tableRow',
            'content' => array_map(fn (string $cell): array => [
                'type' => $header ? 'tableHeader' : 'tableCell',
                'content' => [$this->paragraph($cell)],
            ], $cells),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function blockquote(string $text): array
    {
        return ['type' => 'blockquote', 'content' => [$this->paragraph($text)]];
    }
}
