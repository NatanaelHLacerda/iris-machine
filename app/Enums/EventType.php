<?php

namespace App\Enums;

enum EventType: string
{
    case PageView = 'page_view';
    case CtaClick = 'cta_click';
    case FormView = 'form_view';
    case FormStart = 'form_start';
    case FormSubmit = 'form_submit';

    public function label(): string
    {
        return match ($this) {
            self::PageView => 'Visitou a landing',
            self::CtaClick => 'Clicou em um CTA',
            self::FormView => 'Viu o formulário',
            self::FormStart => 'Começou a preencher',
            self::FormSubmit => 'Enviou o formulário',
        };
    }
}
