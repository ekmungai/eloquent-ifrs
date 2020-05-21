<?php
/**
 * Eloquent IFRS Accounting
 *
 * @author    Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license   MIT
 */
namespace IFRS\Reports;

use IFRS\Models\Account;

class AgingSchedule
{

    /**
     * Aging Schedule accounts.
     *
     * @var array
     */
    public $accounts = [];

    /**
     * Aging Schedule balances.
     *
     * @var array
     */
    public $balances = [];


    /**
     * Aging Schedule brackets.
     *
     * @var array
     */
    public $brackets = [];

    /**
     * Get Aginng Schedule bracket the Transaction belongs to.
     *
     * @param int   $age
     * @param array $brackets
     *
     * @return string
     */
    private function getBracket($age, $brackets) : string
    {
        if (count($brackets) == 1) {
            return array_key_first($brackets);
        }else {
            $bracket_name = array_key_first($brackets);
            $bracket = array_shift($brackets);
            if ($age < $bracket) {
                return $bracket_name;
            }else{
                return $this->getBracket($age, $brackets);
            }
        }

    }
    /**
     * Agine Schedule for the account type as at the endDate.
     *
     * @param string $type
     * @param int    $currencyId
     * @param string $endDate
     */
    public function __construct(string $type = Account::RECEIVABLE, int $currencyId = null, string $endDate = null)
    {
        $this->brackets = config('ifrs')['aging_schedule_brackets'];

        $balances = array_fill_keys(array_keys($this->brackets), 0);

        foreach (Account::where("account_type",$type)->get() as $account){

            $account_balances = array_fill_keys(array_keys($this->brackets), 0);

            $schedule = new AccountSchedule($account->id, $currencyId, $endDate);
            $schedule->getTransactions();

            foreach ($schedule->transactions as $transaction){
                if ($transaction->unclearedAmount > 0) {

                    $bound = $this->getBracket($transaction->age, $this->brackets);

                    $balances[$bound] += $transaction->unclearedAmount;
                    $account_balances[$bound] += $transaction->unclearedAmount;
                }
            }
            if (array_sum($account_balances) > 0) {
                $account->balances = $account_balances;
                array_push($this->accounts, $account);
            }
        }

        $this->balances = $balances;
    }

    /**
     * Print Aging Schedule attributes.
     *
     * @return object
     */
    public function attributes()
    {
        return (object) [
            "Accounts" => $this->accounts,
            "Balances" => $this->balances,
            "Brackets" => $this->brackets
        ];
    }
}