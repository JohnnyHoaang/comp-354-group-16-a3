# MediLink — Developer Reference

A clinic appointment app: patients search a date for free one-hour slots and book one;
admins manage opening hours and cancel bookings.

Server-rendered Blade with session auth. No API, no JavaScript framework.

## Routes

Middleware is listed per route. `auth` redirects guests to `/login`, `guest` redirects
signed-in users to `/home`, and `admin` aborts with 403.

### Authentication and entry

| Verb | Path | Name | Handler | Middleware |
| --- | --- | --- | --- | --- |
| ANY | `/` | — | `Route::redirect` | — |
| GET | `/login` | `login` | view: `login` | `guest` |
| POST | `/login` | `login.store` | `LoginController@store` | `guest`, `throttle:5,1` |
| POST | `/logout` | `logout` | `LoginController@destroy` | `auth` |
| GET | `/up` | — | framework health check | — |

- `/` permanently redirects to `/home`.
- `login.store` attempts the credentials, regenerates the session, and redirects to
  `/home`. On failure it adds "The email or password is incorrect." to the `email` error
  key — the same message for a wrong email and a wrong password, so the form never reveals
  which half was wrong.
- `logout` logs out, invalidates the session, and regenerates the CSRF token.

### Patient

All require `auth`.

| Verb | Path | Name | Handler |
| --- | --- | --- | --- |
| GET | `/home` | `home` | `HomeController@index` |
| GET | `/book/list` | `bookings.index` | `BookingController@index` |
| GET | `/book/new` | `bookings.create` | `BookingController@create` |
| POST | `/book/new` | `bookings.store` | `BookingController@store` |

- **`/home`** shows the signed-in email and role, plus a reminder callout for the user's
  own bookings starting within the next 48 hours.
- **`/book/list`** lists the user's own bookings from today onward, ordered by date then
  start time.
- **`/book/new`** is the **availability search**. With no `?date=` it shows only step 1.
  With a date it resolves that weekday's schedule and renders each slot as available or
  booked. Closed and unscheduled days say so; a fully booked day says so.
- **`POST /book/new`** **confirms** a booking. It re-checks availability inside a
  transaction, then creates a confirmed 60-minute booking owned by the current user.
  Rejections redirect back with an error on `start_time`.

### Admin

All require `auth` and `admin`.

| Verb | Path | Name | Handler |
| --- | --- | --- | --- |
| GET | `/admin` | `admin` | view: `admin` |
| GET | `/admin/bookings` | `admin.bookings` | `AdminBookingController@index` |
| DELETE | `/admin/users/{user}/bookings/{booking}` | `admin.bookings.destroy` | `AdminBookingController@destroy` |
| GET | `/admin/schedule` | `admin.schedule` | `AdminScheduleController@index` |
| PATCH | `/admin/schedule/{clinicSchedule}` | `admin.schedule.update` | `AdminScheduleController@update` |

- **`/admin/bookings`** looks a patient up by exact email (`?email=`) and lists all their
  bookings with a delete button each. Without the parameter it shows just the search form.
- **`admin.bookings.destroy`** cancels one booking. The booking is looked up *through* the
  user relation, so a mismatched pair 404s. The freed slot shows as available on the next
  search.
- **`/admin/schedule`** renders one form per weekday, Sunday to Saturday, with opening
  time, closing time, and a "closed all day" checkbox.
- **`admin.schedule.update`** saves one weekday. Times arrive as `H:i` and are stored as
  clock integers. The change takes effect on the next patient search.


### `users`

| Column | Type | Notes |
| --- | --- | --- |
| `id` | bigint pk | |
| `name` | string | |
| `email` | string unique | The login identifier, and how admins look patients up |
| `password` | string | Cast `hashed`, so plain text assigned to it is hashed on save |
| `role` | enum | `user` or `admin`, default `user` |

### `bookings`

| Column | Type | Notes |
| --- | --- | --- |
| `id` | bigint pk | |
| `user_id` | fk nullable | Owner. `restrictOnDelete`, so a user with bookings cannot be deleted |
| `doctor_full_name` | string | Always "Dr. Alex Smith" for real bookings; availability filters on it |
| `date` | date | Cast to Carbon |
| `start_time` | integer | Clock integer — `905` is 09:05 |
| `duration_minutes` | smallint | Default 60; not mass-assignable, so bookings are always one hour |
| `confirmed` | boolean | Column default `false`, but the booking flow always sets `true` |

### `clinic_schedules`

| Column | Type | Notes |
| --- | --- | --- |
| `id` | bigint pk | |
| `weekday` | tinyint unique | `0` Sunday … `6` Saturday. One row per weekday |
| `opens_at` | smallint | Clock integer, default `900` |
| `closes_at` | smallint | Clock integer, default `1700`. Exclusive — the last slot must end by this time |
| `is_closed` | boolean | Closes the weekday without discarding its hours |

**`App\Models\Booking`**

| Member | Purpose |
| --- | --- |
| `DOCTOR_FULL_NAME` | The only bookable doctor |
| `DEFAULT_DURATION_MINUTES` | 60, mirroring the column default |
| `onClinicDay($date)` | Scope: that date's bookings for the bookable doctor |
| `startMinutes()` / `endMinutes()` | Minutes past midnight |
| `overlapsMinutes($from, $to)` | Half-open overlap test |
| `user()` | `belongsTo` User |

**`App\Models\ClinicSchedule`**

| Member | Purpose |
| --- | --- |
| `SLOT_MINUTES` | Slot length, 60 |
| `forDate($date)` | The schedule governing a date, or `null` |
| `isOpen()` | False when closed or hours are inverted |
| `slotStartTimes()` | Clock integers for every offered slot |
| `offersSlot($startTime)` | Is this a legitimate slot start? |
| `availabilityOn($date)` | Slots with `is_available` flags, for the search view |
| `isSlotFreeOn($date, $startTime)` | Booking-level check used by confirm |
| `openingHours()` / `weekdayName()` | Display helpers |

**`App\Models\User`** — `bookings()` is `hasMany Booking` and the only way the app creates
bookings, so ownership is automatic.

**`App\Models\Concerns\HasClockTimes`** — `minutesFromClock(930)` is 570,
`clockFromMinutes(570)` is `930`, `formatClock(930)` is `"09:30"`.

## Running it locally

```sh
composer install
npm install
php artisan migrate --seed

# serve app + vite together
composer run dev

# or separately
php artisan serve
npm run dev
```

Seeding runs `UserSeeder`, `AdminSeeder` and `ClinicScheduleSeeder`. The clinic seeder is
idempotent: it opens Monday to Friday 09:00–17:00 and closes both weekend days.

### Seeded accounts

All seeded passwords are `password`.

| Email | Role | Use it for |
| --- | --- | --- |
| `admin@example.com` | admin | The admin area: booking management and the clinic schedule |
| `user1@example.com` | user | The patient flow |
| `user2@example.com` | user | A second patient, to check that one patient's booking blocks another's slot |
