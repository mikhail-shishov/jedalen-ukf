<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Canteen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

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

    private function normalizePayload(Request $request): array
    {
        $validated = $request->validate($this->validationRules());

        if (Schema::hasColumn('canteens', 'notifications_enabled')) {
            $validated['notifications_enabled'] = $request->boolean('notifications_enabled');
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

    public function index()
    {
        $canteens = Canteen::all();
        $editableColumns = $this->editableColumns();

        return view('admin.canteens', compact('canteens', 'editableColumns'));
    }

    public function store(Request $request)
    {
        $data = $this->normalizePayload($request);

        Canteen::create($data);
        return redirect()->back()->with('success', 'Jedáleň bola pridaná.');
    }

    public function update(Request $request, $id)
    {
        $canteen = Canteen::findOrFail($id);
        $data = $this->normalizePayload($request);

        $canteen->update($data);
        return redirect()->back()->with('success', 'Jedáleň bola aktualizovaná.');
    }

    public function destroy($id)
    {
        $canteen = Canteen::findOrFail($id);
        $canteen->delete();
        return redirect()->back()->with('success', 'Jedáleň bola odstránená.');
    }
}
