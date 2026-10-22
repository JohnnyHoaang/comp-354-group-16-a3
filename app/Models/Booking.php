<?php

namespace App\Models;

use App\Models\Concerns\HasClockTimes;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['doctor_full_name', 'start_time', 'date', 'confirmed'])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasClockTimes, HasFactory;

    /**
     * The only doctor patients can currently book with.
     */
    public const DOCTOR_FULL_NAME = 'Dr. Alex Smith';

    /**
     * Length of a booking when none is stored, mirroring the column default.
     */
    public const DEFAULT_DURATION_MINUTES = 60;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Bookings that occupy the bookable doctor's calendar on the given date.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function onClinicDay(Builder $query, Carbon|string $date): Builder
    {
        return $query->whereDate('date', $date)
            ->where('doctor_full_name', self::DOCTOR_FULL_NAME);
    }

    /**
     * Minutes past midnight at which this booking starts.
     */
    public function startMinutes(): int
    {
        return self::minutesFromClock($this->start_time);
    }

    /**
     * Minutes past midnight at which this booking ends.
     */
    public function endMinutes(): int
    {
        return $this->startMinutes() + ($this->duration_minutes ?? self::DEFAULT_DURATION_MINUTES);
    }

    /**
     * Whether this booking covers any part of the given half-open minute range.
     */
    public function overlapsMinutes(int $startMinutes, int $endMinutes): bool
    {
        return $this->startMinutes() < $endMinutes && $startMinutes < $this->endMinutes();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_time' => 'integer',
            'duration_minutes' => 'integer',
            'date' => 'date',
            'confirmed' => 'boolean',
        ];
    }
}
