{{--
Shared error layout for MoodLink FEU Diliman

Self-contained on purpose so it renders even when the database or the compiled
Vite Tailwind assets are unavailable All styling and scripts are inline and must
not depend on the app build pipeline

Child views provide
@section('title')   the headline no punctuation
@section('summary') one short plain line no punctuation
@section('detail')  a longer explanation no punctuation
@section('reasons') optional list items each wrapped in <li> no punctuation
$code      machine code eg DB_UNAVAILABLE optional
$status    HTTP status optional defaults from exception
$retryable whether to show the retry button defaults true
--}}
@php
    $status ??= (isset($exception) && $exception->getCode() ? $exception->getCode() : 500);
    $code ??= null;
    $retryable ??= true;
    $reference = strtoupper(substr(md5(($code ?? 'ERR') . microtime()), 0, 8));
    $when = now()->format('M j Y H:i:s T');

    // Short human name for the status family shown as the small eyebrow label
    $family = match (true) {
        $status >= 500 => 'Server issue',
        $status === 429 => 'Slow down',
        $status === 404 => 'Not found',
        $status === 403 => 'No access',
        $status === 401 => 'Sign in needed',
        $status === 419 => 'Session ended',
        $status === 405 => 'Wrong action',
        default => 'Something went wrong',
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="color-scheme" content="light dark">
    <title>{{ $status }} @yield('title', 'Something went wrong') MoodLink</title>
    <style>
        :root {
            --brand: #6c9140;
            --brand-dark: #587836;
            --brand-tint: rgba(108, 145, 64, .10);
            --brand-line: rgba(108, 145, 64, .22);
            --ink: #14181a;
            --ink-soft: #56605f;
            --ink-faint: #8b938f;
            --canvas: #f6f8f4;
            --card: #ffffff;
            --rule: #e5e9df;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --brand: #8bb15f;
                --brand-dark: #9cc16f;
                --brand-tint: rgba(139, 177, 95, .12);
                --brand-line: rgba(139, 177, 95, .26);
                --ink: #eef2ea;
                --ink-soft: #a7b0a6;
                --ink-faint: #727b72;
                --canvas: #0b0f0b;
                --card: #12160f;
                --rule: #262c22;
            }
        }

        *, *::before, *::after { box-sizing: border-box; }

        html, body { margin: 0; padding: 0; min-height: 100%; }

        body {
            font-family: 'Figtree', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            background:
                radial-gradient(1200px 600px at 50% -10%, var(--brand-tint), transparent 60%),
                var(--canvas);
            color: var(--ink);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(1.5rem, 5vw, 4rem) 1.25rem;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }

        .wrap {
            width: 100%;
            max-width: 46rem;
            text-align: center;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            font-weight: 700;
            font-size: .95rem;
            letter-spacing: .02em;
            color: var(--ink);
        }

        .brand-mark {
            width: 1.6rem;
            height: 1.6rem;
            border-radius: .5rem;
            background: var(--brand);
            color: #fff;
            display: grid;
            place-items: center;
            font-size: .95rem;
            font-weight: 800;
            box-shadow: 0 2px 8px rgba(108, 145, 64, .35);
        }

        .brand-sub { color: var(--ink-faint); font-weight: 600; }

        .eyebrow {
            margin: 2.75rem 0 0;
            display: inline-block;
            padding: .4rem .85rem;
            border-radius: 999px;
            background: var(--brand-tint);
            border: 1px solid var(--brand-line);
            color: var(--brand-dark);
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        .code {
            margin: 1rem 0 0;
            font-size: clamp(6.5rem, 26vw, 13rem);
            font-weight: 800;
            line-height: .9;
            letter-spacing: -.04em;
            background: linear-gradient(180deg, var(--ink), var(--ink-soft));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        h1 {
            margin: .35rem 0 0;
            font-size: clamp(1.6rem, 5.5vw, 2.6rem);
            font-weight: 700;
            letter-spacing: -.01em;
            line-height: 1.15;
        }

        .summary {
            margin: 1.1rem auto 0;
            max-width: 34rem;
            font-size: clamp(1.05rem, 2.6vw, 1.2rem);
            line-height: 1.6;
            color: var(--ink-soft);
        }

        .detail {
            margin: .85rem auto 0;
            max-width: 34rem;
            font-size: 1rem;
            line-height: 1.7;
            color: var(--ink-soft);
        }

        .reasons {
            margin: 1.75rem auto 0;
            max-width: 34rem;
            width: 100%;
            text-align: left;
            background: var(--card);
            border: 1px solid var(--rule);
            border-radius: 1rem;
            padding: 1.15rem 1.35rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .04), 0 10px 30px rgba(20, 24, 26, .05);
        }

        .reasons-title {
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--ink-faint);
            margin-bottom: .65rem;
        }

        .reasons ul { list-style: none; margin: 0; padding: 0; }

        .reasons li {
            position: relative;
            padding: .35rem 0 .35rem 1.6rem;
            font-size: .95rem;
            line-height: 1.55;
            color: var(--ink);
        }

        .reasons li::before {
            content: '';
            position: absolute;
            left: 0;
            top: .95rem;
            width: .5rem;
            height: .5rem;
            border-radius: 50%;
            background: var(--brand);
        }

        .actions {
            margin-top: 2.25rem;
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
            justify-content: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .95rem 1.9rem;
            border-radius: .7rem;
            font-family: inherit;
            font-size: .82rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            transition: transform .12s ease, background-color .15s ease, border-color .15s ease;
        }

        .btn:active { transform: translateY(1px); }

        .btn-primary {
            background: var(--brand);
            color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, .1), 0 8px 20px rgba(108, 145, 64, .28);
        }

        .btn-primary:hover { background: var(--brand-dark); }

        .btn-secondary {
            background: transparent;
            color: var(--ink-soft);
            border-color: var(--rule);
        }

        .btn-secondary:hover { background: var(--brand-tint); color: var(--ink); }

        .meta {
            margin-top: 2.5rem;
            padding-top: 1.4rem;
            border-top: 1px solid var(--rule);
            font-size: .74rem;
            line-height: 1.9;
            color: var(--ink-faint);
        }

        .meta-row {
            display: inline-flex;
            flex-wrap: wrap;
            gap: .4rem .9rem;
            justify-content: center;
        }

        .meta-row span { white-space: nowrap; }

        .meta code {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            color: var(--ink-soft);
            background: var(--brand-tint);
            padding: .1rem .4rem;
            border-radius: .35rem;
        }

        .support { margin-top: .35rem; color: var(--ink-faint); }
        .support a { color: var(--brand-dark); font-weight: 600; text-decoration: none; }
        .support a:hover { text-decoration: underline; }

        @media (max-width: 480px) {
            .btn { width: 100%; justify-content: center; }
        }
    </style>
</head>

<body>
    <main class="wrap" role="alert" aria-live="assertive">
        <div class="brand">
            <span class="brand-mark" aria-hidden="true">M</span>
            MoodLink <span class="brand-sub">FEU Diliman</span>
        </div>

        <span class="eyebrow">{{ $family }}</span>

        <div class="code">{{ $status }}</div>
        <h1>@yield('title', 'Something went wrong')</h1>
        <p class="summary">@yield('summary', 'An unexpected problem stopped this page from loading')</p>

        @hasSection('detail')
            <p class="detail">@yield('detail')</p>
        @endif

        @hasSection('reasons')
            <div class="reasons">
                <div class="reasons-title">What usually causes this</div>
                <ul>@yield('reasons')</ul>
            </div>
        @endif

        {{--
            Buttons drive the top level window so they work whether this renders
            standalone or inside an Inertia error modal iframe where a plain link
            would only navigate the iframe
        --}}
        <div class="actions">
            @if($retryable)
                <button type="button" class="btn btn-primary" data-go="{{ url()->current() }}">Try again</button>
                <button type="button" class="btn btn-secondary" data-go="{{ url('/') }}">Back to dashboard</button>
            @else
                <button type="button" class="btn btn-primary" data-go="{{ url('/') }}">Back to dashboard</button>
            @endif
        </div>

        <div class="meta">
            <div class="meta-row">
                <span>Status {{ $status }}</span>
                @if($code)<span>Code {{ $code }}</span>@endif
                <span>Reference <code>{{ $reference }}</code></span>
                <span>{{ $when }}</span>
            </div>
            <div class="support">
                Share the reference above if you contact the guidance office IT support
            </div>
        </div>
    </main>

    <script nonce="{{ Vite::cspNonce() }}">
        function go(url) {
            try { window.top.location.href = url; }
            catch (e) { window.location.href = url; }
        }
        document.querySelectorAll('[data-go]').forEach(function (el) {
            el.addEventListener('click', function () { go(el.getAttribute('data-go')); });
        });
    </script>
</body>

</html>
