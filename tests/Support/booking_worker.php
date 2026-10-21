<?php

require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';

$app->make(
    Illuminate\Contracts\Console\Kernel::class
)->bootstrap();

use App\Models\User;
use App\Services\BookingReservationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
config()->set('database.default', 'sqlite');
config()->set('database.connections.sqlite.database', $argv[1]);

DB::purge('sqlite');

$userId = (int) $argv[2];
$startAt = (float) $argv[3];

while (microtime(true) < $startAt) {
    usleep(1000);
}

try {
    $user = User::findOrFail($userId);

    app(BookingReservationService::class)->reserve(
        $user,
        '2026-11-01',
        '09:30'
    );

    echo "SUCCESS\n";
} catch (ValidationException $e) {
    echo "CONFLICT\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
