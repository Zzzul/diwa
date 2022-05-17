<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DistributionTest extends TestCase
{
    /**
     * @test
     */
    public function get_all_distributions()
    {
        $this->get('/api/v2/distributions')
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function get_distribution_by_slug()
    {
        $this->get('/api/v2/distributions/mx')
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function distribution_not_found()
    {
        $this->get('/api/v2/distributions/qwe/')
            ->assertStatus(404)
            ->assertSeeText('distribution not found.');
    }

    /**
     * @test
     */
    public function search_distribution()
    {
        $this->get('api/v2/search?notbasedon=None&ostype=Linux&category=All&origin=All&basedon=Ubuntu&desktop=Xfce&architecture=All&package=All&rolling=All&isosize=All&netinstall=All&language=All&defaultinit=All&status=Active')
            ->assertStatus(200);
    }
}
