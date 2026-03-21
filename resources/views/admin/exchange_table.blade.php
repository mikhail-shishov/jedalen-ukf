@php
$exchangesCollection = $exchanges ?? collect();
@endphp

<div class="table-responsive">
  <table class="table table-striped table-sm">
    <thead>
      <tr>
        <th>ID</th>
        <th>Predávajúci</th>
        <th>Kupujúci</th>
        <th>Jedlo</th>
        <th>Cena</th>
        <th>Dátum výdaja</th>
        <th>Status</th>
        <th>Vytvorené</th>
      </tr>
    </thead>
    <tbody>
      @forelse($exchangesCollection as $exchange)
      <tr>
        <td>{{ $exchange->id }}</td>
        <td>
          {{ trim(($exchange->seller_first ?? '') . ' ' . ($exchange->seller_last ?? '')) ?: ($exchange->seller_login ?? '—') }}
        </td>
        <td>
          @if($exchange->buyer_login)
            {{ trim(($exchange->buyer_first ?? '') . ' ' . ($exchange->buyer_last ?? '')) ?: ($exchange->buyer_login ?? '—') }}
          @else
            <span class="text-muted">—</span>
          @endif
        </td>
        <td>{{ $exchange->meal_name ?? '—' }}</td>
        <td>{{ number_format($exchange->listing_price, 2, ',', ' ') }} €</td>
        <td>{{ \Carbon\Carbon::createFromFormat('Y-m-d', $exchange->meal_date)->format('d.m.Y') }}</td>
        <td>
          <span class="badge {{ $exchange->status === 'sold' ? 'bg-success' : ($exchange->status === 'expired' ? 'bg-danger' : 'bg-info') }}">
            {{ $exchange->status === 'active' ? 'Aktívne' : ($exchange->status === 'sold' ? 'Predané' : 'Vypršalo') }}
          </span>
        </td>
        <td>{{ \Carbon\Carbon::parse($exchange->order_created_at)->format('d.m.Y H:i') }}</td>
      </tr>
      @empty
      <tr>
        <td colspan="8" class="text-center">Zatiaľ žiadne položky na burži.</td>
      </tr>
      @endforelse
    </tbody>
  </table>
</div>

@if($exchangesCollection->count() > 0)
  <div class="d-flex justify-content-center mt-3">
    {{ $exchanges->links('pagination::bootstrap-4') }}
  </div>
@endif
