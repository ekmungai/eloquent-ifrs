<?php

/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */

namespace IFRS\Console;

use Illuminate\Console\Command;

use IFRS\Models\Entity;
use IFRS\Support\MisattachedBalances;

class RepairBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ifrs:repair-balances
                            {--entity= : Only check the Entity with this id}
                            {--apply : Move the Balances instead of only listing them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List, and optionally move, Balances attached to the wrong Reporting Period before 7.0.0';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $entity = null;

        if (!is_null($this->option('entity'))) {
            $entity = Entity::find($this->option('entity'));

            if (is_null($entity)) {
                $this->error('There is no Entity with the id ' . $this->option('entity'));

                return self::FAILURE;
            }
        }

        $findings = MisattachedBalances::find($entity);

        if ($findings->isEmpty()) {
            $this->info('No misattached Balances found.');

            return self::SUCCESS;
        }

        $this->table(
            ['Balance', 'Entity', 'Account', 'Amount', 'Saved', 'From', 'To', 'Blocked'],
            $findings->map(function ($finding) {
                return [
                    $finding->balance->id,
                    $finding->balance->entity_id,
                    $finding->balance->account_id,
                    $finding->balance->balance,
                    $finding->balance->created_at->toDateString(),
                    $finding->from->calendar_year,
                    $finding->from->calendar_year - 1,
                    $finding->blocked ?? '',
                ];
            })
        );

        if (!$this->option('apply')) {
            $this->line('Nothing was changed. Check that none of these Balances were given their period on purpose, then run again with --apply.');

            return self::SUCCESS;
        }

        $moved = MisattachedBalances::repair($findings);

        $this->info($moved . ' of ' . $findings->count() . ' Balances moved. Blocked Balances were left as they are.');

        return self::SUCCESS;
    }
}
