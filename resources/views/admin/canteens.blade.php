@extends('admin.dashboard')

@section('admin_content')
    @php
        $days = [
            'mon' => 'Pondelok',
            'tue' => 'Utorok',
            'wed' => 'Streda',
            'thu' => 'Štvrtok',
            'fri' => 'Piatok',
            'sat' => 'Sobota',
            'sun' => 'Nedeľa',
        ];
        $hasNotificationsEnabled = in_array('notifications_enabled', $editableColumns ?? [], true);
        $hasOpenOffset = in_array('notify_open_offset_min', $editableColumns ?? [], true);
        $hasCloseOffset = in_array('notify_close_offset_min', $editableColumns ?? [], true);
    @endphp

    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2>Správa jedální</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCanteenModal">
            <i class="bi bi-plus-lg"></i> Pridať jedáleň
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Názov</th>
                    <th>Adresa</th>
                    <th>Pracovný čas</th>
                    <th class="text-end">Akcie</th>
                </tr>
            </thead>
            <tbody>
                @foreach($canteens as $canteen)
                    <tr>
                        <td>{{ $canteen->id }}</td>
                        <td><strong>{{ $canteen->name }}</strong></td>
                        <td>{{ $canteen->address }}</td>
                        <td>
                            @foreach($days as $dayKey => $dayLabel)
                                @php
                                    $openColumn = 'open_time_' . $dayKey;
                                    $closeColumn = 'close_time_' . $dayKey;
                                    $open = in_array($openColumn, $editableColumns ?? [], true)
                                        ? ($canteen->{$openColumn} ? \Illuminate\Support\Str::of($canteen->{$openColumn})->substr(0, 5) : null)
                                        : null;
                                    $close = in_array($closeColumn, $editableColumns ?? [], true)
                                        ? ($canteen->{$closeColumn} ? \Illuminate\Support\Str::of($canteen->{$closeColumn})->substr(0, 5) : null)
                                        : null;
                                @endphp
                                <div class="small">
                                    <strong>{{ mb_substr($dayLabel, 0, 3) }}:</strong>
                                    @if($open || $close)
                                        {{ $open ?? '--:--' }} - {{ $close ?? '--:--' }}
                                    @else
                                        zatvorené
                                    @endif
                                </div>
                            @endforeach
                        </td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary edit-canteen-btn" data-bs-toggle="modal"
                                data-bs-target="#editCanteenModal" data-id="{{ $canteen->id }}" data-name="{{ $canteen->name }}"
                                data-address="{{ $canteen->address }}"
                                @if($hasNotificationsEnabled)
                                    data-notifications-enabled="{{ (int) ($canteen->notifications_enabled ?? 1) }}"
                                @endif
                                @if($hasOpenOffset)
                                    data-notify-open-offset-min="{{ $canteen->notify_open_offset_min ?? 30 }}"
                                @endif
                                @if($hasCloseOffset)
                                    data-notify-close-offset-min="{{ $canteen->notify_close_offset_min ?? 30 }}"
                                @endif
                                @foreach($days as $dayKey => $dayLabel)
                                    @php
                                        $openColumn = 'open_time_' . $dayKey;
                                        $closeColumn = 'close_time_' . $dayKey;
                                    @endphp
                                    @if(in_array($openColumn, $editableColumns ?? [], true))
                                        data-{{ str_replace('_', '-', $openColumn) }}="{{ $canteen->{$openColumn} ? \Illuminate\Support\Str::of($canteen->{$openColumn})->substr(0, 5) : '' }}"
                                    @endif
                                    @if(in_array($closeColumn, $editableColumns ?? [], true))
                                        data-{{ str_replace('_', '-', $closeColumn) }}="{{ $canteen->{$closeColumn} ? \Illuminate\Support\Str::of($canteen->{$closeColumn})->substr(0, 5) : '' }}"
                                    @endif
                                @endforeach>
                                <i class="bi bi-pencil"></i> Upraviť
                            </button>

                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                data-bs-target="#deleteCanteenModal" data-id="{{ $canteen->id }}"
                                data-name="{{ $canteen->name }}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="modal fade" id="addCanteenModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form action="{{ route('admin.canteens.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Nová jedáleň</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Názov</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Adresa</label>
                            <input type="text" name="address" class="form-control" required>
                        </div>

                        @if($hasNotificationsEnabled)
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" value="1" id="addNotificationsEnabled" name="notifications_enabled" checked>
                                <label class="form-check-label" for="addNotificationsEnabled">
                                    Povoliť upozornenia pre túto jedáleň
                                </label>
                            </div>
                        @endif

                        @if($hasOpenOffset || $hasCloseOffset)
                            <div class="row g-3 mb-3">
                                @if($hasOpenOffset)
                                    <div class="col-md-6">
                                        <label class="form-label">Predstih upozornenia pred otvorením (min)</label>
                                        <input type="number" name="notify_open_offset_min" class="form-control" min="0" max="360" value="30">
                                    </div>
                                @endif
                                @if($hasCloseOffset)
                                    <div class="col-md-6">
                                        <label class="form-label">Predstih upozornenia pred zatvorením (min)</label>
                                        <input type="number" name="notify_close_offset_min" class="form-control" min="0" max="360" value="30">
                                    </div>
                                @endif
                            </div>
                        @endif

                        <h6 class="mt-4">Pracovný čas</h6>
                        <div class="row g-3">
                            @foreach($days as $dayKey => $dayLabel)
                                @php
                                    $openColumn = 'open_time_' . $dayKey;
                                    $closeColumn = 'close_time_' . $dayKey;
                                @endphp
                                @if(in_array($openColumn, $editableColumns ?? [], true) || in_array($closeColumn, $editableColumns ?? [], true))
                                    <div class="col-md-6">
                                        <label class="form-label d-block">{{ $dayLabel }}</label>
                                        <div class="d-flex gap-2 mb-2">
                                            @if(in_array($openColumn, $editableColumns ?? [], true))
                                                <input type="time" name="{{ $openColumn }}" id="add{{ ucfirst($openColumn) }}" class="form-control day-time-input" placeholder="Otvorené">
                                            @endif
                                            @if(in_array($closeColumn, $editableColumns ?? [], true))
                                                <input type="time" name="{{ $closeColumn }}" id="add{{ ucfirst($closeColumn) }}" class="form-control day-time-input" placeholder="Zatvorené">
                                            @endif
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input day-closed-toggle" type="checkbox" value="1" id="addClearDay{{ ucfirst($dayKey) }}" name="clear_day_{{ $dayKey }}">
                                            <label class="form-check-label" for="addClearDay{{ ucfirst($dayKey) }}">
                                                Zatvorené / bez času
                                            </label>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušiť</button>
                        <button type="submit" class="btn btn-primary">Vytvoriť</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="editCanteenModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form id="editCanteenForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Upraviť jedáleň</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Názov</label>
                            <input type="text" name="name" id="editName" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Adresa</label>
                            <input type="text" name="address" id="editAddress" class="form-control" required>
                        </div>

                        @if($hasNotificationsEnabled)
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" value="1" id="editNotificationsEnabled" name="notifications_enabled">
                                <label class="form-check-label" for="editNotificationsEnabled">
                                    Povoliť upozornenia pre túto jedáleň
                                </label>
                            </div>
                        @endif

                        @if($hasOpenOffset || $hasCloseOffset)
                            <div class="row g-3 mb-3">
                                @if($hasOpenOffset)
                                    <div class="col-md-6">
                                        <label class="form-label">Predstih upozornenia pred otvorením (min)</label>
                                        <input type="number" name="notify_open_offset_min" id="editNotifyOpenOffset" class="form-control" min="0" max="360">
                                    </div>
                                @endif
                                @if($hasCloseOffset)
                                    <div class="col-md-6">
                                        <label class="form-label">Predstih upozornenia pred zatvorením (min)</label>
                                        <input type="number" name="notify_close_offset_min" id="editNotifyCloseOffset" class="form-control" min="0" max="360">
                                    </div>
                                @endif
                            </div>
                        @endif

                        <h6 class="mt-4">Pracovný čas</h6>
                        <div class="row g-3">
                            @foreach($days as $dayKey => $dayLabel)
                                @php
                                    $openColumn = 'open_time_' . $dayKey;
                                    $closeColumn = 'close_time_' . $dayKey;
                                @endphp
                                @if(in_array($openColumn, $editableColumns ?? [], true) || in_array($closeColumn, $editableColumns ?? [], true))
                                    <div class="col-md-6">
                                        <label class="form-label d-block">{{ $dayLabel }}</label>
                                        <div class="d-flex gap-2 mb-2">
                                            @if(in_array($openColumn, $editableColumns ?? [], true))
                                                <input type="time" name="{{ $openColumn }}" id="edit{{ ucfirst($openColumn) }}" class="form-control day-time-input" placeholder="Otvorené">
                                            @endif
                                            @if(in_array($closeColumn, $editableColumns ?? [], true))
                                                <input type="time" name="{{ $closeColumn }}" id="edit{{ ucfirst($closeColumn) }}" class="form-control day-time-input" placeholder="Zatvorené">
                                            @endif
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input day-closed-toggle" type="checkbox" value="1" id="editClearDay{{ ucfirst($dayKey) }}" name="clear_day_{{ $dayKey }}">
                                            <label class="form-check-label" for="editClearDay{{ ucfirst($dayKey) }}">
                                                Zatvorené / bez času
                                            </label>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušiť</button>
                        <button type="submit" class="btn btn-primary">Uložiť zmeny</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="deleteCanteenModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Potvrdiť zmazanie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Naozaj chcete zmazať jedáleň <strong id="delCanteenName"></strong>?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušiť</button>
                    <form id="deleteCanteenForm" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Zmazať</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const bindClosedToggles = (container) => {
                if (!container) {
                    return;
                }

                @foreach($days as $dayKey => $dayLabel)
                    const addOpen{{ ucfirst($dayKey) }} = container.querySelector('[name="open_time_{{ $dayKey }}"]');
                    const addClose{{ ucfirst($dayKey) }} = container.querySelector('[name="close_time_{{ $dayKey }}"]');
                    const clear{{ ucfirst($dayKey) }} = container.querySelector('[name="clear_day_{{ $dayKey }}"]');

                    if (clear{{ ucfirst($dayKey) }}) {
                        const update{{ ucfirst($dayKey) }} = () => {
                            const shouldDisable = clear{{ ucfirst($dayKey) }}.checked;
                            if (addOpen{{ ucfirst($dayKey) }}) {
                                addOpen{{ ucfirst($dayKey) }}.disabled = shouldDisable;
                                if (shouldDisable) {
                                    addOpen{{ ucfirst($dayKey) }}.value = '';
                                }
                            }
                            if (addClose{{ ucfirst($dayKey) }}) {
                                addClose{{ ucfirst($dayKey) }}.disabled = shouldDisable;
                                if (shouldDisable) {
                                    addClose{{ ucfirst($dayKey) }}.value = '';
                                }
                            }
                        };

                        clear{{ ucfirst($dayKey) }}.addEventListener('change', update{{ ucfirst($dayKey) }});
                        update{{ ucfirst($dayKey) }}();
                    }
                @endforeach
            };

            const addModal = document.getElementById('addCanteenModal');
            bindClosedToggles(addModal);

            const editModal = document.getElementById('editCanteenModal');
            bindClosedToggles(editModal);

            editModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const address = button.getAttribute('data-address');

                editModal.querySelector('#editCanteenForm').action = `/admin/canteens/${id}`;
                editModal.querySelector('#editName').value = name;
                editModal.querySelector('#editAddress').value = address;

                const notificationsEnabledInput = editModal.querySelector('#editNotificationsEnabled');
                if (notificationsEnabledInput) {
                    notificationsEnabledInput.checked = Number(button.getAttribute('data-notifications-enabled') || '1') === 1;
                }

                const notifyOpenOffsetInput = editModal.querySelector('#editNotifyOpenOffset');
                if (notifyOpenOffsetInput) {
                    notifyOpenOffsetInput.value = button.getAttribute('data-notify-open-offset-min') || '30';
                }

                const notifyCloseOffsetInput = editModal.querySelector('#editNotifyCloseOffset');
                if (notifyCloseOffsetInput) {
                    notifyCloseOffsetInput.value = button.getAttribute('data-notify-close-offset-min') || '30';
                }

                @foreach($days as $dayKey => $dayLabel)
                    @php
                        $openColumn = 'open_time_' . $dayKey;
                        $closeColumn = 'close_time_' . $dayKey;
                    @endphp
                    @if(in_array($openColumn, $editableColumns ?? [], true))
                        const edit{{ ucfirst($openColumn) }} = editModal.querySelector('#edit{{ ucfirst($openColumn) }}');
                        if (edit{{ ucfirst($openColumn) }}) {
                            edit{{ ucfirst($openColumn) }}.value = button.getAttribute('data-{{ str_replace('_', '-', $openColumn) }}') || '';
                        }
                    @endif
                    @if(in_array($closeColumn, $editableColumns ?? [], true))
                        const edit{{ ucfirst($closeColumn) }} = editModal.querySelector('#edit{{ ucfirst($closeColumn) }}');
                        if (edit{{ ucfirst($closeColumn) }}) {
                            edit{{ ucfirst($closeColumn) }}.value = button.getAttribute('data-{{ str_replace('_', '-', $closeColumn) }}') || '';
                        }
                    @endif

                    const clearDay{{ ucfirst($dayKey) }} = editModal.querySelector('[name="clear_day_{{ $dayKey }}"]');
                    if (clearDay{{ ucfirst($dayKey) }}) {
                        const openValue = button.getAttribute('data-{{ str_replace('_', '-', $openColumn) }}') || '';
                        const closeValue = button.getAttribute('data-{{ str_replace('_', '-', $closeColumn) }}') || '';
                        clearDay{{ ucfirst($dayKey) }}.checked = !openValue && !closeValue;
                        clearDay{{ ucfirst($dayKey) }}.dispatchEvent(new Event('change'));
                    }
                @endforeach
            });

            const deleteModal = document.getElementById('deleteCanteenModal');
            deleteModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');

                deleteModal.querySelector('#deleteCanteenForm').action = `/admin/canteens/${id}`;
                deleteModal.querySelector('#delCanteenName').textContent = name;
            });
        });
    </script>
@endsection