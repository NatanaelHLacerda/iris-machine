<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Support\Crm\LeadFilters;
use App\Support\Crm\Period;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadExportController extends Controller
{
    private const array HEADER = ['Data', 'Nome', 'E-mail', 'WhatsApp', 'Momento', 'Formato', 'Etapa', 'Canal', 'Campanha', 'Conjunto', 'Anúncio'];

    public function __invoke(Request $request): StreamedResponse
    {
        $query = LeadFilters::apply(Lead::query(), LeadFilters::fromRequest($request))->latest();

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, self::HEADER, ';');

            $query->lazy()->each(fn (Lead $lead) => fputcsv($out, [
                $lead->created_at->setTimezone(Period::TIMEZONE)->format('d/m/Y H:i'),
                $lead->name,
                $lead->email,
                $lead->formattedWhatsapp(),
                $lead->level->label(),
                $lead->plan_interest?->label(),
                $lead->status->label(),
                $lead->channel->label(),
                $lead->utm_campaign,
                $lead->utm_content,
                $lead->utm_term,
            ], ';'));

            fclose($out);
        }, 'leads-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
