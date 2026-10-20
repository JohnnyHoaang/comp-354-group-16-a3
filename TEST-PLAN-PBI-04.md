# Test Plan — PBI-04: Outdated appointment availability

**Feature under test:** appointment availability search (`GET /book/new`) and booking
confirmation (`POST /book/new`).

**Objective:** demonstrate that a patient always sees current availability, that a booking
which is no longer possible is refused with a clear reason, and that automated tests
cover both.

## 1. The defect being validated

Before the fix, the availability grid in `resources/views/bookings/create.blade.php` was
hardcoded — a loop printed all 24 hours as "Available" and never consulted the database
(the view's own comment said so). `BookingController@create` computed nothing, and
`BookingController@store` validated only the *format* of the date and time before
inserting.

So the three observable symptoms were:

| # | Symptom | Now expected |
| --- | --- | --- |
| D1 | Every hour 00:00–23:00 showed as available, on every date | Only slots inside the clinic's opening hours appear |
| D2 | A booked slot still showed as available to the next searcher | A booked slot shows as "Booked" and is not selectable |
| D3 | Confirming an already-taken slot silently created a double booking | Confirmation is refused with a message naming the reason |

## 2. Acceptance criteria and traceability

| AC | Criterion | Manual cases | Automated tests |
| --- | --- | --- | --- |
| AC1 | New searches reflect bookings, cancellations, and clinic schedule changes | TC-01 … TC-06, TC-12 | `Feature/AvailabilitySearchTest`, `Unit/ClinicScheduleTest` |
| AC2 | Booking rechecks availability and clearly rejects slots that are no longer available | TC-07 … TC-11 | `Feature/ConfirmBookingTest` |
| AC3 | Automated regression tests cover these scenarios and pass | TC-13 | Whole suite — 89 tests, 280 assertions |

## 3. Test environment

| Item | Value |
| --- | --- |
| Build | Branch `fix/PBI-04-fix-outdated-availability-search` |
| Database | SQLite, reset before the run |
| Accounts | `user1@example.com`, `user2@example.com`, `admin@example.com` — all with password `password` |
| Seeded hours | Monday–Friday 09:00–17:00 open; Saturday and Sunday closed |
| Browsers | One normal window plus one private window, so two users can be signed in at once |

### Setup, run before every manual pass

```sh
php artisan migrate:fresh --seed
composer run dev
```

`migrate:fresh --seed` leaves **zero bookings** (`DatabaseSeeder` does not call
`BookingSeeder`), which is the clean starting state these cases assume.

### Fixed dates used below

| Placeholder | Date | Weekday | Why |
| --- | --- | --- | --- |
| **OPEN-DAY** | 2026-10-26 | Monday | A seeded open weekday |
| **CLOSED-DAY** | 2026-10-24 | Saturday | A seeded closed weekday |

If you run this plan after those dates have passed, substitute the next Monday and the
next Saturday. Any open weekday works; the dates only need to be in the future so the
booking also appears in *My bookings*.

## 4. Automated regression suite (AC3)

```sh
php artisan test --compact
```

**Expected:** `Tests: 89 passed (280 assertions)`, zero failures, zero errors.

To run only the suites written for this PBI:

```sh
php artisan test tests/Feature/AvailabilitySearchTest.php \
                 tests/Feature/ConfirmBookingTest.php \
                 tests/Unit/ClinicScheduleTest.php
```

The three staleness paths each have a dedicated test that asserts the **old** result
first and the **new** result after the change, so a regression to cached or hardcoded
availability fails them:

| Scenario | Test |
| --- | --- |
| Cancellation frees the slot | `test_a_new_search_frees_a_slot_after_the_booking_is_cancelled` |
| Schedule hours edited | `test_a_new_search_reflects_an_updated_clinic_schedule` |
| Weekday closed | `test_a_new_search_reflects_the_clinic_becoming_closed_that_day` |

**Suggested negative control.** To prove the suite would actually catch the original bug,
revert `BookingController@create` to pass no availability (or restore the hardcoded 24-hour
loop in the view) and re-run — `AvailabilitySearchTest` must fail. Undo afterwards. This
is optional but it is the strongest single piece of evidence that AC3 is met.

## 5. Manual test cases

Each case lists its own precondition. Unless stated otherwise, sign in as
`user1@example.com` and start from `/book/new`.

---

### TC-01 — Search shows only the clinic's opening hours (AC1, covers D1)

**Precondition:** fresh seed, no bookings.

1. Sign in as `user1@example.com`.
2. Go to `/book/new`.
3. Enter **OPEN-DAY** and press **Show availability**.

**Expected**
- Header reads `Availability for 2026-10-26 — 09:00–17:00`.
- Exactly **8** slot columns: 09:00, 10:00, 11:00, 12:00, 13:00, 14:00, 15:00, 16:00.
- Every one reads **Available**.
- **No 17:00 column** — that slot would end at 18:00, after closing.
- No 00:00–08:00 and no 18:00–23:00 columns.

**Fails if** 24 hourly columns appear — that is the original defect.

---

### TC-02 — A booking disappears from the next search (AC1, covers D2)

**Precondition:** TC-01 complete.

1. On the **OPEN-DAY** availability page, click the **11:00** button, then press
   **Confirm one-hour booking**.
2. Confirm the success banner reads `Your one-hour booking is confirmed.` on *My bookings*,
   and the booking is listed as 11:00, 60 minutes, Confirmed.
3. Navigate back to `/book/new` and search **OPEN-DAY** again.

**Expected**
- The 11:00 column now reads **Booked** and is plain text — there is no button to click.
- The other seven slots still read Available.

---

### TC-03 — An admin cancellation returns the slot to the next search (AC1)

**Precondition:** TC-02 complete, so `user1` holds the 11:00 booking.

1. In a private window, sign in as `admin@example.com`.
2. Go to `/admin/bookings`, search `user1@example.com`.
3. Delete the **OPEN-DAY 11:00** booking. Expect the banner `Booking deleted.`
4. Back in the first window as `user1`, search **OPEN-DAY** again.

**Expected**
- 11:00 reads **Available** again and is clickable.
- No cache clear, no restart, no logout was needed.

---

### TC-04 — Changing opening hours changes the next search (AC1)

**Precondition:** no bookings on OPEN-DAY.

1. As **admin**, go to `/admin/schedule`.
2. On **Monday**, set Opens `13:00` and Closes `16:00`, then **Save Monday**. Expect
   `Clinic schedule updated.`
3. As **user1**, search **OPEN-DAY** again.

**Expected**
- Header reads `Availability for 2026-10-26 — 13:00–16:00`.
- Exactly three slots: 13:00, 14:00, 15:00.
- 09:00–12:00 are gone entirely.

**Teardown:** set Monday back to 09:00 / 17:00.

---

### TC-05 — Closing a weekday empties the search (AC1)

1. As **admin** on `/admin/schedule`, tick **Closed all day** for Monday and save.
2. As **user1**, search **OPEN-DAY**.

**Expected**
- The page reads `The clinic is closed on 2026-10-26.`
- No availability table and no booking form are rendered.

Then confirm the already-closed weekend behaves the same: search **CLOSED-DAY** and
expect `The clinic is closed on 2026-10-24.`

**Teardown:** untick **Closed all day** for Monday and save.

---

### TC-06 — A booking that starts mid-slot blocks both slots it touches (AC1)

This checks the overlap rule rather than exact-time matching.

1. As **user1**, go to `/book/playground` and press **Add two mock bookings**. This creates
   a Dr. Alex Smith booking **tomorrow at 09:30**.
2. Search tomorrow's date. *(If tomorrow is a Saturday or Sunday, first open that weekday
   on `/admin/schedule`, or use the tinker alternative below.)*

