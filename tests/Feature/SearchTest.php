<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SearchTest extends TestCase
{
    /**
     * @test
     */
    public function can_get_params()
    {
        $this->get('/api/v2/params/search')
            ->assertStatus(200)
            ->assertSeeText('success');
    }

    /**
     * @test
     */
    public function can_search_with_params()
    {
        $this->get('/api/v2/params/search?notbasedon=None&ostype=Linux&category=All&origin=All&basedon=Ubuntu&desktop=Xfce&architecture=All&package=All&rolling=All&isosize=All&netinstall=All&language=All&defaultinit=All&status=Active')
            ->assertStatus(200)
            ->assertSeeText('success');
    }
}
