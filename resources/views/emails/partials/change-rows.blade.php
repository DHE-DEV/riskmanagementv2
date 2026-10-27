{{-- Tabelle bisher/neu fuer Aenderungsmails; geaenderte Zeilen gelb markiert --}}
<table role="presentation" cellpadding="0" cellspacing="0" style="width: 100%; margin: 0 0 20px 0; font-size: 14px; border-collapse: collapse;">
    <tr>
        <th style="padding: 8px 10px; text-align: left; font-size: 12px; color: #71717a; border-bottom: 1px solid #e4e4e7;">Feld</th>
        <th style="padding: 8px 10px; text-align: left; font-size: 12px; color: #71717a; border-bottom: 1px solid #e4e4e7;">Bisher</th>
        <th style="padding: 8px 10px; text-align: left; font-size: 12px; color: #71717a; border-bottom: 1px solid #e4e4e7;">Neu</th>
    </tr>
    @foreach ($rows as $row)
        <tr style="{{ $row['changed'] ? 'background: #fef9c3;' : '' }}">
            <td style="padding: 6px 10px; color: #71717a; white-space: nowrap; border-bottom: 1px solid #f4f4f5;">{{ $row['label'] }}</td>
            <td style="padding: 6px 10px; border-bottom: 1px solid #f4f4f5; {{ $row['changed'] && $row['old'] !== '' ? 'color: #71717a; text-decoration: line-through;' : '' }}">{{ $row['old'] !== '' ? $row['old'] : '–' }}</td>
            <td style="padding: 6px 10px; border-bottom: 1px solid #f4f4f5; {{ $row['changed'] ? 'font-weight: 600;' : '' }}">{{ $row['new'] !== '' ? $row['new'] : '–' }}</td>
        </tr>
    @endforeach
</table>
