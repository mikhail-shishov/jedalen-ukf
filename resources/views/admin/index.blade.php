@extends('admin.dashboard')

@section('admin_content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Vitajte, {{ Auth::user()->first_name }}!</h1>
</div>
<div class="row">
    <div class="col-md-4">
        <div class="card text-white bg-dark mb-3">
            <div class="card-body">
                <h5 class="card-title">Kredit</h5>
                <p class="card-text h3">{{ Auth::user()->credit_balance }} €</p>
            </div>
        </div>
    </div>
</div>
@endsection