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
        $openModal = session('open_modal');
        $openModalId = session('open_modal_id');
        $oldEditValues = [
            'name' => old('name'),
            'address' => old('address'),
            'is_active' => old('is_active', 1),
            'notifications_enabled' => old('notifications_enabled', 1),
            'notify_open_offset_min' => old('notify_open_offset_min', 30),
            'notify_close_offset_min' => old('notify_close_offset_min', 30),
        ];

        foreach (array_keys($days) as $dayKey) {
            $oldEditValues['open_time_' . $dayKey] = old('open_time_' . $dayKey);
            $oldEditValues['close_time_' . $dayKey] = old('close_time_' . $dayKey);
            $oldEditValues['clear_day_' . $dayKey] = old('clear_day_' . $dayKey);
        }
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

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Názov</th>
                    <th>Stav</th>
                    <th>Adresa</th>
                    <th>Pracovný čas</th>
                    <th class="text-end">Akcie</th>
                </tr>
            </thead>
            <tbody>
                @foreach($canteens as $canteen)
                    <tr>
                        <td><strong>{{ $canteen->name }}</strong></td>
                        <td>
                            @if((bool) ($canteen->is_active ?? true))
                                <span class="badge bg-success">Aktívna</span>
                            @else
                                <span class="badge bg-secondary">Neaktívna</span>
                            @endif
                        </td>
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
                                data-is-active="{{ (int) ($canteen->is_active ?? 1) }}"
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
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Adresa</label>
                            <input type="text" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address') }}" required>
                            @error('address')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" value="1" id="addIsActive" name="is_active" @checked(old('is_active', true))>
                            <label class="form-check-label" for="addIsActive">Aktívna</label>
                            <div class="form-text">Neaktívna jedáleň sa používateľom nezobrazuje a nedá sa na ňu nič priradiť.</div>
                        </div>

                        @if($hasNotificationsEnabled)
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" value="1" id="addNotificationsEnabled" name="notifications_enabled" @checked(old('notifications_enabled', true))>
                                <label class="form-check-label" for="addNotificationsEnabled">
                                    Povoliť upozornenia pre túto jedáleň
                                </label>
                                <div class="form-text">Ak jedáleň vypnete, push upozornenia sa automaticky vypnú.</div>
                            </div>
                        @endif

                        @if($hasOpenOffset || $hasCloseOffset)
                            <div class="row g-3 mb-3">
                                @if($hasOpenOffset)
                                    <div class="col-md-6">
                                        <label class="form-label">Predstih upozornenia pred otvorením (min)</label>
                                        <input type="number" name="notify_open_offset_min" class="form-control @error('notify_open_offset_min') is-invalid @enderror" min="0" max="360" value="{{ old('notify_open_offset_min', 30) }}">
                                        @error('notify_open_offset_min')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endif
                                @if($hasCloseOffset)
                                    <div class="col-md-6">
                                        <label class="form-label">Predstih upozornenia pred zatvorením (min)</label>
                                        <input type="number" name="notify_close_offset_min" class="form-control @error('notify_close_offset_min') is-invalid @enderror" min="0" max="360" value="{{ old('notify_close_offset_min', 30) }}">
                                        @error('notify_close_offset_min')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
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
                                                <input type="time" name="{{ $openColumn }}" id="add{{ ucfirst($openColumn) }}" class="form-control day-time-input @error($openColumn) is-invalid @enderror" value="{{ old($openColumn) }}" placeholder="Otvorené">
                                            @endif
                                            @if(in_array($closeColumn, $editableColumns ?? [], true))
                                                <input type="time" name="{{ $closeColumn }}" id="add{{ ucfirst($closeColumn) }}" class="form-control day-time-input @error($closeColumn) is-invalid @enderror" value="{{ old($closeColumn) }}" placeholder="Zatvorené">
                                            @endif
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input day-closed-toggle" type="checkbox" value="1" id="addClearDay{{ ucfirst($dayKey) }}" name="clear_day_{{ $dayKey }}" @checked(old('clear_day_' . $dayKey))>
                                            <label class="form-check-label" for="addClearDay{{ ucfirst($dayKey) }}">
                                                Zatvorené / bez času
                                            </label>
                                        </div>
                                        @error($openColumn)
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        @error($closeColumn)
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
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
                            <input type="text" name="name" id="editName" class="form-control @error('name') is-invalid @enderror" required>
                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Adresa</label>
                            <input type="text" name="address" id="editAddress" class="form-control @error('address') is-invalid @enderror" required>
                            @error('address')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" value="1" id="editIsActive" name="is_active">
                            <label class="form-check-label" for="editIsActive">Aktívna</label>
                            <div class="form-text">Neaktívna jedáleň nebude zobrazovaná nikdy okrem tejto stránky. Služí iba na archívovanie jedálne, v ktorej sú alebo boli položky.</div>
                        </div>

                        @if($hasNotificationsEnabled)
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" value="1" id="editNotificationsEnabled" name="notifications_enabled" @checked(old('notifications_enabled', true))>
                                <label class="form-check-label" for="editNotificationsEnabled">
                                    Povoliť upozornenia pre túto jedáleň
                                </label>
                                <div class="form-text">Pri neaktívnej jedálni sú push upozornenia vypnuté automaticky.</div>
                            </div>
                        @endif

                        @if($hasOpenOffset || $hasCloseOffset)
                            <div class="row g-3 mb-3">
                                @if($hasOpenOffset)
                                    <div class="col-md-6">
                                        <label class="form-label">Predstih upozornenia pred otvorením (min)</label>
                                        <input type="number" name="notify_open_offset_min" id="editNotifyOpenOffset" class="form-control @error('notify_open_offset_min') is-invalid @enderror" min="0" max="360">
                                        @error('notify_open_offset_min')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endif
                                @if($hasCloseOffset)
                                    <div class="col-md-6">
                                        <label class="form-label">Predstih upozornenia pred zatvorením (min)</label>
                                        <input type="number" name="notify_close_offset_min" id="editNotifyCloseOffset" class="form-control @error('notify_close_offset_min') is-invalid @enderror" min="0" max="360">
                                        @error('notify_close_offset_min')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
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
                                                <input type="time" name="{{ $openColumn }}" id="edit{{ ucfirst($openColumn) }}" class="form-control day-time-input @error($openColumn) is-invalid @enderror" placeholder="Otvorené">
                                            @endif
                                            @if(in_array($closeColumn, $editableColumns ?? [], true))
                                                <input type="time" name="{{ $closeColumn }}" id="edit{{ ucfirst($closeColumn) }}" class="form-control day-time-input @error($closeColumn) is-invalid @enderror" placeholder="Zatvorené">
                                            @endif
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input day-closed-toggle" type="checkbox" value="1" id="editClearDay{{ ucfirst($dayKey) }}" name="clear_day_{{ $dayKey }}">
                                            <label class="form-check-label" for="editClearDay{{ ucfirst($dayKey) }}">
                                                Zatvorené / bez času
                                            </label>
                                        </div>
                                        @error($openColumn)
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        @error($closeColumn)
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
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
            const openModal = @json($openModal);
            const openModalId = @json($openModalId);
            const oldEditValues = @json($oldEditValues);

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

            const syncNotificationControls = (modal) => {
                if (!modal) {
                    return;
                }

                const activeInput = modal.querySelector('input[name="is_active"]:not([type="hidden"])');
                const notificationsInput = modal.querySelector('input[name="notifications_enabled"]');

                if (!activeInput || !notificationsInput) {
                    return;
                }

                const applyState = () => {
                    const active = activeInput.checked;
                    notificationsInput.disabled = !active;

                    if (!active) {
                        notificationsInput.checked = false;
                    }
                };

                activeInput.addEventListener('change', applyState);
                applyState();
            };

            const populateEditModal = (source) => {
                const getValue = (key, fallback = '') => {
                    if (!source) {
                        return fallback;
                    }

                    const value = source[key];

                    return value === undefined || value === null ? fallback : value;
                };

                editModal.querySelector('#editCanteenForm').action = `/admin/canteens/${getValue('id')}`;
                editModal.querySelector('#editName').value = getValue('name');
                editModal.querySelector('#editAddress').value = getValue('address');

                const isActiveInput = editModal.querySelector('#editIsActive');
                if (isActiveInput) {
                    isActiveInput.checked = Number(getValue('is_active', 1)) === 1;
                }

                const notificationsEnabledInput = editModal.querySelector('#editNotificationsEnabled');
                if (notificationsEnabledInput) {
                    notificationsEnabledInput.checked = Number(getValue('notifications_enabled', 1)) === 1;
                    notificationsEnabledInput.disabled = !Number(getValue('is_active', 1));
                }

                const notifyOpenOffsetInput = editModal.querySelector('#editNotifyOpenOffset');
                if (notifyOpenOffsetInput) {
                    notifyOpenOffsetInput.value = getValue('notify_open_offset_min', '30');
                }

                const notifyCloseOffsetInput = editModal.querySelector('#editNotifyCloseOffset');
                if (notifyCloseOffsetInput) {
                    notifyCloseOffsetInput.value = getValue('notify_close_offset_min', '30');
                }

                @foreach($days as $dayKey => $dayLabel)
                    @php
                        $openColumn = 'open_time_' . $dayKey;
                        $closeColumn = 'close_time_' . $dayKey;
                    @endphp
                    @if(in_array($openColumn, $editableColumns ?? [], true))
                        const edit{{ ucfirst($openColumn) }} = editModal.querySelector('#edit{{ ucfirst($openColumn) }}');
                        if (edit{{ ucfirst($openColumn) }}) {
                            edit{{ ucfirst($openColumn) }}.value = getValue('{{ $openColumn }}');
                        }
                    @endif
                    @if(in_array($closeColumn, $editableColumns ?? [], true))
                        const edit{{ ucfirst($closeColumn) }} = editModal.querySelector('#edit{{ ucfirst($closeColumn) }}');
                        if (edit{{ ucfirst($closeColumn) }}) {
                            edit{{ ucfirst($closeColumn) }}.value = getValue('{{ $closeColumn }}');
                        }
                    @endif

                    const clearDay{{ ucfirst($dayKey) }} = editModal.querySelector('[name="clear_day_{{ $dayKey }}"]');
                    if (clearDay{{ ucfirst($dayKey) }}) {
                        const clearDayValue = getValue('clear_day_{{ $dayKey }}', null);
                        if (clearDayValue !== null) {
                            clearDay{{ ucfirst($dayKey) }}.checked = Boolean(clearDayValue);
                        } else {
                            const openValue = getValue('{{ $openColumn }}');
                            const closeValue = getValue('{{ $closeColumn }}');
                            clearDay{{ ucfirst($dayKey) }}.checked = !openValue && !closeValue;
                        }

                        clearDay{{ ucfirst($dayKey) }}.dispatchEvent(new Event('change'));
                    }
                @endforeach
            };

            const buildEditSourceFromButton = (button) => {
                const source = {
                    id: button.getAttribute('data-id') || '',
                    name: button.getAttribute('data-name') || '',
                    address: button.getAttribute('data-address') || '',
                    is_active: button.getAttribute('data-is-active') || '1',
                    notifications_enabled: button.getAttribute('data-notifications-enabled') || '1',
                    notify_open_offset_min: button.getAttribute('data-notify-open-offset-min') || '30',
                    notify_close_offset_min: button.getAttribute('data-notify-close-offset-min') || '30',
                };

                @foreach($days as $dayKey => $dayLabel)
                    @php
                        $openColumn = 'open_time_' . $dayKey;
                        $closeColumn = 'close_time_' . $dayKey;
                    @endphp
                    @if(in_array($openColumn, $editableColumns ?? [], true))
                        source['{{ $openColumn }}'] = button.getAttribute('data-{{ str_replace('_', '-', $openColumn) }}') || '';
                    @endif
                    @if(in_array($closeColumn, $editableColumns ?? [], true))
                        source['{{ $closeColumn }}'] = button.getAttribute('data-{{ str_replace('_', '-', $closeColumn) }}') || '';
                    @endif
                @endforeach

                return source;
            };

            const addModal = document.getElementById('addCanteenModal');
            bindClosedToggles(addModal);
            syncNotificationControls(addModal);

            const editModal = document.getElementById('editCanteenModal');
            bindClosedToggles(editModal);
            syncNotificationControls(editModal);

            editModal.addEventListener('show.bs.modal', function (event) {
                if (!event.relatedTarget) {
                    return;
                }

                populateEditModal(buildEditSourceFromButton(event.relatedTarget));
            });

            const deleteModal = document.getElementById('deleteCanteenModal');
            deleteModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');

                deleteModal.querySelector('#deleteCanteenForm').action = `/admin/canteens/${id}`;
                deleteModal.querySelector('#delCanteenName').textContent = name;
            });

            if (openModal === 'add') {
                new bootstrap.Modal(addModal).show();
            }

            if (openModal === 'edit' && openModalId) {
                oldEditValues.id = openModalId;
                populateEditModal(oldEditValues);
                new bootstrap.Modal(editModal).show();
            }
        });
    </script>
@endsection