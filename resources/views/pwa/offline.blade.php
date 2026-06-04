<!DOCTYPE html>
<html lang="fr" dir="ltr" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('messages.pwa.offline_title') ?? 'Vous êtes hors ligne' }}</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f8fafc; color: #334155; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; margin: 0; text-align: center; padding: 20px; }
        .dark body { background: #0f172a; color: #f8fafc; }
        svg { width: 80px; height: 80px; color: #94a3b8; margin-bottom: 20px; }
        .dark svg { color: #475569; }
        h1 { font-size: 24px; font-weight: 800; margin-bottom: 10px; }
        p { font-size: 16px; margin-bottom: 30px; color: #64748b; }
        .dark p { color: #94a3b8; }
        button { background: #005b9f; color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: bold; font-size: 16px; cursor: pointer; }
    </style>
    <script>
        (function(){
            const t = localStorage.getItem('smartCampus.theme');
            if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</head>
<body>
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.163a1.5 1.5 0 013.113-.73" />
    </svg>
    <h1>{{ __('messages.pwa.offline_title') ?? 'Vous êtes hors ligne' }}</h1>
    <p>{{ __('messages.pwa.offline_text') ?? 'Vérifiez votre connexion internet et réessayez.' }}</p>
    <button onclick="window.location.reload()">{{ __('messages.pwa.retry') ?? 'Réessayer' }}</button>
</body>
</html>
