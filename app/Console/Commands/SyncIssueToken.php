<?php

namespace App\Console\Commands;

use App\Services\Sync\SyncTokenIssuer;
use Illuminate\Console\Command;

class SyncIssueToken extends Command
{
    protected $signature = 'sync:issue-token
        {school_number : The school_number this device will sync data for}
        {device_name : A label for this install, e.g. "St. Mary\'s HQ Laptop"}
        {--role=school : school or central}';

    protected $description = 'Issue a new sync token for a school/office install (run this on the CENTRAL server)';

    public function handle(SyncTokenIssuer $issuer): int
    {
        $schoolNumber = $this->argument('school_number');
        $deviceName = $this->argument('device_name');
        $role = $this->option('role');

        $result = $issuer->issue($schoolNumber, $deviceName, $role);
        $token = $result['plain_token'];

        $this->info('Sync token created. Copy this into the school install\'s .env file — it will NOT be shown again:');
        $this->newLine();
        $this->line("SYNC_ROLE={$role}");
        $this->line("SYNC_CENTRAL_URL=https://your-central-domain.example");
        $this->line("SYNC_SCHOOL_NUMBER={$schoolNumber}");
        $this->line("SYNC_DEVICE_NAME=\"{$deviceName}\"");
        $this->line("SYNC_TOKEN={$token}");
        $this->newLine();
        $this->comment('Tip: this can also be done from the browser now at /sync/tokens.');

        return self::SUCCESS;
    }
}
