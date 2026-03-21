@php
$ordersCollection = $orders ?? collect();
@endphp

<div class="table-responsive">
  <table class="table table-striped table-sm">
    <thead>
      <tr>
        <th>ID</th>
        <th>Študent</th>
        <th>Jedlo</th>
        <th>Cena</th>
        <th>Status</th>
        <th>Dátum</th>
      </tr>
    </thead>
    <tbody>
      @forelse($ordersCollection as $order)
      <tr>
        <td>{{ $order->id }}</td>
        <td>{{ trim(($order->user?->first_name ?? '') . ' ' . ($order->user?->last_name ?? '')) ?: ($order->user?->login_id ?? '—') }}</td>
        <td>{{ $order->meal?->name_sk ?? $order->meal?->raw_name ?? '—' }}</td>
        <td>{{ $order->price ?? $order->price_paid ?? '0.00' }} €</td>
        <td>
          <span class="badge {{ $order->status === 'collected' ? 'bg-success' : ($order->status === 'cancelled' ? 'bg-danger' : 'bg-warning') }}">
            {{ $order->status }}
          </span>
        </td>
        <td>{{ $order->created_at->format('d.m.Y H:i') }}</td>
      </tr>
      @empty
      <tr>
        <td colspan="6" class="text-center">Zatiaľ žiadne objednávky.</td>
      </tr>
      @endforelse
    </tbody>
  </table>
</div>

@if($ordersCollection->count() > 0)
  <div class="d-flex justify-content-center mt-3">
    {{ $orders->links('pagination::bootstrap-4') }}
  </div>
@endif
