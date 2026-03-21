@extends('admin.dashboard')

@section('admin_content')
<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
<h2>Prehľad objednávok</h2>
</div>

<ul class="nav nav-tabs mb-3" role="tablist">
  <li class="nav-item">
    <a class="nav-link {{ $tab === 'orders' ? 'active' : '' }}" href="{{ route('admin.orders', ['tab' => 'orders']) }}">
      Objednávky
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link {{ $tab === 'exchange' ? 'active' : '' }}" href="{{ route('admin.orders', ['tab' => 'exchange']) }}">
      Burža
    </a>
  </li>
</ul>

<form method="GET" action="{{ route('admin.orders') }}" class="row g-2 mb-3">
  <input type="hidden" name="tab" value="{{ $tab }}">
  <div class="col-md-6 col-lg-4">
    <input
      type="search"
      name="q"
      class="form-control"
      value="{{ $searchQuery ?? '' }}"
      placeholder="{{ $tab === 'exchange' ? 'Hľadať v burži...' : 'Hľadať v objednávkach...' }}">
  </div>
  <div class="col-auto">
    <button type="submit" class="btn btn-outline-primary">Hľadať</button>
  </div>
  @if(!empty($searchQuery))
    <div class="col-auto">
      <a href="{{ route('admin.orders', ['tab' => $tab]) }}" class="btn btn-outline-secondary">Vymazať filter</a>
    </div>
  @endif
</form>

@if($tab === 'orders')
  @include('admin.orders_table', ['orders' => $orders])
@else
  @include('admin.exchange_table', ['exchanges' => $exchanges])
@endif

@endsection