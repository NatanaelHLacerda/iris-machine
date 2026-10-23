<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Crm\GoalProgress;
use App\Support\Crm\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class GoalSettingController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        try {
            $cents = Money::toCents((string) $request->input('goal'));
        } catch (InvalidArgumentException) {
            $cents = null;
        }

        if ($cents === null || $cents <= 0) {
            throw ValidationException::withMessages(['goal' => 'Informe a meta em reais, por exemplo 4.000,00.']);
        }

        Setting::put(GoalProgress::GOAL_KEY, $cents);

        return back()->with('status', 'Meta mensal atualizada para '.Money::format($cents).'.');
    }
}
