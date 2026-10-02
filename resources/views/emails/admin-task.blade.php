<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $heading }}: {{ $task->title }}</title>
</head>
<body style="margin: 0; padding: 24px; background: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; color: #18181b; line-height: 1.5;">
    <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e4e4e7;">
        <div style="background: #0b3250; padding: 20px 28px; color: #ffffff; font-size: 14px; font-weight: 600;">
            Passolution Travel Information Platform
        </div>

        <div style="padding: 28px;">
            <p style="margin: 0 0 6px 0; font-size: 13px; color: #71717a;">{{ $intro }}</p>
            <h1 style="margin: 0 0 20px 0; font-size: 20px; line-height: 1.3;">{{ $task->title }}</h1>

            @if (! empty($note))
                <p style="margin: 0 0 20px 0; padding: 10px 14px; background: #fef9c3; border-radius: 8px; white-space: pre-line; color: #3f3f46;">{{ $note }}</p>
            @endif

            @if ($task->description)
                <p style="margin: 0 0 20px 0; white-space: pre-line; color: #3f3f46;">{{ $task->description }}</p>
            @endif

            <table role="presentation" cellpadding="0" cellspacing="0" style="width: 100%; margin: 0 0 24px 0; font-size: 14px;">
                <tr>
                    <td style="padding: 4px 16px 4px 0; color: #71717a; white-space: nowrap;">Rubrik</td>
                    <td style="padding: 4px 0;">{{ $task->category?->name ?? '–' }}</td>
                </tr>
                <tr>
                    <td style="padding: 4px 16px 4px 0; color: #71717a; white-space: nowrap;">Priorität</td>
                    <td style="padding: 4px 0;">{{ \App\Models\AdminTask::priorityOptions()[$task->priority] ?? $task->priority }}</td>
                </tr>
                <tr>
                    <td style="padding: 4px 16px 4px 0; color: #71717a; white-space: nowrap;">Fällig am</td>
                    <td style="padding: 4px 0;">{{ $task->due_date?->format('d.m.Y') ?? '–' }}</td>
                </tr>
                <tr>
                    <td style="padding: 4px 16px 4px 0; color: #71717a; white-space: nowrap;">Liegt bei</td>
                    <td style="padding: 4px 0;">{{ $task->handlerLabel() ?? '–' }}</td>
                </tr>
                <tr>
                    <td style="padding: 4px 16px 4px 0; color: #71717a; white-space: nowrap;">Verantwortlich</td>
                    <td style="padding: 4px 0;">{{ $task->responsibleLabel() ?? '–' }}</td>
                </tr>
                <tr>
                    <td style="padding: 4px 16px 4px 0; color: #71717a; white-space: nowrap;">Erfasst von</td>
                    <td style="padding: 4px 0;">{{ trim((string) $task->creator?->name) ?: '–' }}</td>
                </tr>
                @if ($subject = $task->subjectLabel())
                    <tr>
                        <td style="padding: 4px 16px 4px 0; color: #71717a; white-space: nowrap;">Bezug</td>
                        <td style="padding: 4px 0;">{{ $subject }}</td>
                    </tr>
                @endif
            </table>

            <a href="{{ $url }}" style="display: inline-block; background: #0b3250; color: #ffffff; text-decoration: none; padding: 11px 22px; border-radius: 8px; font-weight: 600; font-size: 14px;">
                Aufgabe öffnen
            </a>
        </div>
    </div>
</body>
</html>
