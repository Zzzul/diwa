<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LatestReleasedTest extends TestCase
{
    /**
     * @test
     */
    public function can_get_latest_distributions()
    {
        $this->get('api/v2/latest/distributions')
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function can_get_latest_headlines()
    {
        $this->get('api/v2/latest/headlines')
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function can_get_latest_newsletters()
    {
        $this->get('api/v2/latest/newsletters')
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function can_get_latest_packages()
    {
        $this->get('api/v2/latest/packages')
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function can_get_latest_podcasts()
    {
        $this->get('api/v2/latest/podcasts')
            ->assertStatus(200);
    }
}
