<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_home_renders_the_sales_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('IRIS MACHINE')
            ->assertSee('Agendar conversa')
            ->assertSee('id="agendar"', false);
    }
}
