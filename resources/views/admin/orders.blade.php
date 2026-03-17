@extends('admin.dashboard')

@section('admin_content')
<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
<h2>Prehľad objednávok</h2>
</div>
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
        <td>{{ $order->user->name }}</td>
        <td>{{ $order->meal->name_sk }}</td>
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
@endsection