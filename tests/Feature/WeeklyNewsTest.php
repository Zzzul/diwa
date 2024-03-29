<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class WeeklyNewsTest extends TestCase
{
    /**
     * @test
     */
    public function can_get_weekly_news()
    {
        $this->get('/api/v2/weekly')
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function can_get_weekly_news_by_id()
    {
        $this->get('/api/v2/weekly/20220502')
            ->assertStatus(200);
    }
}
