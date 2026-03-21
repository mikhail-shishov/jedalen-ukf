@extends('admin.dashboard')

@section('admin_content')
<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
<h2>Prehľad objednávok</h2>
</div>

<form method="GET" action="{{ route('admin.orders') }}" class="row g-2 mb-3">
  <div class="col-md-6 col-lg-4">
    <input
      type="search"
      name="q"
      class="form-control"
      value="{{ $searchQuery ?? '' }}"
      placeholder="Hľadať podľa ID, používateľa, jedla, stavu...">
  </div>
  <div class="col-auto">
    <button type="submit" class="btn btn-outline-primary">Hľadať</button>
  </div>
  @if(!empty($searchQuery))
    <div class="col-auto">
      <a href="{{ route('admin.orders') }}" class="btn btn-outline-secondary">Vymazať filter</a>
    </div>
  @endif
</form>

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
      @forelse($orders as $order)
      <tr>
        <td>{{ $order->id }}</td>
        <td>{{ trim(($order->user?->first_name ?? '') . ' ' . ($order->user?->last_name ?? '')) ?: ($order->user?->login_id ?? '—') }}</td>
        <td>{{ $order->meal?->name_sk ?? $order->meal?->raw_name ?? '—' }}</td>
        <td>{{ $order->price }} €</td>
        <td>
          <span class="badge {{ $order->status === 'completed' ? 'bg-success' : 'bg-warning' }}">
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

@if($orders->hasPages())
  <div class="d-flex justify-content-end mt-3">
    {{ $orders->links() }}
  </div>
@endif
@endsection