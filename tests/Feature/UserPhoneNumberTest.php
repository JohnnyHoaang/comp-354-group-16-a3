<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UserPhoneNumberTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_users_can_exist_without_a_phone_number(): void
    {
        $user = User::factory()->create();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'phone' => null,
        ]);
    }
}
