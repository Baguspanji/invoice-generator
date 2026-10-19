<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $page->title }}</title>
    <style>
        * {
            font-family: DejaVu Sans, sans-serif;
        }

        body {
            color: #1e293b;
            font-size: 12px;
            line-height: 1.6;
            margin: 0;
        }

        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 10px;
            margin-bottom: 18px;
        }

        .eyebrow {
            color: #2563eb;
            font-size: 10px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        h1.title {
            font-size: 22px;
            margin: 4px 0 6px;
        }

        .meta {
            color: #64748b;
            font-size: 10px;
        }

        .badge {
            background: #eff6ff;
            color: #2563eb;
            border-radius: 10px;
            padding: 2px 8px;
            font-size: 9px;
            text-transform: uppercase;
        }

        .content h1,
        .content h2,
        .content h3 {
            color: #0f172a;
            margin: 18px 0 6px;
        }

        .content h1 { font-size: 18px; }
        .content h2 { font-size: 15px; }
        .content h3 { font-size: 13px; }

        .content p {
            margin: 6px 0;
        }

        .content ul,
        .content ol {
            margin: 6px 0 6px 18px;
        }

        .content blockquote {
            border-left: 3px solid #cbd5e1;
            color: #475569;
            margin: 8px 0;
            padding-left: 10px;
        }

        .content pre {
            background: #0f172a;
            color: #e2e8f0;
            padding: 10px;
            border-radius: 6px;
            font-size: 10px;
            white-space: pre-wrap;
        }

        .content code {
            font-family: DejaVu Sans Mono, monospace;
        }

        .content table {
            border-collapse: collapse;
            width: 100%;
            margin: 8px 0;
        }

        .content th,
        .content td {
            border: 1px solid #cbd5e1;
            padding: 5px 7px;
            text-align: left;
        }

        .content th {
            background: #f1f5f9;
        }

        .content .mermaid {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            padding: 10px;
            font-family: DejaVu Sans Mono, monospace;
            font-size: 10px;
            white-space: pre-wrap;
        }

        .content .callout {
            background: #f8fafc;
            border-left: 3px solid #2563eb;
            padding: 8px 10px;
            margin: 8px 0;
        }

        .footer {
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 9px;
            margin-top: 22px;
            padding-top: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="eyebrow">{{ $project?->name ?? 'IceSum' }} &middot; Dokumentasi</div>
        <h1 class="title">{{ $page->title }}</h1>
        <div class="meta">
            <span class="badge">{{ $page->status->label() }}</span>
            &nbsp; Diperbarui {{ $page->updated_at?->translatedFormat('d M Y H:i') }}
            @if ($page->published_at)
                &middot; Dipublikasikan {{ $page->published_at->translatedFormat('d M Y H:i') }}
            @endif
        </div>
    </div>

    <div class="content">
        {!! $contentHtml !!}
    </div>

    <div class="footer">
        Dibuat otomatis oleh IceSum pada {{ $generatedAt->translatedFormat('d M Y H:i') }}.
        Diagram Mermaid ditampilkan sebagai teks sumber pada ekspor PDF.
    </div>
</body>
</html>
