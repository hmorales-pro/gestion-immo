<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Auth\Login;
use App\Listeners\BlockDemoNotifications;
use App\Livewire\PublicBooking;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\VisitRequested;
use App\Support\Demo;
use App\Support\Guides;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class WebsiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_is_seo_ready(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('<html lang="fr"', $html);
        $this->assertMatchesRegularExpression('#<title>[^<]{20,80}</title>#', $html);
        $this->assertStringContainsString('<meta name="description"', $html);
        $this->assertStringContainsString('<link rel="canonical" href="'.url('/').'"', $html);
        $this->assertStringContainsString('og:image', $html);
        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringContainsString('"@type":"SoftwareApplication"', $html);
        $this->assertStringContainsString('"@type":"FAQPage"', $html);
        $this->assertStringNotContainsString('livewire', strtolower($html), 'The landing page must stay lightweight.');
    }

    public function test_guides_are_listed_and_rendered(): void
    {
        $guides = Guides::all();
        $this->assertGreaterThanOrEqual(3, $guides->count());

        $this->get('/guides')->assertOk()->assertSee($guides->first()['title']);

        $guide = $guides->first();
        $html = $this->get('/guides/'.$guide['slug'])->assertOk()->getContent();
        $this->assertStringContainsString('"@type":"Article"', $html);
        $this->assertStringContainsString('"@type":"BreadcrumbList"', $html);
        $this->assertStringContainsString('<h2', $html);

        $this->get('/guides/nexiste-pas')->assertNotFound();
    }

    public function test_guide_metadata_respects_seo_lengths(): void
    {
        foreach (Guides::all() as $guide) {
            $this->assertLessThanOrEqual(90, mb_strlen($guide['title']), $guide['slug']);
            $this->assertLessThanOrEqual(170, mb_strlen($guide['description']), $guide['slug']);
        }
    }

    public function test_sitemap_and_robots(): void
    {
        $xml = $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->getContent();

        $this->assertNotFalse(simplexml_load_string($xml));
        $this->assertStringContainsString('<loc>'.url('/').'</loc>', $xml);
        $this->assertStringContainsString('/guides/'.Guides::all()->first()['slug'], $xml);
        $this->assertStringContainsString('/demo', $xml);

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: '.url('/sitemap.xml'), false)
            ->assertSee('Disallow: /admin', false);
    }

    public function test_legal_pages(): void
    {
        $this->get('/mentions-legales')->assertOk()->assertSee('Hébergement');
        $this->get('/confidentialite')->assertOk()->assertSee('CNIL');
    }

    public function test_demo_page_creates_demo_data_lazily(): void
    {
        $this->assertNull(Demo::agent());

        $this->get('/demo')
            ->assertOk()
            ->assertSee('Réserver une visite')
            ->assertSee(config('immobooking.demo.email'));

        $agent = Demo::agent();
        $this->assertNotNull($agent);
        $this->assertSame(3, $agent->properties()->count());
        $this->assertGreaterThan(10, $agent->properties()->first()->slots()->getQuery()->available()->count());
    }

    public function test_demo_reset_wipes_visitor_data(): void
    {
        $agent = Demo::reset();
        $property = $agent->properties()->first();
        $before = Booking::count();
        Booking::factory()->create(['property_id' => $property->id, 'property_slot_id' => $property->slots()->first()->id]);

        Demo::reset();

        $this->assertSame($before, Booking::count());
        $this->assertSame(1, User::where('email', config('immobooking.demo.email'))->count());
    }

    public function test_demo_never_sends_notifications_but_shows_links(): void
    {
        $sent = [];
        Event::listen(NotificationSent::class, function ($e) use (&$sent) {
            $sent[] = $e->notification::class;
        });

        $property = Demo::reset()->properties()->where('booking_mode', 'OWNER_APPROVAL')->first();
        $slot = $property->slots()->getQuery()->available()->first();

        Livewire::test(PublicBooking::class, ['property' => $property])
            ->call('selectSlot', $slot->id)
            ->set('form.name', 'Visiteur Test')
            ->set('form.email', 'quelquun@example.com')
            ->set('form.phone', '06 12 34 56 78')
            ->set('form.employmentStatus', 'CDI')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSee('Démo : aucun e-mail')
            ->assertSee('accepter ou refuser la visite');

        $this->assertSame([], $sent, 'No notification may leave the server for demo data.');
    }

    public function test_real_agencies_still_receive_notifications_when_demo_is_on(): void
    {
        $booking = Booking::factory()->create();
        $event = new NotificationSending($booking->property->agent, new VisitRequested($booking), 'mail');

        $this->assertTrue((new BlockDemoNotifications)->handle($event));
    }

    public function test_demo_login_is_prefilled(): void
    {
        Demo::reset();

        Livewire::test(Login::class)
            ->assertSet('data.email', config('immobooking.demo.email'))
            ->call('authenticate')
            ->assertRedirect();

        $this->assertAuthenticatedAs(Demo::agent());
    }

    public function test_demo_user_cannot_change_profile(): void
    {
        $agent = Demo::reset();
        $this->actingAs($agent);

        Livewire::test(EditProfile::class)
            ->fillForm(['name' => 'Pirate', 'email' => 'pirate@example.com'])
            ->call('save');

        $this->assertSame(config('immobooking.demo.email'), $agent->fresh()->email);
    }
}
