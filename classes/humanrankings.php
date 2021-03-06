<?php

namespace classes;

class HumanRankings
{
    public $rankings;

    public function __construct() {
        // google's ranking
        // $this->rankings["clashroyale"] =  array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20);
        // $this->rankings["election"] =     array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20);
        // $this->rankings["investment"] =   array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20);
        // $this->rankings["masseffect"] =   array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20);
        // $this->rankings["tv"] =           array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20);       

        // my ranking
        $this->rankings["clashroyale"] =  array(18, 8, 19, 20, 15, 1, 16, 9, 2, 7, 10, 12, 11, 3, 13, 4, 5, 14, 17, 6);
        $this->rankings["election"] =     array(1, 11, 7, 10, 9, 8, 12, 2, 3, 13, 14, 4, 5, 15, 16, 17, 18, 6, 19, 20);
        $this->rankings["investment"] =   array(16, 1, 7, 4, 5, 6, 2, 14, 15, 8, 9, 10, 11, 13, 12, 20, 17, 3, 18, 19);
        $this->rankings["masseffect"] =   array(13, 12, 1, 2, 11, 3, 4, 6, 5, 14, 15, 16, 7, 20, 8, 17, 18, 9, 19, 10);
        $this->rankings["tv"] =           array(1, 2, 9, 3, 4, 14, 18, 13, 12, 5, 6, 7, 15, 11, 8, 10, 17, 20, 16, 19);

        $this->rankings["test_single_folder"] =          array(1);    
    }

    public function getRanks($dir_base_name)
    {
        if ($this->rankings[$dir_base_name] == null) {
            print("ERR: Human ranking for $dir_base_name does not exist");
        } else {
            return $this->rankings[$dir_base_name];
        }
    }

    public function computePiScore($human_ranking, $computer_ranking) {
        $score = 0;
        $computer_ranking_length = count($computer_ranking);
        foreach ($computer_ranking as $cmp_rank) {
            $cmp_score = $cmp_rank["score"];
            $cmp_doc = $cmp_rank["doc_id"];
            $cmp_rank = $cmp_rank["rank"];
            $score += abs($human_ranking[intval($doc_id)] - $cmp_score);
        }
        return $score;
    }
}