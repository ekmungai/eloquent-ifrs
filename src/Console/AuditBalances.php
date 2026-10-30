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
use IFRS\Support\BalanceAudit;

class AuditBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ifrs:audit-balances
                            {--entity= : Only check the Entity with this id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List Balances and clearances that versions before 7.1.1 may have corrupted, without changing anything';

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

        $candidates = BalanceAudit::compoundedCandidates($entity);
        $overCleared = BalanceAudit::overCleared($entity);

        if ($candidates->isEmpty() && $overCleared->isEmpty()) {
            $this->info('Nothing to review.');

            return self::SUCCESS;
        }

        if ($candidates->isNotEmpty()) {
            $this->line('Foreign currency Balances saved after they were created. Check each against its source: a Balance that was re-entered may be correct.');
            $this->table(
                ['Balance', 'Entity', 'Account', 'Stored', 'Rate', 'Saved', 'Created'],
                $candidates->map(function ($balance) {
                    return [
                        $balance->id,
                        $balance->entity_id,
                        $balance->account_id,
                        $balance->balance,
                        $balance->exchangeRate->rate,
                        $balance->updated_at->toDateString(),
                        $balance->created_at->toDateString(),
                    ];
                })
            );
        }

        if ($overCleared->isNotEmpty()) {
            $this->line('Transactions and Balances cleared by more than their amount.');
            $this->table(
                ['Type', 'Id', 'Amount', 'Cleared'],
                $overCleared->map(function ($finding) {
                    return [$finding->type, $finding->id, $finding->amount, $finding->cleared];
                })
            );
        }

        $this->line('Nothing was changed.');

        return self::SUCCESS;
    }
}
