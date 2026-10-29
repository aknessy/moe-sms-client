<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * A page title passes through two components before it is printed. Written as
 * an interpolated attribute it was escaped at every hop, so "Reports &
 * Analytics" reached the browser tab reading "Reports &amp;amp; Analytics".
 */
class PageTitleEscapingTest extends TestCase
{
    use RefreshDatabase;

    private function title(string $html): string
    {
        preg_match('/<title>(.*?)<\/title>/s', $html, $matches);

        return trim($matches[1] ?? '');
    }

    public static function pagesWithAnAmpersandInTheirTitle(): array
    {
        return [
            'nominal roll report' => ['nominal-roll-report', 'Reports & Analytics'],
            'retirement projection' => ['retirement-projection', 'Reports & Analytics'],
            'headcount report' => ['headcount-report', 'Reports & Analytics'],
            'posting & deployment' => ['posting-deployment', 'Staff Management'],
        ];
    }

    #[DataProvider('pagesWithAnAmpersandInTheirTitle')]
    public function test_an_ampersand_in_a_page_title_is_escaped_once(string $route, string $expected): void
    {
        $html = $this->actingAs(User::factory()->create(['role' => 'Administrator']))
            ->get(route($route))->assertOk()->getContent();

        $title = $this->title($html);

        // Once: "&amp;". Twice: "&amp;amp;", which reads as "&amp;" on screen.
        $this->assertStringNotContainsString('&amp;amp;', $title);
        $this->assertStringContainsString($expected, html_entity_decode($title));
    }

    public function test_the_login_page_title_is_escaped_once_too(): void
    {
        $title = $this->title($this->get(route('login'))->getContent());

        $this->assertStringNotContainsString('&amp;amp;', $title);
    }

    public function test_a_title_without_an_ampersand_is_unharmed(): void
    {
        $html = $this->actingAs(User::factory()->create(['role' => 'Administrator']))
            ->get(route('nominal-roll'))->getContent();

        $this->assertSame('MOE | Staff Management - Nominall Roll', $this->title($html));
    }
}
