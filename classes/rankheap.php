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
        if ($score1 > $score2) {
            return 1;
        } else {
            return -1;
        }
    }

    public function printHeap() 
    {   
        $i = 0;
        while ($this->valid()) {
            $i++;
            $node = $this->current();
            $doc_id = $node["doc_id"];
            $score = $node["score"];
            print("Rank #$i Doc ID: $doc_id, Score: $score\n");
            $this->next();
        }
    }
}