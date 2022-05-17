<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class HomeTest extends TestCase
{
    /**
     * @test
     */
    public function home()
    {
        $this->get('/')
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function api_v1()
    {
        $this->get('/api')
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function api_v2()
    {
        $this->get('/api/v2/')
            ->assertStatus(200);
    }
}
