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
    public function can_see_home_endpoint()
    {
        $this->withoutDeprecationHandling();
        $this->get('/')
            ->assertStatus(200)
            ->assertSeeText("success");
    }

    /**
     * @test
     */
    public function can_see_api_v1_endpoint()
    {
        $this->get('/api')
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function can_see_api_v2_endpoint()
    {
        $this->get('/api/v2/')
            ->assertStatus(200);
    }
}
