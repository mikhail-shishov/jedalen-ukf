@extends('admin.dashboard')

@section('admin_content')
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2>Kuchyňa</h2>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Opravte chyby vo formulári:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="GET" action="{{ route('admin.cook') }}" class="row g-2 mb-3">
        <div class="col-md-3">
            <label class="form-label">Dátum od</label>
            <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Dátum do</label>
            <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Stav objednávky</label>
            <select name="status" class="form-control">
                <option value="">Všetky stavy</option>
                <option value="ordered" @selected($filters['status'] === 'ordered')>Objednané</option>
                <option value="collected" @selected($filters['status'] === 'collected')>Vydané</option>
                <option value="cancelled" @selected($filters['status'] === 'cancelled')>Zrušené</option>
                <option value="in_exchange" @selected($filters['status'] === 'in_exchange')>V burze</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Jedáleň</label>
            <select name="canteen_id" class="form-control">
                <option value="">Všetky jedálne</option>
                @foreach($canteens as $canteen)
                    <option value="{{ $canteen->id }}" @selected($filters['canteen_id'] === (string) $canteen->id)>
                        {{ $canteen->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Filtrovať</button>
            <a href="{{ route('admin.cook') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    @if(!$hasIngredientsTable || !$hasMealIngredientsTable)
        <div class="alert alert-warning py-2" role="alert">
            Tabuľky <code>ingredients</code> alebo <code>meal_ingredients</code> v databáze neexistujú,
            preto je možné zobrazovať iba stavy porcií z menu položiek.
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header fw-bold">Prijaté objednávky</div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Dátum</th>
                                <th>Jedlo</th>
                                <th>Jedáleň</th>
                                <th>Študent</th>
                                <th>Stav</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($incomingOrders as $order)
                                <tr>
                                    <td>{{ $order->id }}</td>
                                    <td>{{ $order->meal_date }}</td>
                                    <td>{{ $order->meal_name }}</td>
                                    <td>{{ $order->canteen_name }}</td>
                                    <td>{{ trim($order->first_name . ' ' . $order->last_name) ?: '-' }}</td>
                                    <td>
                                        <span class="badge {{ $statusBadges[$order->status] ?? 'bg-secondary' }}">
                                            {{ $statusLabels[$order->status] ?? $order->status }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">Žiadne objednávky.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header fw-bold">Sklad porcií / surovín (menu položky)</div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Dátum</th>
                                <th>Jedlo</th>
                                <th>Jedáleň</th>
                                <th>Celkom</th>
                                <th>Aktuálne</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stockItems as $item)
                                <tr>
                                    <td>{{ $item->date }}</td>
                                    <td>{{ $item->meal_name }}</td>
                                    <td>{{ $item->canteen_name }}</td>
                                    <td>{{ $item->stock_total }}</td>
                                    <td>
                                        <span class="badge {{ $item->stock_current <= 10 ? 'bg-danger' : 'bg-success' }}">
                                            {{ $item->stock_current }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">Žiadne skladové položky.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if($hasIngredientsTable)
        <div class="row g-3 mb-4">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header fw-bold">Pridať surovinu</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.cook.ingredients.store') }}" class="row g-2">
                            @csrf
                            <div class="col-12">
                                <label class="form-label">Názov</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Jednotka</label>
                                <select name="unit" class="form-control" required>
                                    <option value="kg">kg</option>
                                    <option value="g">g</option>
                                    <option value="l">l</option>
                                    <option value="ml">ml</option>
                                    <option value="ks">ks</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sklad</label>
                                <input type="number" step="0.001" min="0" name="stock_quantity" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Min. limit</label>
                                <input type="number" step="0.001" min="0" name="min_limit" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Uložiť surovinu</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header fw-bold">Sklad surovín</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Surovina</th>
                                    <th>Jednotka</th>
                                    <th>Sklad</th>
                                    <th>Min. limit</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ingredients as $ingredient)
                                    <tr>
                                        <form method="POST" action="{{ route('admin.cook.ingredients.update', $ingredient->id) }}">
                                            @csrf
                                            @method('PUT')
                                            <td>{{ $ingredient->name }}</td>
                                            <td>{{ $ingredient->unit }}</td>
                                            <td>
                                                <input type="number" step="0.001" min="0" name="stock_quantity"
                                                    class="form-control form-control-sm" value="{{ $ingredient->stock_quantity }}" required>
                                            </td>
                                            <td>
                                                <input type="number" step="0.001" min="0" name="min_limit"
                                                    class="form-control form-control-sm" value="{{ $ingredient->min_limit }}" required>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" type="submit">Uložiť</button>
                                            </td>
                                        </form>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">Zatiaľ bez surovín.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($hasIngredientsTable && $hasMealIngredientsTable)
        <div class="row g-3 mb-4">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header fw-bold">Norma suroviny na jedlo</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.cook.meal-ingredients.upsert') }}" class="row g-2">
                            @csrf
                            <div class="col-12">
                                <label class="form-label">Jedlo</label>
                                <select name="meal_id" class="form-control" required>
                                    <option value="">Vyberte jedlo</option>
                                    @foreach($meals as $meal)
                                        <option value="{{ $meal->id }}">{{ $meal->label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Surovina</label>
                                <select name="ingredient_id" class="form-control" required>
                                    <option value="">Vyberte surovinu</option>
                                    @foreach($ingredients as $ingredient)
                                        <option value="{{ $ingredient->id }}">{{ $ingredient->name }} ({{ $ingredient->unit }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Množstvo na 1 porciu</label>
                                <input type="number" step="0.001" min="0.001" name="amount" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Uložiť normu</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header fw-bold">Prepojenie jedál a surovín</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Jedlo</th>
                                    <th>Surovina</th>
                                    <th>Množstvo / porcia</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($mealIngredientRows as $row)
                                    <tr>
                                        <td>{{ $row->meal_name }}</td>
                                        <td>{{ $row->ingredient_name }}</td>
                                        <td>{{ number_format((float) $row->amount, 3) }} {{ $row->ingredient_unit }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">Zatiaľ bez prepojení.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
