<?php

namespace Tests\Feature;

use App\Models\ClinicSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminScheduleTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin/schedule')->assertRedirect('/login');
    }

    public function test_patients_cannot_view_the_clinic_schedule(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/schedule')->assertForbidden();
    }

    public function test_patients_cannot_change_the_clinic_schedule(): void
    {
        $schedule = ClinicSchedule::factory()->create(['weekday' => Carbon::MONDAY, 'opens_at' => 900]);

        $this->actingAs(User::factory()->create())
            ->patch('/admin/schedule/'.$schedule->id, ['opens_at' => '08:00', 'closes_at' => '12:00'])
            ->assertForbidden();

        $this->assertSame(900, $schedule->fresh()->opens_at);
    }

    public function test_admins_see_a_row_for_every_weekday(): void
    {
        ClinicSchedule::factory()->create(['weekday' => Carbon::MONDAY, 'opens_at' => 900, 'closes_at' => 1700]);
        ClinicSchedule::factory()->closed()->create(['weekday' => Carbon::SUNDAY]);

        $this->actingAs($this->admin())->get('/admin/schedule')->assertOk()
            ->assertSeeText('Monday')
            ->assertSeeText('Sunday')
            ->assertSee('value="09:00"', false)
            ->assertSee('value="17:00"', false);
    }

    public function test_admins_can_change_a_weekdays_opening_hours(): void
    {
        $schedule = ClinicSchedule::factory()->create([
            'weekday' => Carbon::MONDAY, 'opens_at' => 900, 'closes_at' => 1700,
        ]);

        $this->actingAs($this->admin())
            ->patch('/admin/schedule/'.$schedule->id, ['opens_at' => '10:30', 'closes_at' => '15:00'])
            ->assertRedirect(route('admin.schedule'))
            ->assertSessionHas('status', 'Clinic schedule updated.');

        $schedule->refresh();
        $this->assertSame(1030, $schedule->opens_at);
        $this->assertSame(1500, $schedule->closes_at);
        $this->assertFalse($schedule->is_closed);
    }

    public function test_admins_can_close_a_weekday(): void
    {
        $schedule = ClinicSchedule::factory()->create(['weekday' => Carbon::MONDAY]);

        $this->actingAs($this->admin())->patch('/admin/schedule/'.$schedule->id, [
            'opens_at' => '09:00', 'closes_at' => '17:00', 'is_closed' => '1',
        ])->assertRedirect(route('admin.schedule'));

        $this->assertTrue($schedule->fresh()->is_closed);
    }

    public function test_closing_time_must_be_after_opening_time(): void
    {
        $schedule = ClinicSchedule::factory()->create([
            'weekday' => Carbon::MONDAY, 'opens_at' => 900, 'closes_at' => 1700,
        ]);

        $this->actingAs($this->admin())->from('/admin/schedule')
            ->patch('/admin/schedule/'.$schedule->id, ['opens_at' => '17:00', 'closes_at' => '09:00'])
            ->assertRedirect('/admin/schedule')
            ->assertSessionHasErrors('closes_at');

        $this->assertSame(900, $schedule->fresh()->opens_at);
    }

    public function test_opening_hours_must_be_valid_times(): void
    {
        $schedule = ClinicSchedule::factory()->create(['weekday' => Carbon::MONDAY, 'opens_at' => 900]);

        $this->actingAs($this->admin())->from('/admin/schedule')
            ->patch('/admin/schedule/'.$schedule->id, ['opens_at' => '25:00', 'closes_at' => '17:00'])
            ->assertSessionHasErrors('opens_at');

        $this->assertSame(900, $schedule->fresh()->opens_at);
    }
}
