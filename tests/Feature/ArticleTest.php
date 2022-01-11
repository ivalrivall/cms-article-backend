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
    public function testArticle()
    {
        // login
        $response = $this->postJson('/api/login-web', ['userId' => 'admin', 'password' => 'admin1234', 'email'=>'admin@gmail.com']);
        $response->assertStatus(200);

        // insert article
        $faker = Faker::create();
        $slug = $faker->slug;
        $titleArticle = $faker->name;
        $file = UploadedFile::fake()->image('article-banner.jpg');
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
        $postArticleJson = $postArticle->decodeResponseJson();
        $postArticle->assertStatus(200);

        // update article
        $faker = Faker::create();
        $slug = $faker->slug;
        $titleArticle = $faker->name;
        $file = UploadedFile::fake()->image('article-banner.jpg');
        $updateArticle = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->postJson('/api/app-content/article/update', [
            'articleId' => $postArticleJson['data']['id'],
            'title' => $titleArticle,
            'image' => $file,
            'url'=> $slug,
            'description' => null
        ]);
        $updateArticle->assertStatus(200);
    }
}
