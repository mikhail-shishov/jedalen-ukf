<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Canteen;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class AdminCanteenController extends Controller
{
    private function editableColumns(): array
    {
        $columns = [
            'notifications_enabled',
            'notify_open_offset_min',
            'notify_close_offset_min',
            'open_time_mon',
            'close_time_mon',
            'open_time_tue',
            'close_time_tue',
            'open_time_wed',
            'close_time_wed',
            'open_time_thu',
            'close_time_thu',
            'open_time_fri',
            'close_time_fri',
            'open_time_sat',
            'close_time_sat',
            'open_time_sun',
            'close_time_sun',
        ];

        return collect($columns)
            ->filter(fn (string $column) => Schema::hasColumn('canteens', $column))
            ->values()
            ->all();
    }

    private function validationRules(): array
    {
        $rules = [
            'name' => 'required|string|max:100',
            'address' => 'required|string|max:255',
        ];

        if (Schema::hasColumn('canteens', 'is_active')) {
            $rules['is_active'] = 'nullable|boolean';
        }

        if (Schema::hasColumn('canteens', 'notifications_enabled')) {
            $rules['notifications_enabled'] = 'nullable|boolean';
        }

        if (Schema::hasColumn('canteens', 'notify_open_offset_min')) {
            $rules['notify_open_offset_min'] = 'nullable|integer|min:0|max:360';
        }

        if (Schema::hasColumn('canteens', 'notify_close_offset_min')) {
            $rules['notify_close_offset_min'] = 'nullable|integer|min:0|max:360';
        }

        foreach (['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'] as $day) {
            $openColumn = 'open_time_' . $day;
            $closeColumn = 'close_time_' . $day;

            if (Schema::hasColumn('canteens', $openColumn)) {
                $rules[$openColumn] = 'nullable|date_format:H:i';
            }

            if (Schema::hasColumn('canteens', $closeColumn)) {
                $rules[$closeColumn] = 'nullable|date_format:H:i';
            }

            $rules['clear_day_' . $day] = 'nullable|boolean';
        }

        return $rules;
    }

    private function validatePayload(Request $request, ?Canteen $canteen = null)
    {
        $validator = Validator::make($request->all(), $this->validationRules());

        $validator->after(function ($validator) use ($request, $canteen) {
            $name = trim((string) $request->input('name', ''));
            $address = trim((string) $request->input('address', ''));

            if ($name === '' || $address === '') {
                return;
            }

            $query = Canteen::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->whereRaw('LOWER(address) = ?', [mb_strtolower($address)]);

            if ($canteen !== null) {
                $query->where('id', '!=', $canteen->id);
            }

            if ($query->exists()) {
                $message = 'Jedáleň s rovnakým názvom a adresou už existuje.';
                $validator->errors()->add('name', $message);
                $validator->errors()->add('address', $message);
            }
        });

        return $validator;
    }

    private function normalizePayload(Request $request, array $validated): array
    {
        $validated['name'] = trim((string) $validated['name']);
        $validated['address'] = trim((string) $validated['address']);

        $isActive = true;

        if (Schema::hasColumn('canteens', 'is_active')) {
            $isActive = $request->boolean('is_active');
            $validated['is_active'] = $isActive;
        }

        if (Schema::hasColumn('canteens', 'notifications_enabled')) {
            $validated['notifications_enabled'] = $isActive && $request->boolean('notifications_enabled');
        }

        if (Schema::hasColumn('canteens', 'notify_open_offset_min') && !array_key_exists('notify_open_offset_min', $validated)) {
            $validated['notify_open_offset_min'] = 30;
        }

        if (Schema::hasColumn('canteens', 'notify_close_offset_min') && !array_key_exists('notify_close_offset_min', $validated)) {
            $validated['notify_close_offset_min'] = 30;
        }

        foreach (['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'] as $day) {
            $openColumn = 'open_time_' . $day;
            $closeColumn = 'close_time_' . $day;
            $clearDay = $request->boolean('clear_day_' . $day);

            if ($clearDay) {
                if (Schema::hasColumn('canteens', $openColumn)) {
                    $validated[$openColumn] = null;
                }

                if (Schema::hasColumn('canteens', $closeColumn)) {
                    $validated[$closeColumn] = null;
                }

                unset($validated['clear_day_' . $day]);
                continue;
            }

            if (array_key_exists($openColumn, $validated) && $validated[$openColumn] === '') {
                $validated[$openColumn] = null;
            }

            if (array_key_exists($closeColumn, $validated) && $validated[$closeColumn] === '') {
                $validated[$closeColumn] = null;
            }

            unset($validated['clear_day_' . $day]);
        }

        return $validated;
    }

    private function isDuplicateCanteenException(QueryException $exception): bool
    {
        return (string) $exception->getCode() === '23000' || str_contains($exception->getMessage(), 'canteens_name_address_unique');
    }

    public function index()
    {
        $canteens = Canteen::orderByDesc('is_active')->orderBy('name')->get();
        $editableColumns = $this->editableColumns();

        return view('admin.canteens', compact('canteens', 'editableColumns'));
    }

    public function store(Request $request)
    {
        $validator = $this->validatePayload($request);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput()
                ->with('open_modal', 'add');
        }

        $data = $this->normalizePayload($request, $validator->validated());

        try {
            Canteen::create($data);
        } catch (QueryException $exception) {
            if ($this->isDuplicateCanteenException($exception)) {
                return redirect()
                    ->back()
                    ->withErrors([
                        'name' => 'Jedáleň s rovnakým názvom a adresou už existuje.',
                        'address' => 'Jedáleň s rovnakým názvom a adresou už existuje.',
                    ])
                    ->withInput()
                    ->with('open_modal', 'add');
            }

            throw $exception;
        }

        return redirect()->back()->with('success', 'Jedáleň bola pridaná.');
    }

    public function update(Request $request, $id)
    {
        $canteen = Canteen::findOrFail($id);
        $validator = $this->validatePayload($request, $canteen);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput()
                ->with('open_modal', 'edit')
                ->with('open_modal_id', $canteen->id);
        }

        $data = $this->normalizePayload($request, $validator->validated());

        try {
            $canteen->update($data);
        } catch (QueryException $exception) {
            if ($this->isDuplicateCanteenException($exception)) {
                return redirect()
                    ->back()
                    ->withErrors([
                        'name' => 'Jedáleň s rovnakým názvom a adresou už existuje.',
                        'address' => 'Jedáleň s rovnakým názvom a adresou už existuje.',
                    ])
                    ->withInput()
                    ->with('open_modal', 'edit')
                    ->with('open_modal_id', $canteen->id);
            }

            throw $exception;
        }

        return redirect()->back()->with('success', 'Jedáleň bola aktualizovaná.');
    }

    public function destroy($id)
    {
        $canteen = Canteen::findOrFail($id);

        $blockingRelations = $this->blockingRelations($canteen);
        $blockingTotal = array_sum($blockingRelations);

        if ($blockingTotal > 0) {
            $parts = [];

            if ($blockingRelations['articles'] > 0) {
                $parts[] = $blockingRelations['articles'] . ' článkov';
            }

            if ($blockingRelations['menu_items'] > 0) {
                $parts[] = $blockingRelations['menu_items'] . ' položiek menu';
            }

            if ($blockingRelations['closures'] > 0) {
                $parts[] = $blockingRelations['closures'] . ' uzávierok';
            }

            return redirect()->back()->withErrors([
                'delete_canteen' => 'Jedáleň nie je možné zmazať, kým je naviazaná na: ' . implode(', ', $parts) . '.',
            ]);
        }

        $canteen->delete();
        return redirect()->back()->with('success', 'Jedáleň bola odstránená.');
    }

    private function blockingRelations(Canteen $canteen): array
    {
        $counts = [
            'articles' => 0,
            'menu_items' => 0,
            'closures' => 0,
        ];

        if (Schema::hasTable('articles_has_canteens') && Schema::hasColumn('articles_has_canteens', 'canteens_id')) {
            $counts['articles'] = DB::table('articles_has_canteens')
                ->where('canteens_id', $canteen->id)
                ->count();
        }

        if (Schema::hasTable('menu_items') && Schema::hasColumn('menu_items', 'canteen_id')) {
            $counts['menu_items'] = DB::table('menu_items')
                ->where('canteen_id', $canteen->id)
                ->count();
        }

        if (Schema::hasTable('canteen_closures') && Schema::hasColumn('canteen_closures', 'canteen_id')) {
            $counts['closures'] = DB::table('canteen_closures')
                ->where('canteen_id', $canteen->id)
                ->count();
        }

        return $counts;
    }
}
