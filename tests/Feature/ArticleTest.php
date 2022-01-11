<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Faker\Factory as Faker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ArticleTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testInsertArticle()
    {
        $faker = Faker::create();
        $slug = $faker->slug;
        $titleArticle = $faker->name;
        $file = UploadedFile::fake()->image('article-banner.jpg');
        $response = $this->postJson('/api/login-web', ['userId' => 'admin', 'password' => 'admin1234', 'email'=>'admin@gmail.com']);
        $response->assertStatus(200);
        $token = $response->decodeResponseJson();
        $token = $token['data']['token'];
        $postArticle = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->postJson('/api/app-content/article/create', [
            'title' => $titleArticle,
            'image' => $file,
            'url'=> $slug,
            'description' => null
        ]);
        $postArticle->assertStatus(200);
    }
}
