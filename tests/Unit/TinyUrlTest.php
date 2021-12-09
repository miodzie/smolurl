<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\TinyUrl;
use Database\Factories\TinyUrlFactory;
use Database\Factories\ClickFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

// TODO: Much of these methods should be extracted out to their own classes.
// Can replace them with a Facade afterwards.
class TinyUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_cache_itself()
    {
        $tinyUrl = TinyUrl::factory()->make();
        $tinyUrl->cache();
        $this->assertEquals(TinyUrl::fromCache($tinyUrl->token), $tinyUrl);
    }

    public function test_it_has_many_short_url_logs()
    {
        $tinyUrl = TinyUrl::factory()->create();
        $logs = ClickFactory::times(5)->create(['tiny_url_id' => $tinyUrl->id]);

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $tinyUrl->logs);

        $this->assertEquals(count($logs), 5);
    }

    public function test_it_can_log_a_redirect()
    {
        $tinyUrl = TinyUrlFactory::new()->create();
        $click = $tinyUrl->logRedirect(request());
        $this->assertEquals($click->tiny_url_id, $tinyUrl->id);
        $this->assertEquals($click->ip_address, request()->ip());
    }

    public function test_it_creates_a_proper_redirect_url()
    {
        $shortUrl = TinyUrlFactory::new()->make();
        $this->assertEquals($shortUrl->getRedirectURL(), config('app.url') . '/' . $shortUrl->token);
    }

    public function test_it_creates_a_unique_token()
    {
        $token = TinyUrl::generateUniqueToken();
        $this->assertTrue(!TinyUrl::whereToken($token)->exists());
    }

    public function test_it_can_generate_a_valid_link_without_a_scheme()
    {
        // Arrange
        $expected = 'http://ddg.gg';
        $shortUrl = TinyUrlFactory::new()->make(['full_url' => 'ddg.gg']);

        // Act
        $url = $shortUrl->getURL();

        // Assert
        $this->assertEquals($expected, $url);
    }

    public function test_it_can_generate_a_valid_link_with_a_custom_port()
    {
        // Arrange
        $expected = 'http://ddg.gg:443/1234';
        $shortUrl = TinyUrlFactory::new()->make(['full_url' => 'ddg.gg:443/1234']);

        // Act
        $url = $shortUrl->getURL();

        // Assert
        $this->assertEquals($expected, $url);
    }

    public function test_it_can_generate_a_valid_link_with_a_custom_query_string()
    {
        // Arrange
        $expected = 'http://ddg.gg:443/1234?s=My%20Search%20String&enabled=true';
        $shortUrl = TinyUrlFactory::new()->make(['full_url' => 'ddg.gg:443/1234?s=My%20Search%20String&enabled=true']);

        // Act
        $url = $shortUrl->getURL();

        // Assert
        $this->assertEquals($expected, $url);
    }

    public function test_it_can_generate_a_valid_link_with_a_host_but_no_port()
    {
        // Arrange
        $expected = 'https://ddg.gg/1234';
        $shortUrl = TinyUrlFactory::new()->make(['full_url' => 'https://ddg.gg/1234']);

        // Act
        $url = $shortUrl->getURL();

        // Assert
        $this->assertEquals($expected, $url);
    }
}