<?php

namespace App\Models;

use App\Models\Concerns\HasClockTimes;
use Database\Factories\ClinicScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The clinic's opening hours for one weekday, which decide the bookable slots on any date.
 */
#[Fillable(['weekday', 'opens_at', 'closes_at', 'is_closed'])]
class ClinicSchedule extends Model
{
    /** @use HasFactory<ClinicScheduleFactory> */
    use HasClockTimes, HasFactory;

    /**
     * Length of every bookable slot, in minutes.
     */
    public const SLOT_MINUTES = Booking::DEFAULT_DURATION_MINUTES;

    /**
     * The schedule that governs the given date, or null when the clinic has no hours for it.
     */
    public static function forDate(Carbon|string $date): ?self
    {
        return static::query()
            ->where('weekday', Carbon::parse($date)->dayOfWeek)
            ->first();
    }

    /**
     * Whether the clinic takes any bookings on this weekday.
     */
    public function isOpen(): bool
    {
        return ! $this->is_closed && $this->closes_at > $this->opens_at;
    }

    /**
     * Start times of every slot the clinic offers on this weekday.
     *
     * @return list<int>
     */
    public function slotStartTimes(): array
    {
        if (! $this->isOpen()) {
            return [];
        }

        $slotStartTimes = [];
        $closingMinutes = self::minutesFromClock($this->closes_at);

        for (
            $startMinutes = self::minutesFromClock($this->opens_at);
            $startMinutes + self::SLOT_MINUTES <= $closingMinutes;
            $startMinutes += self::SLOT_MINUTES
        ) {
            $slotStartTimes[] = self::clockFromMinutes($startMinutes);
        }

        return $slotStartTimes;
    }

    /**
     * Whether the clinic's hours include a slot beginning at the given clock integer.
     */
    public function offersSlot(int $startTime): bool
    {
        return in_array($startTime, $this->slotStartTimes(), true);
    }

    /**
     * Every offered slot on the given date, flagged with its current availability.
     *
     * @return list<array{start_time: int, label: string, is_available: bool}>
     */
    public function availabilityOn(Carbon|string $date): array
    {
        $bookings = $this->bookingsOn($date);

        return array_map(fn (int $startTime): array => [
            'start_time' => $startTime,
            'label' => self::formatClock($startTime),
            'is_available' => ! $this->isSlotTaken($bookings, $startTime),
        ], $this->slotStartTimes());
    }

    /**
     * Whether no booking yet covers the slot beginning at the given clock integer.
     */
    public function isSlotFreeOn(Carbon|string $date, int $startTime): bool
    {
        return ! $this->isSlotTaken($this->bookingsOn($date), $startTime);
    }

    /**
     * The clinic's opening hours as a 24-hour range such as `09:00–17:00`.
     */
    public function openingHours(): string
    {
        return self::formatClock($this->opens_at).'–'.self::formatClock($this->closes_at);
    }

    /**
     * The English name of the weekday this schedule applies to.
     */
    public function weekdayName(): string
    {
        return Carbon::create(2024, 1, 7)->addDays($this->weekday)->dayName;
    }

    /**
     * @return Collection<int, Booking>
     */
    private function bookingsOn(Carbon|string $date): Collection
    {
        return Booking::onClinicDay($date)->get();
    }

    /**
     * @param  Collection<int, Booking>  $bookings
     */
    private function isSlotTaken(Collection $bookings, int $startTime): bool
    {
        $startMinutes = self::minutesFromClock($startTime);

        return $bookings->contains(
            fn (Booking $booking): bool => $booking->overlapsMinutes($startMinutes, $startMinutes + self::SLOT_MINUTES)
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'opens_at' => 'integer',
            'closes_at' => 'integer',
            'is_closed' => 'boolean',
        ];
    }
}
