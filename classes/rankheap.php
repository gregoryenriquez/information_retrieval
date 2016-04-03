<?php

namespace classes;

// require_once("SplMaxHeap");

class RankHeap extends \SplMaxHeap
{
    public function compare($result1, $result2)
    {
        $score1 = $result1["score"];
        $score2 = $result2["score"];

        if ($score1 == $score2) {
            return 0;
        }

        print("score1: $score1 score2: $score2\n");
        if ($score1 > $score2) {
            print("returning 1\n");
            return 1;
        } else {
            print("returning -1\n");
            return -1;
        }
    }
}