**Expected**
- Both **09:00** and **10:00** read **Booked** — the 09:30–10:30 appointment overlaps both.
- 11:00 onward remain Available.

**Tinker alternative**, to place the overlapping booking on OPEN-DAY instead:

```sh
php artisan tinker --execute 'App\Models\User::where("email","user1@example.com")->sole()->bookings()->create(["doctor_full_name" => App\Models\Booking::DOCTOR_FULL_NAME, "date" => "2026-10-26", "start_time" => 930, "confirmed" => true]);'
```

**Teardown:** `/book/playground` → **Clear all my bookings**.

---

### TC-07 — A slot taken after the page loaded is rejected (AC2, covers D3)

This is the core acceptance test for AC2. It reproduces the exact race the PBI describes:
a patient searches, someone else books the slot, the first patient then confirms.

**Precondition:** no bookings on OPEN-DAY.

1. **Window A** — signed in as `user1`. Search **OPEN-DAY**. Confirm 10:00 shows Available.
   **Do not refresh this page for the rest of the case.**
2. **Window B** (private) — sign in as `user2@example.com`. Search **OPEN-DAY**, book
   **10:00**, and confirm it succeeds.
3. **Window A** — on the still-open, now-stale page, click **10:00** and press
   **Confirm one-hour booking**.

