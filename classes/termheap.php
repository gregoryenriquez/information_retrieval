<?php

namespace classes;


class TermHeap extends \SplMinHeap
{
    public function compare($term1, $term2)
    {

        $pos1 = explode(":", $term1["next_doc"]);
        $next_doc1 = $pos1[0];
        $pos2 = explode(":", $term2["next_doc"]);
        $next_doc2 = $pos2[0];

        if ($next_doc1 == $next_doc2) {
            return 0;
        }

        if ($next_doc1 == "INF") {
            return 1;
        } elseif ($next_doc2 == "INF") {
            return -1;
        }

        if ($next_doc1 < $next_doc2) {
            return 1;
        } else {
            return -1;
        }
    }
}