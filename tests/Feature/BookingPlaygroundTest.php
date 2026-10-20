<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BookingPlaygroundTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_cannot_access_the_playground_or_its_actions(): void
    {
        $this->get('/book/playground')->assertRedirect('/login');
        $this->post('/book/playground/add')->assertRedirect('/login');
        $this->post('/book/playground/clear')->assertRedirect('/login');
    }

    public function test_authenticated_users_can_view_the_playground(): void
    {
        $this->actingAs(User::factory()->create())->get('/book/playground')
            ->assertOk()->assertSeeText('Add two mock bookings')->assertSeeText('Clear all my bookings');
    }

    public function test_mock_bookings_are_added_only_to_the_current_user(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 1)->startOfDay());
        $user = User::factory()->create();
        $otherBooking = Booking::factory()->create();

        $this->actingAs($user)->post('/book/playground/add')->assertRedirect('/book/playground');

        $this->assertCount(2, $user->bookings);
        $bookings = $user->bookings()->orderBy('date')->get();
        $this->assertSame('2026-11-02', $bookings[0]->date->format('Y-m-d'));
        $this->assertSame('2026-11-03', $bookings[1]->date->format('Y-m-d'));
        $this->assertDatabaseHas('bookings', [
            'user_id' => $user->id, 'doctor_full_name' => 'Dr. Alex Smith',
            'start_time' => 930, 'confirmed' => false,
        ]);
        $this->assertDatabaseHas('bookings', [
            'user_id' => $user->id, 'doctor_full_name' => 'Dr. Jordan Lee',
            'start_time' => 1430, 'confirmed' => true,
        ]);
        $this->assertModelExists($otherBooking);
        $this->assertDatabaseCount('bookings', 3);
    }

    public function test_clear_deletes_only_the_current_users_bookings(): void
    {
        $user = User::factory()->create();
        Booking::factory()->for($user)->count(2)->create();
        $otherBooking = Booking::factory()->create();

        $this->actingAs($user)->post('/book/playground/clear')->assertRedirect('/book/playground');

        $this->assertDatabaseMissing('bookings', ['user_id' => $user->id]);
        $this->assertModelExists($otherBooking);
    }
}