**Expected**
- Window A returns to the availability page for OPEN-DAY.
- An error is displayed:
  `That time slot is no longer available. Please choose another time.`
- The refreshed page now shows 10:00 as **Booked**.
- `user1` has **no** booking at 10:00 — check *My bookings*.
- `user2` still holds exactly one booking at 10:00.

**Fails if** the booking is accepted — that is the unguarded-write defect.

---

### TC-08 — A time outside opening hours is rejected (AC2)

**Precondition:** Monday hours are 09:00–17:00.

1. As `user1`, search **OPEN-DAY**.
2. Type `20:00` directly into the **Start time** field and confirm.

**Expected**
- Error: `The clinic does not offer that time slot on the selected date.`
- No booking is created.

---

### TC-09 — A time that is not on the hour is rejected (AC2)

1. As `user1`, search **OPEN-DAY**.
2. Type `09:30` into **Start time** and confirm.

**Expected**
- Error: `The clinic does not offer that time slot on the selected date.`
- No booking is created. Slots start on the hour only.

---

### TC-10 — A booking on a closed day is rejected (AC2)

1. As `user1`, search **CLOSED-DAY** (Saturday). The page says the clinic is closed and
   shows no form, so submit the request directly — for example re-use a booking form from
   an open day with the date field edited via browser dev tools, or run:

```sh
php artisan test --filter=test_a_booking_on_a_closed_day_is_rejected
```

**Expected**
- Error: `The clinic does not offer that time slot on the selected date.`
- No booking is created.

---

### TC-11 — The same patient cannot book one slot twice (AC2)

1. As `user1`, book **14:00** on **OPEN-DAY**.
2. Search **OPEN-DAY** again, type `14:00` into Start time, and confirm.

**Expected**
- Error: `That time slot is no longer available. Please choose another time.`
- *My bookings* shows exactly one 14:00 booking.

---

### TC-12 — A fully booked day reports itself (AC1)

1. As **admin**, set Monday to Opens `09:00`, Closes `11:00` — two slots.
2. As `user1`, book **09:00**, then book **10:00**.
3. Search **OPEN-DAY** again.

**Expected**
- The page reads `No times are still available on 2026-10-26.`
- Both columns read **Booked**.
- The confirmation form is not rendered, because nothing can be booked.

**Teardown:** restore Monday to 09:00 / 17:00 and clear the bookings.

---

### TC-13 — Automated regression suite passes (AC3)

1. Run `php artisan test --compact`.
2. Run `vendor/bin/pint --test` to confirm formatting is clean.
