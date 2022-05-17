<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DistributionNewsTest extends TestCase
{
    /**
     * @test
     */
    public function get_latest_12_news_and_1_sponsor_news()
    {
        $this->get('/api/v2/news')
            ->assertStatus(200)
            ->assertSeeText('news');
    }

    /**
     * @test
     */
    public function get_news_by_id()
    {
        $this->get('/api/v2/news/11531')
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function get_all_news_params()
    {
        $this->get('/api/v2/params/news/')
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function search_news_with_avaiable_params()
    {
        $this->get('/api/v2/news?distribution=ubuntu&release=stable&month=all&year=2021')
            ->assertStatus(200)
            ->assertSeeText('news');
    }
}
