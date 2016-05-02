<?php

namespace classes;

class RankBM25 
{

    public $results;
    public $terms;
    public $num_of_docs;
    public $term_idfs;

    public function __construct(&$inverted_index) {
        $this->term_idfs = array();
        $this->results = new RankHeap();
        $this->terms = new TermHeap();
        $this->num_of_docs = $inverted_index->num_of_docs;
    }

    public function rankBM25WithHeaps($terms, $k, &$inverted_index)
    {
        foreach ($terms as $term) {
            $temp_arr = array("term" => $term, "next_doc" => $inverted_index->nextDoc($term, -INF.":".-INF));
            $this->terms->insert($temp_arr);
        }

        while (explode(":", $this->terms->top()["next_doc"])[0] < $inverted_index->num_of_docs) {
            $d = explode(":", $this->terms->top()["next_doc"])[0];
            if ($d == "INF") {
                break;
            }

            $score = 0;
            while (explode(":", $this->terms->top()["next_doc"])[0] == $d) {
                $t = $this->terms->top()["term"];
                $score += self::scoreBM25($t, $d, $inverted_index);
                self::nextDocTop($inverted_index);
            }
            $temp_arr = array("doc_id" => $d, "score" => $score);
            $this->results->insert($temp_arr);                

            // if ($this->results->isEmpty()) {
            //     $temp_arr = array("doc_id" => $d, "score" => $score);
            //     $this->results->insert($temp_arr);                
            // } else if ($score > $this->results->top()["score"]) {
            //     // print("here\n");
            //     $temp_arr = array("doc_id" => $d, "score" => $score);
            //     $this->results->insert($temp_arr);
            // }
        }
        return $this->results;
    }

    public function nextDocTop(&$inverted_index) {
        $temp_top = $this->terms->top();
        $this->terms->extract();

        $term = $temp_top["term"];
        $position = $temp_top["next_doc"];
        $exp = explode(":", $position);
        $doc_id = $exp[0];
        $pos = $exp[1];
        
        $next_doc = $inverted_index->nextDoc($term, "$doc_id:$pos");

        $new_arr = array("term" => $term, "next_doc" => $next_doc);
        $this->terms->insert($new_arr);
    }

    public function calcAllIdfs(&$inverted_index) 
    {
        $keys = array_keys($inverted_index->postings);
        foreach ($keys as $term) {
            $t_idf = self::calcIdf($term, $inverted_index);
            $this->term_idfs[$term] = $t_idf;
        }
    }

    public function calcIdf($t, &$inverted_index)
    {
        $num_of_docs = $inverted_index->num_of_docs;
        $docs_with_term = count(array_keys($inverted_index->postings[$t]));
        $tmp = floatval($num_of_docs/$docs_with_term);
        return log($tmp, 2);
    }

    public function tfBM25($f_t_d, $k, $b, $l_d, $l_avg)
    {
        $numerator = $f_t_d * ($k + 1);
        $denominator = $f_t_d + $k * ((1 - $b) + $b * ($l_d/$l_avg));
        return $numerator / $denominator;
    }

    public function scoreBM25($q, $doc_id, &$inverted_index, $k = 1.2, $b = 0.75)
    {
        $terms = explode(" ", $q);
        $score = 0;
        $l_avg = $inverted_index->avg_doc_length;

        foreach ($terms as $term) {
            $f_t_d = $inverted_index->postings_sizes[$term][$doc_id];
            $l_d = $inverted_index->doc_lengths[$doc_id];
            $idf = self::calcIdf($term, $inverted_index);
            $tf = self::tfBM25($f_t_d, $k, $b, $l_d, $l_avg);
            $score += $idf * $tf;
        }
        return $score;
    }

}