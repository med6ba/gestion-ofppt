<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { margin: 0; background: #f3f4f6; font-family: "DejaVu Sans", sans-serif; color: #17212b; }
        .page { padding: 42px; }
        .badge { width: 760px; height: 430px; margin: 40px auto 0; border: 1px solid #d8dee8; border-radius: 18px; background: #fff; overflow: hidden; }
        .left { float: left; width: 230px; height: 430px; background: #005b9f; color: white; padding: 24px; box-sizing: border-box; }
        .green { height: 92px; margin: 54px -24px -24px; background: #009245; }
        .logo { width: 62px; height: 62px; background: white; border-radius: 8px; padding: 4px; object-fit: contain; }
        .photo { margin-top: 54px; height: 150px; border: 1px solid rgba(255,255,255,.45); border-radius: 14px; text-align: center; line-height: 150px; font-size: 13px; font-weight: bold; letter-spacing: .5px; background: rgba(255,255,255,.14); }
        .brand { margin-top: 24px; font-size: 11px; font-weight: bold; text-transform: uppercase; opacity: .85; }
        .main { margin-left: 230px; padding: 34px; }
        .eyebrow { color: #005b9f; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        h1 { margin: 8px 0 22px; font-size: 30px; line-height: 1.15; }
        .info { width: 300px; float: left; }
        .field { margin-bottom: 15px; }
        .label { color: #64748b; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .value { margin-top: 3px; font-size: 15px; font-weight: bold; }
        .mono { font-family: "DejaVu Sans Mono", monospace; font-size: 12px; }
        .qr { float: right; width: 205px; text-align: center; border: 1px solid #d8dee8; border-radius: 14px; background: #f8fafc; padding: 13px; }
        .qr img { width: 178px; height: 178px; }
        .qr-text { margin-top: 8px; color: #64748b; font-size: 10px; font-weight: bold; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="page">
        <div class="badge">
            <div class="left">
                @if ($logoDataUri)
                    <img class="logo" src="{{ $logoDataUri }}" alt="OFPPT logo">
                @endif
                <div class="photo">{{ __('messages.badge.photo') }}</div>
                <div class="brand">Smart Campus OFPPT</div>
                <div class="green"></div>
            </div>

            <div class="main">
                <div class="eyebrow">{{ __('messages.badge.heading') }}</div>
                <h1>{{ $stagiaire->name }}</h1>

                <div class="info">
                    <div class="field">
                        <div class="label">{{ __('messages.common.filiere') }}</div>
                        <div class="value">{{ $stagiaire->filiereName() }}</div>
                    </div>
                    <div class="field">
                        <div class="label">{{ __('messages.common.group') }}</div>
                        <div class="value">{{ $stagiaire->group?->code ?? __('messages.common.no_group') }}</div>
                    </div>
                    <div class="field">
                        <div class="label">{{ __('messages.badge.badge_id') }}</div>
                        <div class="value mono">{{ $stagiaire->badge_id }}</div>
                    </div>
                </div>

                <div class="qr">
                    <img src="{{ $qrDataUri }}" alt="QR login code">
                    <div class="qr-text">{{ __('messages.badge.secure_qr') }}</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
