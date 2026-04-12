@extends('admin.dashboard')

@section('admin_content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2 mb-0">Admin Dashboard</h1>
    <small class="text-muted">Prehlad operativy na najblizsie dni</small>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Objednavky dnes</div>
                <div class="display-6 fw-semibold">{{ $ordersToday }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Objednavky (dnes + 2 dni)</div>
                <div class="display-6 fw-semibold">{{ $ordersNextThreeDays }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Polozky v menu (3 dni)</div>
                <div class="display-6 fw-semibold">{{ $menuItemsNextThreeDays }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Objednavky v burze</div>
                <div class="display-6 fw-semibold">{{ $inExchangeCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <div class="text-muted small">Stripe webhook</div>
                        <div class="fw-semibold">
                            @if($stripeWebhookConfigured)
                                <span class="badge bg-success">Konfigurovaný</span>
                            @else
                                <span class="badge bg-danger">Nekonfigurovaný</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="small text-muted">Posledné prijatie</div>
                        <div class="fw-semibold">
                            @if(!empty($stripeWebhookLastReceivedAt))
                                {{ \Carbon\Carbon::parse($stripeWebhookLastReceivedAt)->format('d.m.Y H:i:s') }}
                            @else
                                —
                            @endif
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="small text-muted">Posledná udalosť</div>
                        <div class="fw-semibold">{{ $stripeWebhookLastEventType ?: '—' }}</div>
                    </div>
                    <div class="text-end">
                        <div class="small text-muted">Výsledok</div>
                        <div class="fw-semibold">{{ $stripeWebhookLastResult ?: '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Objednavky na najblizsich 7 dni</h2>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Datum</th>
                                <th>Jedalen</th>
                                <th class="text-end">Pocet objednavok</th>
                                <th class="text-end">Suma</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($upcomingByDayAndCanteen as $row)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($row->order_date)->format('d.m.Y') }}</td>
                                    <td>{{ $row->canteen_name }}</td>
                                    <td class="text-end">{{ $row->orders_count }}</td>
                                    <td class="text-end">{{ number_format((float) $row->total_amount, 2, '.', ' ') }} €</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Pre najblizsie dni zatial nie su data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Posledne objednavky</h2>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Student</th>
                                <th>Status</th>
                                <th class="text-end">Cena</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentOrders as $order)
                                <tr>
                                    <td>#{{ $order->id }}</td>
                                    <td>{{ trim(($order->first_name ?? '') . ' ' . ($order->last_name ?? '')) ?: '-' }}</td>
                                    <td>
                                        <span class="badge {{ $order->status === 'collected' ? 'bg-success' : ($order->status === 'cancelled' ? 'bg-danger' : ($order->status === 'in_exchange' ? 'bg-info text-dark' : 'bg-warning text-dark')) }}">
                                            {{ $order->status }}
                                        </span>
                                    </td>
                                    <td class="text-end">{{ number_format((float) ($order->price ?? 0), 2, '.', ' ') }} €</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Zatial ziadne objednavky.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection