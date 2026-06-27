@php
    use App\Exceptions\InfrastructureException;

    $infra = isset($exception) && $exception instanceof InfrastructureException ? $exception : null;
    $code = $infra?->errorCode ?? 'RATE_LIMIT_EXCEEDED';
    $status = 429;
    $retryAfter = $infra?->retryAfter()
        ?? (isset($headers['Retry-After']) ? (int) $headers['Retry-After'] : null)
        ?? 60;
    $reference = strtoupper(substr(md5($code . microtime()), 0, 8));
    $when = now()->format('M j, Y · H:i:s T');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta http-equiv="refresh" content="{{ $retryAfter }}">
    <title>429 · Too many requests</title>
    <style>
        :root {
            --brand: #6c9140;
            --brand-dark: #587836;
            --ink: #1f2937;
            --ink-soft: #6b7280;
            --ink-faint: #9ca3af;
            --canvas: #f8f9f7;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            height: 100%;
        }

        body {
            font-family: 'Figtree', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            background: var(--canvas);
            color: var(--ink);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1.25rem;
            text-align: center;
            -webkit-font-smoothing: antialiased;
        }

        .wrap {
            width: 100%;
            max-width: 34rem;
        }

        .code {
            font-size: clamp(5rem, 18vw, 8rem);
            font-weight: 300;
            line-height: 1;
            letter-spacing: -.02em;
            color: var(--ink);
        }

        h1 {
            margin: .5rem 0 0;
            font-size: clamp(1.5rem, 5vw, 2rem);
            font-weight: 400;
            color: var(--ink);
        }

        .summary {
            margin: 1rem auto 0;
            max-width: 30rem;
            font-size: 1rem;
            line-height: 1.6;
            color: var(--ink);
        }

        .detail {
            margin: .75rem auto 0;
            max-width: 30rem;
            font-size: .9rem;
            line-height: 1.65;
            color: var(--ink-soft);
        }

        .countdown {
            display: inline-flex;
            align-items: baseline;
            gap: .35rem;
            margin-top: 1.5rem;
            padding: .5rem 1rem;
            border-radius: .5rem;
            background: rgba(108, 145, 64, .08);
            border: 1px solid rgba(108, 145, 64, .2);
            font-size: .875rem;
            color: var(--ink-soft);
        }

        .countdown strong {
            font-variant-numeric: tabular-nums;
            font-weight: 600;
            color: var(--brand);
        }

        .actions {
            margin-top: 2rem;
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
            justify-content: center;
        }

        .btn {
            display: inline-block;
            padding: .8rem 1.5rem;
            border-radius: .5rem;
            font-family: inherit;
            font-size: .8rem;
            font-weight: 600;
            letter-spacing: .06em;
            text-transform: uppercase;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            transition: background-color .15s ease, border-color .15s ease;
        }

        .btn:disabled {
            opacity: .4;
            cursor: not-allowed;
        }

        .btn-primary {
            background: var(--brand);
            color: #fff;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .1), 0 1px 3px rgba(0, 0, 0, .08);
        }

        .btn-primary:hover:not(:disabled) {
            background: var(--brand-dark);
        }

        .btn-secondary {
            background: transparent;
            color: var(--ink-soft);
            border-color: #d6dad0;
        }

        .btn-secondary:hover {
            background: #eef0ec;
        }

        .meta {
            margin-top: 2.25rem;
            font-size: .72rem;
            color: var(--ink-faint);
        }

        .meta code {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            color: var(--ink-soft);
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --ink: #f3f4f1;
                --ink-soft: #9ca3af;
                --ink-faint: #6b7280;
                --canvas: #0d110c;
            }

            .countdown {
                background: rgba(108, 145, 64, .12);
                border-color: rgba(108, 145, 64, .25);
            }

            .btn-secondary {
                border-color: #3a453a;
            }

            .btn-secondary:hover {
                background: #1d231c;
            }
        }
    </style>
</head>

<body>
    <main class="wrap" role="alert" aria-live="assertive">
        <div class="code">429</div>
        <h1>Too many requests</h1>
        <p class="summary">
            {{ $infra?->getMessage() ?: "You've sent too many requests in a short period." }}
        </p>
        <p class="detail">
            This page is rate-limited to once per minute per visitor. The limit resets
            automatically — no action is required. The page will refresh on its own
            when you're able to try again.
        </p>

        <p class="countdown" role="status" aria-live="polite">
            Refreshing in <strong id="timer">{{ $retryAfter }}</strong> second{{ $retryAfter === 1 ? '' : 's' }}
        </p>

        <div class="actions">
            <button type="button" class="btn btn-primary" id="retry-btn" disabled data-go="{{ url()->current() }}">
                Try again
            </button>
            <button type="button" class="btn btn-secondary" data-go="{{ url('/') }}">
                Go to homepage
            </button>
        </div>

        <p class="meta">
            Error 429 · {{ $code }} · Reference <code>{{ $reference }}</code> · {{ $when }}
        </p>
    </main>

    <script nonce="{{ Vite::cspNonce() }}">
        function go(url) {
            try { window.top.location.href = url; }
            catch (e) { window.location.href = url; }
        }

        document.querySelectorAll('[data-go]').forEach(function (el) {
            el.addEventListener('click', function () {
                if (!el.disabled) go(el.getAttribute('data-go'));
            });
        });

        (function () {
            var seconds = {{ (int) $retryAfter }};
            var timerEl = document.getElementById('timer');
            var retryBtn = document.getElementById('retry-btn');
            var target = window.location.href;

            var interval = setInterval(function () {
                seconds -= 1;

                if (seconds <= 0) {
                    clearInterval(interval);
                    go(target);
                } else {
                    if (timerEl) timerEl.textContent = seconds;
                }
            }, 1000);

            // Enable the manual button a moment before the auto-redirect
            // so the user can act if they want to.
            setTimeout(function () {
                if (retryBtn) retryBtn.disabled = false;
            }, Math.max(0, ({{ (int) $retryAfter }} - 3) * 1000));
        })();
    </script>
</body>

</html>