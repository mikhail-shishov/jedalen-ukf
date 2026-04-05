@extends('admin.dashboard')

@section('admin_content')
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2>Platobné údaje</h2>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('admin.payments-settings.update') }}" method="POST" class="card border-0 shadow-sm">
        @csrf
        @method('PUT')

        @php
            $formatSettingUpdatedAt = function (?string $value): string {
                if (!$value) {
                    return 'Naposledy upravené: bez záznamu';
                }

                try {
                    return 'Naposledy upravené: ' . \Illuminate\Support\Carbon::parse($value)->format('d.m.Y H:i');
                } catch (\Throwable $e) {
                    return 'Naposledy upravené: ' . $value;
                }
            };
        @endphp

        <div class="card-body">
            <p class="text-muted mb-4">
                Tieto údaje sa zobrazujú na stránke platieb vo frontend aplikácii.
            </p>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="client_name" class="form-label">Klient</label>
                    <input
                        type="text"
                        id="client_name"
                        name="client_name"
                        class="form-control @error('client_name') is-invalid @enderror"
                        value="{{ old('client_name', $bankDetails['client_name']) }}"
                        required
                    >
                    @error('client_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text text-muted small">
                        {{ $formatSettingUpdatedAt($bankDetailsMeta['client_name']['updated_at'] ?? null) }}
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="account_name" class="form-label">Názov účtu</label>
                    <input
                        type="text"
                        id="account_name"
                        name="account_name"
                        class="form-control @error('account_name') is-invalid @enderror"
                        value="{{ old('account_name', $bankDetails['account_name']) }}"
                        required
                    >
                    @error('account_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text text-muted small">
                        {{ $formatSettingUpdatedAt($bankDetailsMeta['account_name']['updated_at'] ?? null) }}
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="account_number" class="form-label">Číslo účtu</label>
                    <input
                        type="text"
                        id="account_number"
                        name="account_number"
                        class="form-control @error('account_number') is-invalid @enderror"
                        value="{{ old('account_number', $bankDetails['account_number']) }}"
                        required
                    >
                    @error('account_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text text-muted small">
                        {{ $formatSettingUpdatedAt($bankDetailsMeta['account_number']['updated_at'] ?? null) }}
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="iban" class="form-label">IBAN</label>
                    <input
                        type="text"
                        id="iban"
                        name="iban"
                        class="form-control @error('iban') is-invalid @enderror"
                        value="{{ old('iban', $bankDetails['iban']) }}"
                        required
                    >
                    @error('iban')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text text-muted small">
                        {{ $formatSettingUpdatedAt($bankDetailsMeta['iban']['updated_at'] ?? null) }}
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="bank_name" class="form-label">Názov banky</label>
                    <input
                        type="text"
                        id="bank_name"
                        name="bank_name"
                        class="form-control @error('bank_name') is-invalid @enderror"
                        value="{{ old('bank_name', $bankDetails['bank_name']) }}"
                        required
                    >
                    @error('bank_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text text-muted small">
                        {{ $formatSettingUpdatedAt($bankDetailsMeta['bank_name']['updated_at'] ?? null) }}
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="refund_email" class="form-label">E-mail pre vrátenie kreditu</label>
                    <input
                        type="email"
                        id="refund_email"
                        name="refund_email"
                        class="form-control @error('refund_email') is-invalid @enderror"
                        value="{{ old('refund_email', $bankDetails['refund_email']) }}"
                        required
                    >
                    @error('refund_email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text text-muted small">
                        {{ $formatSettingUpdatedAt($bankDetailsMeta['refund_email']['updated_at'] ?? null) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer bg-white border-0 pb-4">
            <button type="submit" class="btn btn-primary">Uložiť zmeny</button>
        </div>
    </form>
@endsection
