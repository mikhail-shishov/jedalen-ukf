@extends('admin.dashboard')

@section('admin_content')
    <div class="pt-3 pb-2 mb-3 border-bottom">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.users') }}">Používatelia</a></li>
                <li class="breadcrumb-item active">História kreditu: <b>{{ $user->first_name }} {{ $user->last_name }}</b></li>
            </ol>
        </nav>
        <h2>História transakcií používateľa <em>{{ $user->first_name }} {{ $user->last_name }}</em>, ID <em>{{ $user->login_id }}</em></h2>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            Aktuálny stav: <strong>{{ number_format($user->credit_balance, 2) }} €</strong>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Dátum a čas</th>
                            <th>Zmena</th>
                            <th>Predtým</th>
                            <th>Potom</th>
                            <th>Metóda</th>
                            <th>Poznámka / ID transakcie</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td>{{ $payment->created_at->format('d.m.Y H:i:s') }}</td>
                                <td>
                                    <span class="badge {{ $payment->amount >= 0 ? 'bg-success' : 'bg-danger' }}">
                                        {{ $payment->amount >= 0 ? '+' : '' }}{{ number_format($payment->amount, 2) }} €
                                    </span>
                                </td>
                                <td>{{ number_format($payment->balance_before, 2) }} €</td>
                                <td><strong>{{ number_format($payment->balance_after, 2) }} €</strong></td>
                                <td><small class="text-muted">{{ $payment->method->name ?? 'Neznáma' }}</small></td>
                                <td>
                                    {{ $payment->error_message }}
                                    @if($payment->external_transaction_id)
                                        <br>
                                        @if(str_starts_with($payment->external_transaction_id, 'ADMIN_MOD_'))
                                            <i class="bi bi-person-badge"></i> ID administrátora: {{ str_replace('ADMIN_MOD_', '', $payment->external_transaction_id) }}
                                        @else
                                            <i class="bi bi-receipt"></i> ID transakcie: {{ $payment->external_transaction_id }}
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Žiadne finančné pohyby neboli zaznamenané.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection