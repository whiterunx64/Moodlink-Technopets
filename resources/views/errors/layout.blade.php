{{--
Shared error layout for infrastructure failures, shown inside Inertia's
error modal (and as a standalone page in production).

Visual style follows the minimal centered reference: a large light status
code, a title, a descriptive paragraph, and a single action button.

Self-contained on purpose: these pages render precisely when the database
or the compiled Vite/Tailwind assets may be unavailable, so the markup must
not depend on the app's build pipeline. All styling is inline.

Child views provide:
@section('title') the headline
@section('summary') one-sentence plain-language summary
@section('detail') longer explanation paragraph
$code machine code (e.g. DB_UNAVAILABLE) — optional
$status HTTP status — optional, defaults from exception
$retryable whether to show the retry button — defaults true
--}}
@php
    $status ??= (isset($exception) && $exception->getCode() ? $exception->getCode() : 500);
    $code ??= null;
    $retryable ??= true;
    $reference = strtoupper(substr(md5(($code ?? 'ERR') . microtime()), 0, 8));
    $when = now()->format('M j, Y · H:i:s T');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $status }} · @yield('title', 'Something went wrong')</title>
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

        .btn-primary {
            background: var(--brand);
            color: #fff;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .1), 0 1px 3px rgba(0, 0, 0, .08);
        }

        .btn-primary:hover {
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
        <div class="code">{{ $status }}</div>
        <h1>@yield('title', 'Something went wrong')</h1>
        <p class="summary">@yield('summary', 'An unexpected error occurred while processing your request.')</p>
        @hasSection('detail')
            <p class="detail">@yield('detail')</p>
        @endif

        {{--
            These actions can render inside Inertia's modal iframe, where a plain
            <a href> would only navigate the iframe (looking like "nothing
            happens"). Drive the top-level window instead, falling back to the
            current window when not framed.
        --}}
        <div class="actions">
            @if($retryable)
                <button type="button" class="btn btn-primary" data-go="{{ url()->current() }}">Try again</button>
                <button type="button" class="btn btn-secondary" data-go="{{ url('/') }}">Return to dashboard</button>
            @else
                <button type="button" class="btn btn-primary" data-go="{{ url('/') }}">Return to dashboard</button>
            @endif
        </div>

        <p class="meta">
            Error {{ $status }}@if($code) · {{ $code }}@endif · Reference <code>{{ $reference }}</code> · {{ $when }}
        </p>
    </main>

    <script nonce="{{ Vite::cspNonce() }}">
        // Navigate the top-level window so the buttons work whether this page is
        // standalone or embedded in Inertia's error-modal iframe.
        function go(url) {
            try {
                window.top.location.href = url;
            } catch (e) {
                window.location.href = url;
            }
        }

        document.querySelectorAll('[data-go]').forEach(function (el) {
            el.addEventListener('click', function () {
                go(el.getAttribute('data-go'));
            });
        });
    </script>
</body>

</html>