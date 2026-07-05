@php
    use App\Exceptions\InfrastructureException;

    $infra = isset($exception) && $exception instanceof InfrastructureException ? $exception : null;
    $retryAfter = $infra?->retryAfter()
        ?? (isset($headers['Retry-After']) ? (int) $headers['Retry-After'] : null)
        ?? 60;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="color-scheme" content="light dark">
    <meta http-equiv="refresh" content="{{ $retryAfter }}">
    <title>Too many requests</title>
    <style>
        html { color-scheme: light dark; }
        :root {
            --art: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='88' height='94' viewBox='0 0 44 47' fill='%23dadce0'><path d='M28 2h8v6h-8zM28 8h8v6h-8zM26 8h2v6h-2zM36 8h2v4h-2zM32 12h2v2h-2zM14 14h14v6H14zM8 20h20v6H8zM4 20h4v14H4zM8 26h24v6H8zM10 32h6v10h-6zM24 32h6v10h-6zM8 32h2v4H8zM30 26h8v2h-8zM2 24h2v6H2z'/></svg>");
        }
        body {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #202124; background-color: #fff;
            margin: 0; min-height: 100vh;
            display: flex; align-items: center; justify-content: center; text-align: center;
        }
        .wrap { max-width: 960px; width: 100%; padding: 2rem clamp(1.5rem, 6vw, 5rem); }
        .status { font-size: clamp(3.5rem, 12vw, 5.5rem); font-weight: 700; line-height: 1; letter-spacing: -.02em; color: #202124; margin: 0 0 1.5rem; }
        .art { width: 120px; height: 128px; margin: 0 auto 1.75rem; background: var(--art) no-repeat center / contain; }
        h1 { font-size: 2.3rem; font-weight: 500; line-height: 1.25; margin: 0 0 1.1rem; }
        p { font-size: 1.25rem; line-height: 1.65; color: #5f6368; margin: 0 auto 1rem; max-width: 70ch; }
        p b, p strong { color: #202124; font-weight: 700; }
        a.btn { display: inline-block; margin-top: 1.75rem; color: #1a73e8; text-decoration: none; font-weight: 600; font-size: 1.1rem; }
        a.btn:hover { text-decoration: underline; }
        @media (prefers-color-scheme: dark) {
            body { color: #e8eaed; background-color: #202124; }
            :root { --art: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='88' height='94' viewBox='0 0 44 47' fill='%235f6368'><path d='M28 2h8v6h-8zM28 8h8v6h-8zM26 8h2v6h-2zM36 8h2v4h-2zM32 12h2v2h-2zM14 14h14v6H14zM8 20h20v6H8zM4 20h4v14H4zM8 26h24v6H8zM10 32h6v10h-6zM24 32h6v10h-6zM8 32h2v4H8zM30 26h8v2h-8zM2 24h2v6H2z'/></svg>"); }
            p { color: #9aa0a6; }
            .status { color: #e8eaed; }
            p b, p strong { color: #e8eaed; }
            a.btn { color: #8ab4f8; }
        }
    </style>
</head>

<body>
    <main class="wrap" role="alert">
        <div class="status">Error 429</div>
        <div class="art" aria-hidden="true"></div>
        <h1>You are going a little too fast</h1>
        <p>{{ $infra?->getMessage() ?: 'You have sent too many requests in a short time so we slowed things down for a moment to protect the service' }}</p>
        <p>No action is needed The limit resets on its own and this page will <b>refresh automatically in <span id="timer">{{ $retryAfter }}</span> seconds</b></p>
        <a class="btn" href="#" data-go="{{ url('/') }}">Back to dashboard</a>
    </main>

    <script nonce="{{ Vite::cspNonce() }}">
        function go(url) {
            try { window.top.location.href = url; }
            catch (e) { window.location.href = url; }
        }
        document.querySelectorAll('[data-go]').forEach(function (el) {
            el.addEventListener('click', function (e) { e.preventDefault(); go(el.getAttribute('data-go')); });
        });
        (function () {
            var s = {{ (int) $retryAfter }};
            var t = document.getElementById('timer');
            setInterval(function () { s -= 1; if (s >= 0 && t) t.textContent = s; }, 1000);
        })();
    </script>
</body>

</html>
