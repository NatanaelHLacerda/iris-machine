<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrackingEventsRequest extends FormRequest
{
    /**
     * O endpoint dispensa CSRF (sendBeacon não manda token), então só aceita a própria landing como origem.
     */
    public function authorize(): bool
    {
        $origin = $this->headers->get('Origin') ?? $this->headers->get('Referer');

        return $origin !== null
            && parse_url($origin, PHP_URL_HOST) === parse_url((string) config('app.url'), PHP_URL_HOST);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'visitor_id' => ['required', 'uuid'],
            'session_id' => ['required', 'uuid'],
            'events' => ['required', 'array', 'max:20'],
            'events.*.type' => ['required', 'string', 'max:20'],
            'events.*.target' => ['nullable', 'string', 'max:60'],
            'events.*.path' => ['required', 'string', 'max:255'],
            'attribution' => ['nullable', 'array'],
            'attribution.utm_source' => ['nullable', 'string', 'max:255'],
            'attribution.utm_campaign' => ['nullable', 'string', 'max:255'],
            'attribution.utm_content' => ['nullable', 'string', 'max:255'],
            'attribution.utm_term' => ['nullable', 'string', 'max:255'],
            'attribution.fbclid' => ['nullable', 'string', 'max:255'],
            'attribution.referrer' => ['nullable', 'string', 'max:255'],
        ];
    }
}
