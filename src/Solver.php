<?php namespace dwalker109\Countdown;

class Solver
{
    // Class vars for state tracking
    private $numbers;
    private $target;
    private $rpn_equations;
    private $results;
    private $max_matches;
    private $timeout;


    public function __construct(Array $numbers, $target, $max_matches)
    {
        $this->numbers = $numbers;
        $this->target = $target;
        $this->max_matches = $max_matches;

        $this->timeout = null;
        $this->rpn_equations = [];
        $this->results = [];
    }


    /**
     * Solve the problem
     * @return Array|bool
     */
    public function run()
    {
        // Turn off PHP time limit
        set_time_limit(0);

        // Generate the RPN expressions to allow brute force calculations,
        // then run these calculations
        $this->buildRpnExpressions($this->numbers);
        $this->calculateResults();

        // Return the results, which will be boolean false if this timed out
        return $this->results;
    }


    /**
     * Check/set the timeout
     * @return boolean
     */
    private function timeout()
    {
        // If timeout is null, set it
        if ($this->timeout === null) {
            $this->timeout = strtotime('+30 seconds');
            return false;
        }

        // Check whether timeout occurred
        if ($this->timeout === true) {
            return true;
        }

        // If timeout is running, check it, invalidate results if timeout occurred, return status
        if ($this->timeout <= time()) {
            $this->timeout = true;
            $this->results = false;
            return true;
        }
    }


    /**
     * Calculate all available RPN equations and store the results
     */
    private function calculateResults()
    {
        foreach ($this->rpn_equations as $rpn) {
            // Check timeout
            if ($this->timeout()) {
                return;
            }

            // If this equation is a winner, store it
            if (Rpn::calculate($rpn) === $this->target) {
                $this->results[] = [
                    'rpn' => $rpn,
                    'ifx' => Rpn::ConvertRpnToIfx($rpn),
                ];
            }

            // Return after the maximum allowed matches
            if ($this->max_matches &&
                    count($this->results) > $this->max_matches) {
                return;
            }
        }
    }


    /**
    * Build RPN equation strings for all permutations of the source numbers
    * (adapted from a Java example from the URL below)
    * @link   http://stackoverflow.com/a/2394972
    * @param  Array $numbers
    * @param  int $level
    * @param  string $equation
    */
    private function buildRpnExpressions(Array $numbers, $level = 0, $equation = null)
    {
        // Check timeout
        if ($this->timeout()) {
            return;
        }

        // At least two operands deep, so iterate and append each
        // operator to this tree in a recursive call to this method
        if ($level >= 2) {
            foreach (Rpn::$operators as $operator) {
                $this->buildRpnExpressions($numbers, $level - 1, $equation . Rpn::OP_TOK . $operator);
            }
        }

        // Iterate all source numbers once per tree - pass by ref so the current number can
        // be nullifed prior to the pool of numbers being used in the recursive call
        $all_used = true;
        foreach ($numbers as &$number) {
            if ($number !== null) {
                // Flag that all numbers have not been used, then iterate further down this tree
                // after nullifying current number in the passed pool, while incrementing depth
                $all_used = false;
                $n = $number;
                $number = null;
                $this->buildRpnExpressions($numbers, $level + 1, $equation . Rpn::OP_TOK . $n);
                // Restore current number for use with next iteration's tree
                $number = $n;
            }
        }

        // No numbers were left this time around and level is 1, so this tree is complete - store it
        if ($all_used && $level == 1) {
            $this->rpn_equations[] = trim($equation);
        }
    }
}
