<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: "DejaVu Sans", sans-serif; color: #17212b; margin: 0; }
        .page { padding: 58px 64px; }
        .header { border-bottom: 4px solid #009245; padding-bottom: 20px; }
        .logo { width: 74px; height: 74px; object-fit: contain; float: left; margin-right: 18px; }
        .brand { color: #005b9f; font-size: 22px; font-weight: bold; padding-top: 8px; }
        .sub { color: #64748b; font-size: 12px; margin-top: 4px; }
        h1 { margin-top: 76px; text-align: center; font-size: 28px; text-transform: uppercase; }
        .body { margin-top: 48px; font-size: 16px; line-height: 1.9; }
        .field { font-weight: bold; color: #005b9f; }
        .meta { margin-top: 42px; border: 1px solid #d8dee8; border-radius: 12px; padding: 18px; font-size: 13px; color: #475569; }
        .signature { margin-top: 72px; text-align: right; font-size: 14px; }
        .stamp { display: inline-block; min-width: 220px; border-top: 1px solid #94a3b8; padding-top: 10px; text-align: center; }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            @if ($logoDataUri)
                <img class="logo" src="{{ $logoDataUri }}" alt="OFPPT logo">
            @endif
            <div class="brand">{{ __('messages.brand') }}</div>
            <div class="sub">{{ __('messages.attestations.manage_heading') }}</div>
        </div>

        <h1>{{ __('messages.attestations.document_title') }}</h1>

        <div class="body">
            <p>
                {{ __('messages.attestations.document_title') }}:
                <span class="field">{{ $attestation->full_name }}</span>,
                {{ __('messages.common.cni') }} <span class="field">{{ $attestation->cni }}</span>,
                {{ __('messages.common.filiere') }} <span class="field">{{ $attestation->filiere ?? __('messages.common.not_provided') }}</span>.
            </p>
            <p>
                La presente attestation est delivree a l'interesse(e) pour servir et valoir ce que de droit.
            </p>
        </div>

        <div class="meta">
            {{ __('messages.dashboard.request') }}: {{ $attestation->id }}<br>
            {{ __('messages.common.created_at') }}: {{ $attestation->created_at->format('Y-m-d') }}<br>
            {{ __('messages.status.approved') }}: {{ $attestation->reviewed_at?->format('Y-m-d') ?? now()->format('Y-m-d') }}<br>
            {{ __('messages.common.approve') }}: {{ $attestation->reviewer?->name ?? 'Administration' }}
        </div>

        <div class="signature">
            <div class="stamp">Signature et cachet</div>
        </div>
    </div>
</body>
</html>
