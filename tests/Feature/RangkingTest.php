<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RangkingTest extends TestCase
{
    /**
     * @test
     */
    public function can_get_rankings()
    {
        $this->get('/api/v2/rankings')
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function can_get_rankings_with_custom_params()
    {
        $this->get('/api/v2/rankings/trending-1')
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function can_get_rankings_params()
    {
        $this->get('/api/v2/params/rankings/')
            ->assertStatus(200);
    }
}
