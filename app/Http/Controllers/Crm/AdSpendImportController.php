<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\ImportAdSpendRequest;
use App\Support\Crm\MetaAdsCsvImporter;
use Illuminate\Http\RedirectResponse;

class AdSpendImportController extends Controller
{
    public function store(ImportAdSpendRequest $request, MetaAdsCsvImporter $importer): RedirectResponse
    {
        $result = $importer->import($request->file('file')->getRealPath());

        return back()->with('status', sprintf(
            'Importação concluída: %d novas linhas, %d atualizadas, %d ignoradas.',
            $result['created'],
            $result['updated'],
            $result['skipped'],
        ));
    }
}
