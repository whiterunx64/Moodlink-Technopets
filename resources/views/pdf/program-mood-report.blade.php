@php
    /** @var array $detail */
    $moodValues = [
        'Excited' => $detail['excited'],
        'Content' => $detail['content'],
        'Stressed' => $detail['stressed'],
        'Drained' => $detail['drained'],
    ];
    $maxMood = max(1, ...array_values($moodValues));

    // The ONLY colours in the document — one per section title.
    $sectionColor = [
        'overview' => '#1e3a5f',
        'moods'    => '#0f766e',
        'roster'   => '#831843',
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

        .masthead { border-bottom: 2px solid #1a1a1a; padding-bottom: 12px; margin-bottom: 20px; }
        .crest { font-size: 9px; letter-spacing: 3px; text-transform: uppercase; color: #666; margin-bottom: 6px; }
        .doc-title { font-size: 23px; font-weight: bold; margin: 0; }
        .doc-sub { font-size: 10px; color: #444; margin-top: 5px; }

        .section { margin-bottom: 22px; }
        .sec-title {
            font-size: 13px; font-weight: bold; margin: 0 0 10px;
            padding-bottom: 4px; border-bottom: 1.5px solid #ccc;
        }

        table { width: 100%; border-collapse: collapse; }

        /* Overview stats — pure-black underline under each figure */
        .stat-cell { padding: 4px 14px 4px 0; vertical-align: top; width: 25%; }
        .stat-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; color: #666; }
        .stat-value {
            display: block; font-size: 28px; font-weight: bold; color: #000;
            border-bottom: 2px solid #000; padding-bottom: 5px; margin-top: 4px;
        }

        /* Mood bars */
        .bar-track { background: #ededed; border: 1px solid #ccc; height: 15px; width: 100%; }
        .bar-fill { height: 15px; background: #555; }
        .grid td { padding: 6px 0; vertical-align: middle; }

        /* Student roster table */
        .roster th, .roster td {
            border: 1px solid #c0c0c0; padding: 6px 8px; font-size: 10px; text-align: left;
        }
        .roster th {
            background: #f0f0f0; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .roster tr:nth-child(even) td { background: #f8f8f8; }
        .roster td.center, .roster th.center { text-align: center; }

        .footer {
            position: fixed; bottom: -38px; left: 0; right: 0;
            border-top: 1px solid #bbb; padding-top: 6px; font-size: 8px; color: #777;
        }
        .footer .page:after { content: counter(page); }
    </style>
</head>
<body>

    <div class="footer">
        <span>Generated {{ $generatedAt }} · MoodSpace — Confidential Program Record</span>
        <span style="float:right;">Page <span class="page"></span></span>
    </div>

    {{-- Masthead --}}
    <div class="masthead">
        <div class="crest">MoodSpace · Student Wellbeing Office</div>
        <h1 class="doc-title">Program Mood Report</h1>
        <div class="doc-sub">
            <strong>{{ $detail['program'] }}</strong>
            &nbsp;·&nbsp; {{ $periodLabel }}
            &nbsp;·&nbsp; {{ count($detail['students']) }} students
        </div>
    </div>

    {{-- I. Overview --}}
    <div class="section">
        <div class="sec-title" style="color: {{ $sectionColor['overview'] }};">I. Program Overview</div>
        <table>
            <tr>
                <td class="stat-cell">
                    <span class="stat-label">Total Mood Entries</span><br>
                    <span class="stat-value">{{ $detail['total'] }}</span>
                </td>
                <td class="stat-cell">
                    <span class="stat-label">Students</span><br>
                    <span class="stat-value">{{ count($detail['students']) }}</span>
                </td>
                <td class="stat-cell">
                    <span class="stat-label">At-Risk Students</span><br>
                    <span class="stat-value">{{ $detail['at_risk'] }}</span>
                </td>
                <td class="stat-cell"></td>
            </tr>
        </table>
    </div>

    {{-- II. Mood breakdown --}}
    <div class="section">
        <div class="sec-title" style="color: {{ $sectionColor['moods'] }};">II. Mood Breakdown</div>
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

    {{-- III. Student roster --}}
    <div class="section">
        <div class="sec-title" style="color: {{ $sectionColor['roster'] }};">III. Student Roster</div>
        @if (count($detail['students']) === 0)
            <div class="muted">No students in this program.</div>
        @else
            <table class="roster">
                <thead>
                    <tr>
                        <th style="width:4%;" class="center">#</th>
                        <th style="width:26%;">Name</th>
                        <th style="width:16%;">Student No.</th>
                        <th style="width:8%;">Year</th>
                        <th style="width:7%;" class="center" title="Excited">Exc</th>
                        <th style="width:7%;" class="center" title="Content">Con</th>
                        <th style="width:7%;" class="center" title="Stressed">Str</th>
                        <th style="width:7%;" class="center" title="Drained">Dra</th>
                        <th style="width:18%;" class="center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($detail['students'] as $i => $student)
                        <tr>
                            <td class="center">{{ $i + 1 }}</td>
                            <td>{{ $student['name'] }}</td>
                            <td>{{ $student['student_number'] }}</td>
                            <td>{{ $student['year_level'] }}</td>
                            <td class="center">{{ $student['mood_counts']['Excited'] ?: '—' }}</td>
                            <td class="center">{{ $student['mood_counts']['Content'] ?: '—' }}</td>
                            <td class="center">{{ $student['mood_counts']['Stressed'] ?: '—' }}</td>
                            <td class="center">{{ $student['mood_counts']['Drained'] ?: '—' }}</td>
                            <td class="center" @if($student['at_risk']) style="font-weight:bold; color:#b91c1c;" @endif>
                                {{ $student['at_risk'] ? 'AT RISK' : 'Stable' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="muted" style="font-size:9px; margin-top:6px;">
                Exc = Excited · Con = Content · Str = Stressed · Dra = Drained (mood entry counts for the period)
            </p>
        @endif
    </div>

</body>
</html>
