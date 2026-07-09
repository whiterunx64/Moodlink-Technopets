@php
    /** @var array $report */
    $moodValues = [
        'Excited' => $report['mood_summary']['excited'],
        'Content' => $report['mood_summary']['content'],
        'Stressed' => $report['mood_summary']['stressed'],
        'Drained' => $report['mood_summary']['drained'],
    ];
    $maxMood = max(1, ...array_values($moodValues));

    $periodLabel = [
        'this_week' => 'This Week',
        'this_month' => 'This Month',
        'all_time' => 'All Time',
    ][$period] ?? ucfirst($period);

    // The ONLY colours in the document — one per section title.
    $sectionColor = [
        'profile'  => '#1e3a5f',
        'summary'  => '#5b21b6',
        'entries'  => '#0f766e',
        'trend'    => '#9a3412',
        'journal'  => '#831843',
    ];
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 42px 46px 56px; }
        * { box-sizing: border-box; }
        body {
            font-family: Georgia, 'Times New Roman', serif;
            color: #1a1a1a;
            font-size: 11px;
            line-height: 1.55;
            margin: 0;
        }
        .muted { color: #555; }

        /* ── Masthead ────────────────────────────────────────────── */
        .masthead {
            border-bottom: 2px solid #1a1a1a;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .crest {
            font-size: 9px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 6px;
        }
        .doc-title { font-size: 23px; font-weight: bold; margin: 0; }
        .doc-sub { font-size: 10px; color: #444; margin-top: 5px; }

        /* ── Section titles — the only coloured text ─────────────── */
        .section { margin-bottom: 22px; }
        .sec-title {
            font-size: 13px;
            font-weight: bold;
            margin: 0 0 10px;
            padding-bottom: 4px;
            border-bottom: 1.5px solid #ccc;
        }

        table { width: 100%; border-collapse: collapse; }

        /* ── Profile (pure-black underline under each field) ─────── */
        .info td { padding: 8px 12px 8px 0; vertical-align: top; }
        .info .label { color: #666; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; }
        .info .value {
            display: block; font-weight: bold; font-size: 14px; color: #000;
            border-bottom: 2px solid #000; padding-bottom: 5px; margin-top: 3px;
        }

        /* ── Summary stats (pure-black underline under each) ─────── */
        .stat-cell { padding: 4px 14px 4px 0; vertical-align: top; width: 33%; }
        .stat-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; color: #666; }
        .stat-value {
            display: block; font-size: 30px; font-weight: bold; color: #000;
            border-bottom: 2px solid #000; padding-bottom: 5px; margin-top: 4px;
        }

        /* ── Mood bars (grey, neutral) ───────────────────────────── */
        .bar-track { background: #ededed; border: 1px solid #ccc; height: 15px; width: 100%; }
        .bar-fill { height: 15px; background: #555; }
        .grid td { padding: 6px 0; vertical-align: middle; }

        /* ── Data table ──────────────────────────────────────────── */
        .data-table th, .data-table td {
            border: 1px solid #c0c0c0; padding: 5px 7px; text-align: center; font-size: 10px;
        }
        .data-table th {
            background: #f0f0f0; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .data-table tr:nth-child(even) td { background: #f8f8f8; }
        .data-table td.date { text-align: left; white-space: nowrap; }
        .data-table td.total { font-weight: bold; }

        /* ── Journal ─────────────────────────────────────────────── */
        .entry td { border-bottom: 1px solid #e0e0e0; padding: 9px 6px; vertical-align: top; }
        .tag {
            display: inline-block; padding: 3px 9px; font-size: 9px; font-weight: bold;
            border: 1px solid #ccc; background: #f2f2f2; color: #333;
        }
        .journal-text { font-style: italic; }

        /* ── Footer ──────────────────────────────────────────────── */
        .footer {
            position: fixed; bottom: -38px; left: 0; right: 0;
            border-top: 1px solid #bbb; padding-top: 6px;
            font-size: 8px; color: #777;
        }
        .footer .page:after { content: counter(page); }
    </style>
</head>
<body>

    <div class="footer">
        <span>Generated {{ $generatedAt }} · MoodSpace — Confidential Student Record</span>
        <span style="float:right;">Page <span class="page"></span></span>
    </div>

    {{-- Masthead --}}
    <div class="masthead">
        <div class="crest">MoodSpace · Student Wellbeing Office</div>
        <h1 class="doc-title">Student Mood Report</h1>
        <div class="doc-sub">
            <strong>{{ $report['name'] }}</strong> — {{ $report['program'] }}
            &nbsp;·&nbsp; {{ $periodLabel }}
            &nbsp;·&nbsp; Trend window: last {{ $trendDays }} days
        </div>
    </div>

    {{-- I. Profile --}}
    <div class="section">
        <div class="sec-title" style="color: {{ $sectionColor['profile'] }};">I. Student Profile</div>
        <table class="info">
            <tr>
                <td width="25%"><span class="label">Name of Student</span><br><span class="value">{{ $report['full_name'] }}</span></td>
                <td width="25%"><span class="label">Student Number</span><br><span class="value">{{ $report['student_number'] }}</span></td>
                <td width="25%"><span class="label">Year Level</span><br><span class="value">{{ $report['year_level'] }}</span></td>
                <td width="25%"><span class="label">Program</span><br><span class="value">{{ $report['program'] }}</span></td>
            </tr>
        </table>
    </div>

    {{-- II. Summary --}}
    <div class="section">
        <div class="sec-title" style="color: {{ $sectionColor['summary'] }};">II. Summary At a Glance</div>
        <table>
            <tr>
                <td class="stat-cell">
                    <span class="stat-label">Total Mood Entries</span><br>
                    <span class="stat-value">{{ $report['summary_stats']['total_mood_entries'] }}</span>
                </td>
                <td class="stat-cell">
                    <span class="stat-label">Total Posts</span><br>
                    <span class="stat-value">{{ $report['summary_stats']['total_posts'] }}</span>
                </td>
                <td class="stat-cell">
                    <span class="stat-label">Flagged Posts</span><br>
                    <span class="stat-value">{{ $report['summary_stats']['flagged_posts'] }}</span>
                </td>
            </tr>
        </table>
    </div>

    {{-- III. Mood entries --}}
    <div class="section">
        <div class="sec-title" style="color: {{ $sectionColor['entries'] }};">III. Mood Entries Breakdown</div>
        <table class="grid">
            @foreach ($moodValues as $mood => $value)
                @php $pct = round($value / $maxMood * 100); @endphp
                <tr>
                    <td width="80" style="font-weight:bold;">{{ $mood }}</td>
                    <td>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: {{ max($pct, 1) }}%;"></div>
                        </div>
                    </td>
                    <td width="34" style="text-align:right; font-weight:bold; font-size:13px;">{{ $value }}</td>
                </tr>
            @endforeach
        </table>
    </div>

    {{-- IV. Trend table --}}
    <div class="section">
        <div class="sec-title" style="color: {{ $sectionColor['trend'] }};">IV. MoodSpace Posts — Last {{ $trendDays }} Days</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="text-align:left;">Date</th>
                    @foreach ($moods as $mood)
                        <th>{{ $mood }}</th>
                    @endforeach
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($trendRows as $row)
                    <tr>
                        <td class="date">{{ $row['date'] }}</td>
                        @foreach ($moods as $mood)
                            <td>{{ $row['counts'][$mood] ?: '—' }}</td>
                        @endforeach
                        <td class="total">{{ $row['total'] ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- V. Journal --}}
    <div class="section">
        <div class="sec-title" style="color: {{ $sectionColor['journal'] }};">V. Recent Journal Entries</div>
        @if (count($report['recent_entries']) === 0)
            <div class="muted">No journal entries available.</div>
        @else
            <table>
                @foreach ($report['recent_entries'] as $entry)
                    <tr class="entry">
                        <td width="80">
                            <span class="tag">{{ $entry['mood'] }}</span>
                        </td>
                        <td>
                            @if (!empty($entry['content']))
                                <span class="journal-text">“{{ $entry['content'] }}”</span>
                            @else
                                <span class="muted journal-text">No journal written for this entry.</span>
                            @endif
                        </td>
                        <td width="95" class="muted" style="text-align:right; white-space:nowrap; font-size:9px;">{{ $entry['date'] }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>

</body>
</html>
