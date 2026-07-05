{{--
    Shared error layout. Minimal Chrome style, centered, plain and descriptive.
    Self contained so it renders even when the database or compiled assets are
    unavailable; all styling and the background art are inline.

    Child views set these variables in a php block:
      status  - HTTP status
      heading - the headline
      body    - one descriptive paragraph (may contain b and u tags)
      more    - optional second paragraph
      retry   - whether to show the try again button (defaults true)
--}}
@php
    $status  ??= (isset($exception) && $exception->getCode() ? $exception->getCode() : 500);
    $heading ??= 'Something went wrong';
    $body    ??= 'An unexpected problem stopped this page from loading';
    $more    ??= null;
    $retry   ??= true;
    $code    ??= null; // machine code shown to help the maintainer diagnose
    $when      = now()->format('M j Y g:i A');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="color-scheme" content="light dark">
    <title>{{ $heading }}</title>
    <style>
        html { color-scheme: light dark; }

        body {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #202124;
            background-color: #fff;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        :root {
            --art: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='88' height='94' viewBox='0 0 44 47' fill='%23dadce0'><path d='M28 2h8v6h-8zM28 8h8v6h-8zM26 8h2v6h-2zM36 8h2v4h-2zM32 12h2v2h-2zM14 14h14v6H14zM8 20h20v6H8zM4 20h4v14H4zM8 26h24v6H8zM10 32h6v10h-6zM24 32h6v10h-6zM8 32h2v4H8zM30 26h8v2h-8zM2 24h2v6H2z'/></svg>");
        }

        .wrap {
            max-width: 960px;
            width: 100%;
            padding: 2rem clamp(1.5rem, 6vw, 5rem);
        }

        /* Big status code at the very top */
        .status {
            font-size: clamp(3.5rem, 12vw, 5.5rem);
            font-weight: 700;
            line-height: 1;
            letter-spacing: -.02em;
            color: #202124;
            margin: 0 0 1.5rem;
        }

        /* Pixel icon sits below the big status code */
        .art {
            width: 120px;
            height: 128px;
            margin: 0 auto 1.75rem;
            background: var(--art) no-repeat center / contain;
        }

        h1 {
            font-size: 2.3rem;
            font-weight: 500;
            line-height: 1.25;
            margin: 0 0 1.1rem;
        }

        p {
            font-size: 1.25rem;
            line-height: 1.65;
            color: #5f6368;
            margin: 0 auto 1rem;
            max-width: 70ch;
        }

        p b, p strong { color: #202124; font-weight: 700; }
        p u { text-decoration-thickness: 2px; text-underline-offset: 3px; }

        /* Diagnostic line for the maintainer: the exact code + time to report.
           A wide top rule separates it from the message instead of a boxed card. */
        .diag {
            margin: 2.5rem auto 0;
            max-width: 700px;
            padding-top: 1.5rem;
            border-top: 2px solid #dadce0;
            font-size: .95rem;
            line-height: 1.7;
            color: #5f6368;
        }

        .diag code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-weight: 700;
            font-size: 1.05rem;
            color: #202124;
        }

        .actions {
            margin-top: 2rem;
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        a.btn, button.btn {
            display: inline-block;
            color: #1a73e8;
            background: none;
            border: 0;
            padding: 0;
            font: inherit;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        a.btn:hover, button.btn:hover { text-decoration: underline; }

        @media (prefers-color-scheme: dark) {
            body { color: #e8eaed; background-color: #202124; }
            :root {
                --art: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='88' height='94' viewBox='0 0 44 47' fill='%235f6368'><path d='M28 2h8v6h-8zM28 8h8v6h-8zM26 8h2v6h-2zM36 8h2v4h-2zM32 12h2v2h-2zM14 14h14v6H14zM8 20h20v6H8zM4 20h4v14H4zM8 26h24v6H8zM10 32h6v10h-6zM24 32h6v10h-6zM8 32h2v4H8zM30 26h8v2h-8zM2 24h2v6H2z'/></svg>");
            }
            p { color: #9aa0a6; }
            .status { color: #e8eaed; }
            p b, p strong { color: #e8eaed; }
            a.btn, button.btn { color: #8ab4f8; }
            .diag { color: #9aa0a6; background: #2a2d30; border-color: #3c4043; }
            .diag code { color: #e8eaed; }
        }
    </style>
</head>

<body>
    <main class="wrap" role="alert">
        <div class="status">Error {{ $status }}</div>
        <div class="art" aria-hidden="true"></div>
        <h1>{{ $heading }}</h1>
        <p>{!! $body !!}</p>
        @if($more)
            <p>{!! $more !!}</p>
        @endif
        @if($code)
            <div class="diag">
                When reporting this to the system maintainer include this exact code so they can find the cause quickly<br>
                <code>{{ $status }} {{ $code }}</code> at {{ $when }}
            </div>
        @endif
        <div class="actions">
            @if($retry)
                <button type="button" class="btn" data-go="{{ url()->current() }}">Try again</button>
            @endif
            <a class="btn" href="#" data-go="{{ url('/') }}">Back to dashboard</a>
        </div>
    </main>

    <script nonce="{{ Vite::cspNonce() }}">
        function go(url) {
            try { window.top.location.href = url; }
            catch (e) { window.location.href = url; }
        }
        document.querySelectorAll('[data-go]').forEach(function (el) {
            el.addEventListener('click', function (e) { e.preventDefault(); go(el.getAttribute('data-go')); });
        });
    </script>
</body>

</html>
