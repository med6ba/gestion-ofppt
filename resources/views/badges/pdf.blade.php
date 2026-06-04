<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        @page { margin: 22px; }
        body {
            margin: 0;
            background: #eef2f6;
            font-family: "DejaVu Sans", sans-serif;
            color: #0f172a;
        }
        .sheet {
            width: 100%;
            padding-top: 12px;
        }
        .badge {
            width: 860px;
            height: 510px;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
            border: 1px solid #d6dde8;
            border-radius: 26px;
            background: #ffffff;
        }
        .top-band {
            height: 74px;
            background: #0f172a;
            color: #ffffff;
            padding: 16px 28px;
            position: relative;
        }
        .brand-block { float: left; width: 420px; }
        .logo {
            float: left;
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: #ffffff;
            padding: 5px;
            object-fit: contain;
        }
        .brand-text { margin-left: 66px; padding-top: 5px; }
        .brand-title { font-size: 19px; font-weight: 900; letter-spacing: .2px; }
        .brand-subtitle { margin-top: 2px; color: #cbd5e1; font-size: 10px; font-weight: 900; text-transform: uppercase; }
        .issued {
            float: right;
            margin-top: 4px;
            text-align: right;
            font-size: 10px;
            font-weight: 900;
            color: #cbd5e1;
        }
        .issued strong {
            display: block;
            margin-top: 4px;
            color: #ffffff;
            font-size: 13px;
        }
        .color-strip { height: 9px; font-size: 0; }
        .color-strip span { display: inline-block; height: 9px; width: 33.333%; }
        .green { background: #009245; }
        .grey { background: #8a939f; }
        .blue { background: #005b9f; }
        .body { position: relative; padding: 28px 30px 22px; }
        .watermark {
            position: absolute;
            right: 28px;
            top: 16px;
            color: #f1f5f9;
            font-size: 72px;
            font-weight: 900;
            letter-spacing: 4px;
            z-index: 0;
        }
        .columns { position: relative; z-index: 1; width: 100%; border-collapse: collapse; }
        .identity { width: 560px; vertical-align: top; padding-right: 26px; }
        .qr-zone { width: 240px; vertical-align: top; }
        .pill {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 999px;
            background: #e8f6ef;
            color: #007a3d;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }
        h1 {
            margin: 14px 0 12px;
            font-size: 34px;
            line-height: 1.05;
            color: #0f172a;
        }
        .meta {
            margin-bottom: 18px;
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
        }
        .info-grid { width: 100%; border-collapse: collapse; }
        .info-grid td { width: 50%; padding: 0 10px 10px 0; vertical-align: top; }
        .field {
            min-height: 64px;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #f8fafc;
            padding: 11px 12px;
        }
        .label {
            color: #64748b;
            font-size: 9px;
            font-weight: 900;
            text-transform: uppercase;
        }
        .value {
            margin-top: 4px;
            color: #111827;
            font-size: 14px;
            font-weight: 900;
            word-break: break-word;
        }
        .badge-id {
            margin-top: 10px;
            border: 1px solid #d8e8f5;
            border-radius: 14px;
            background: #eef6ff;
            padding: 12px;
        }
        .mono {
            margin-top: 4px;
            color: #005b9f;
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 11px;
            font-weight: 900;
            word-break: break-all;
        }
        .qr-card {
            border: 1px solid #d8dee8;
            border-radius: 22px;
            background: #ffffff;
            padding: 14px;
            text-align: center;
        }
        .qr-card img {
            width: 192px;
            height: 192px;
            padding: 8px;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: #ffffff;
        }
        .qr-title {
            margin-top: 10px;
            color: #0f172a;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
        }
        .qr-help {
            margin-top: 5px;
            color: #64748b;
            font-size: 9px;
            line-height: 1.4;
            font-weight: 700;
        }
        .email {
            margin-top: 10px;
            border-radius: 12px;
            background: #f8fafc;
            padding: 8px;
            color: #475569;
            font-size: 9px;
            font-weight: 800;
            word-break: break-all;
        }
        .footer {
            position: absolute;
            left: 30px;
            right: 30px;
            bottom: 18px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            color: #64748b;
            font-size: 9px;
            font-weight: 800;
        }
        .footer strong { color: #009245; }
        html[dir="rtl"] .brand-block,
        html[dir="rtl"] .logo { float: right; }
        html[dir="rtl"] .brand-text { margin-left: 0; margin-right: 66px; }
        html[dir="rtl"] .issued { float: left; text-align: left; }
        html[dir="rtl"] .identity { padding-right: 0; padding-left: 26px; }
        html[dir="rtl"] .watermark { right: auto; left: 28px; }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="badge">
            <div class="top-band">
                <div class="brand-block">
                    @if ($logoDataUri)
                        <img class="logo" src="{{ $logoDataUri }}" alt="OFPPT logo">
                    @endif
                    <div class="brand-text">
                        <div class="brand-title">Smart Campus OFPPT</div>
                        <div class="brand-subtitle">{{ __('messages.badge.digital_identity') }}</div>
                    </div>
                </div>
                <div class="issued">
                    {{ __('messages.badge.issued_at') }}
                    <strong>{{ now()->format('d/m/Y') }}</strong>
                </div>
            </div>
            <div class="color-strip"><span class="green"></span><span class="grey"></span><span class="blue"></span></div>

            <div class="body">
                <div class="watermark">OFPPT</div>
                <table class="columns">
                    <tr>
                        <td class="identity">
                            <span class="pill">{{ __('messages.badge.heading') }}</span>
                            <h1>{{ $stagiaire->name }}</h1>
                            <div class="meta">{{ __('messages.badge.subtitle') }}</div>

                            <table class="info-grid">
                                <tr>
                                    <td>
                                        <div class="field">
                                            <div class="label">{{ __('messages.common.filiere') }}</div>
                                            <div class="value">{{ $stagiaire->filiereName() }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="field">
                                            <div class="label">{{ __('messages.common.group') }}</div>
                                            <div class="value">{{ $stagiaire->group?->code ?? __('messages.common.no_group') }}</div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="field">
                                            <div class="label">{{ __('messages.common.matricule') }}</div>
                                            <div class="value">{{ $stagiaire->registration_number ?? __('messages.common.not_provided') }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="field">
                                            <div class="label">{{ __('messages.common.cni') }}</div>
                                            <div class="value">{{ $stagiaire->cni ?? __('messages.common.not_provided') }}</div>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <div class="badge-id">
                                <div class="label">{{ __('messages.badge.badge_id') }}</div>
                                <div class="mono">{{ $stagiaire->badge_id }}</div>
                            </div>
                        </td>
                        <td class="qr-zone">
                            <div class="qr-card">
                                <img src="{{ $qrDataUri }}" alt="QR login code">
                                <div class="qr-title">{{ __('messages.badge.secure_qr') }}</div>
                                <div class="qr-help">{{ __('messages.badge.login_hint') }}</div>
                                <div class="email">{{ $stagiaire->email }}</div>
                            </div>
                        </td>
                    </tr>
                </table>

                <div class="footer">
                    <strong>Smart Campus OFPPT</strong> | {{ __('messages.badge.property_notice') }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>
