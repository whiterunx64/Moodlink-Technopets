<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Session Check-in</title>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: #ffffff;
            font-family: Georgia, 'Times New Roman', Times, serif;
            color: #1a1a1a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 20px;
        }

        .wrap {
            width: 100%;
            max-width: 560px;
        }

        /* ── Letterhead ── */
        .lh-unit {
            font-size: 13px;
            font-style: italic;
            color: #555;
            margin-bottom: 10px;
        }

        .rule {
            border: none;
            border-top: 1px solid #000;
            margin: 0 0 30px;
        }

        /* ── Status line ── */
        .status-line {
            font-size: 13px;
            letter-spacing: .08em;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        .status-line.success { color: #166534; }
        .status-line.error   { color: #991b1b; }

        /* ── Body text ── */
        .para {
            font-size: 16px;
            line-height: 1.8;
            color: #1a1a1a;
            margin-bottom: 20px;
        }

        /* ── Detail block ── */
        .detail-block {
            padding-left: 28px;
            margin-bottom: 24px;
        }

        .detail-row {
            font-size: 16px;
            line-height: 1.9;
            display: flex;
            gap: 0;
        }

        .detail-label {
            display: inline-block;
            width: 80px;
            color: #555;
            flex-shrink: 0;
        }

        .detail-value {
            color: #1a1a1a;
        }

        .detail-value.present { color: #166534; }
        .detail-value.failed  { color: #991b1b; }

        /* ── Verification dropdown ── */
        .ver-wrap {
            margin-top: 30px;
            border-top: 1px solid #000;
            padding-top: 20px;
        }

        .ver-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            font-family: Georgia, 'Times New Roman', Times, serif;
            font-size: 13px;
            color: #1a1a1a;
            letter-spacing: .04em;
        }

        .ver-toggle:hover .ver-label { text-decoration: underline; }

        .danger-dot {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #b91c1c;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-family: Georgia, serif;
            line-height: 1;
        }

        .ver-label {
            flex: 1;
            text-align: left;
        }

        .ver-chevron {
            font-size: 10px;
            color: #555;
            transition: transform .2s ease;
        }

        .ver-toggle.open .ver-chevron { transform: rotate(180deg); }

        .ver-body {
            display: none;
            padding: 18px 0 0 28px;
        }

        .ver-body.open { display: block; }

        .ver-body .para { margin-bottom: 14px; }
        .ver-body .para:last-child { margin-bottom: 0; }

        /* ── Footer ── */
        .footer {
            margin-top: 30px;
            border-top: 1px solid #000;
            padding-top: 14px;
            font-size: 13px;
            font-style: italic;
            color: #555;
        }

        @media (max-width: 600px) {
            body { padding: 32px 20px; }
        }
    </style>
</head>

<body>

<div class="wrap">

    {{-- Letterhead --}}
    <p class="lh-unit">Guidance &amp; Counseling Unit</p>
    <hr class="rule">

    {{-- Status --}}
    @if ($success)
        <p class="status-line success">✓ &nbsp;Check-in Successful</p>
    @else
        <p class="status-line error">⚠ &nbsp;Check-in Failed</p>
    @endif

    {{-- Message --}}
    <p class="para">{{ $message }}</p>

    {{-- Session details --}}
    <div class="detail-block">
        <div class="detail-row">
            <span class="detail-label">Student</span>
            <span class="detail-value">{{ $appointment->student_name }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Managed by</span>
            <span class="detail-value">{{ $appointment->managedBy?->full_name ?? 'Unassigned' }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Date</span>
            <span class="detail-value">{{ $appointment->display_date }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Time</span>
            <span class="detail-value">{{ $appointment->display_time }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Status</span>
            <span class="detail-value {{ $success ? 'present' : 'failed' }}">
                @if ($success) Present @else Failed @endif
            </span>
        </div>
    </div>

    {{-- Verification dropdown --}}
    <div class="ver-wrap">

        <button
            class="ver-toggle"
            id="ver-toggle"
            type="button"
            onclick="toggleVerification()"
            aria-expanded="false"
            aria-controls="ver-body"
        >
            <span class="danger-dot" aria-hidden="true">!</span>
            <span class="ver-label">Verification Details — Administrator Log</span>
            <span class="ver-chevron" aria-hidden="true">▼</span>
        </button>

        <div class="ver-body" id="ver-body" role="region">

            <p class="para">
                Attendance verification for this appointment has been completed successfully.
            </p>

            <p class="para">
                This check-in has been recorded under the supervision of
                <strong>{{ $appointment->managedBy?->full_name ?? 'the assigned administrator' }}</strong>,
                the administrator responsible for conducting and managing this session.
            </p>

            <p class="para">
                All verification events are logged and maintained as part of the official
                attendance records of the Counseling Appointment Management System.
            </p>

        </div>

    </div>

    {{-- Footer --}}
    <div class="footer">
        Counseling Appointment Management System
    </div>

</div>

<script>
    function toggleVerification() {
        const btn  = document.getElementById('ver-toggle');
        const body = document.getElementById('ver-body');
        const open = btn.classList.contains('open');
        btn.classList.toggle('open', !open);
        body.classList.toggle('open', !open);
        btn.setAttribute('aria-expanded', String(!open));
    }
</script>

</body>
</html>