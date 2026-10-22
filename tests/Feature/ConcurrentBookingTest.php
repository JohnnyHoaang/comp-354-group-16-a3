<?php

namespace Tests\Feature;

use PDO;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ConcurrentBookingTest extends TestCase
{
    public function test_only_one_concurrent_booking_succeeds(): void
    {
        // Create a separate SQLite database for this test.
        $database = tempnam(sys_get_temp_dir(), 'medilink_');

        try {
            $pdo = new PDO('sqlite:'.$database);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $pdo->exec('
                CREATE TABLE users (
                    id INTEGER PRIMARY KEY,
                    name TEXT,
                    email TEXT,
                    password TEXT
                )
            ');

            $pdo->exec('
                CREATE TABLE bookings (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    doctor_full_name TEXT NOT NULL,
                    date TEXT NOT NULL,
                    start_time INTEGER NOT NULL,
                    duration_minutes INTEGER NOT NULL DEFAULT 60,
                    confirmed INTEGER NOT NULL DEFAULT 0,
                    created_at TEXT,
                    updated_at TEXT
                )
            ');

            $pdo->exec('
    CREATE TABLE clinic_schedules (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        weekday INTEGER NOT NULL UNIQUE,
        opens_at INTEGER NOT NULL,
        closes_at INTEGER NOT NULL,
        is_closed INTEGER NOT NULL DEFAULT 0,
        created_at TEXT,
        updated_at TEXT
    )
');

            $pdo->exec('
    INSERT INTO clinic_schedules
        (weekday, opens_at, closes_at, is_closed)
    VALUES
        (0, 900, 1200, 0)
');

            $pdo->exec("
                INSERT INTO users (id, name, email, password)
                VALUES
                (1, 'Patient A', 'patient1@test.com', 'test'),
                (2, 'Patient B', 'patient2@test.com', 'test')
            ");

            // Start both workers at approximately the same time.
            $startAt = (string) (microtime(true) + 2);

            $worker = base_path('tests/Support/booking_worker.php');

            $process1 = new Process([
                PHP_BINARY, $worker, $database, '1', $startAt,
            ]);

            $process2 = new Process([
                PHP_BINARY, $worker, $database, '2', $startAt,
            ]);

            $process1->setTimeout(15);
            $process2->setTimeout(15);

            $process1->start();
            $process2->start();

            $process1->wait();
            $process2->wait();

            // Both processes should finish without an unexpected error.
            $this->assertTrue(
                $process1->isSuccessful(),
                $process1->getErrorOutput()
            );

            $this->assertTrue(
                $process2->isSuccessful(),
                $process2->getErrorOutput()
            );
            $results = [
                trim($process1->getOutput()),
                trim($process2->getOutput()),
            ];

            sort($results);

            // Exactly one succeeds; the other receives a conflict.
            $this->assertSame(
                ['CONFLICT', 'SUCCESS'],
                $results
            );

            // Only one booking should exist.
            $count = (int) $pdo
                ->query('SELECT COUNT(*) FROM bookings')
                ->fetchColumn();

            $this->assertSame(1, $count);

        } finally {
            $pdo = null;
            if (is_file($database)) {
                unlink($database);
            }
        }
    }
}
