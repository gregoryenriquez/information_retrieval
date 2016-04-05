<?php

namespace classes;

class RankBM25 {

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

            if ($this->results->isEmpty()) {
                $temp_arr = array("doc_id" => $d, "score" => $score);
                $this->results->insert($temp_arr);                
            } else if ($score > $this->results->top()["score"]) {
                // print("here\n");
                $temp_arr = array("doc_id" => $d, "score" => $score);
                $this->results->insert($temp_arr);
            }
        }
        print("Results:\n");
        // var_dump($this->terms);
        var_dump($this->results);
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
        // var_dump($this->term_idfs);
    }

    public function calcIdf($t, &$inverted_index)
    {
        $num_of_docs = $inverted_index->num_of_docs;
        // print("num_of_docs: $num_of_docs\n");
        $docs_with_term = count($inverted_index->postings[$t]);
        // print("docs_with_term: $docs_with_term\n");
        $tmp = floatval($num_of_docs/$docs_with_term);
        return log($tmp, 2);
    }

    public function tfBM25($f_t_d, $k, $b, $l_d, $l_avg)
    {
        $numerator = $f_t_d * ($k + 1);
        // print("num: $numerator\n");
        $denominator = $f_t_d + $k * ((1 - $b) + b * ($l_d/$l_avg));
        // print("dem: $denominator\n");
        return $numerator / $denominator;
    }

    public function scoreBM25($q, $doc_id, &$inverted_index, $k = 1.2, $b = 0.75)
    {
        // print("q: $q, doc_id: $doc_id, k: $k, b: $b\n");
        $terms = explode(" ", $q);
        // var_dump($terms);
        $score = 0;
        $l_avg = $inverted_index->avg_doc_length;
        // var_dump($l_avg);

        // print("done\n");

        foreach ($terms as $term) {
            // var_dump($this->inverted_index->postings_sizes[$term]);
            $f_t_d = $inverted_index->postings_sizes[$term][$doc_id];
            // print("f_t_d: $f_t_d\n");
            $l_d = $inverted_index->doc_lengths[$doc_id];
            // print("l_d: $l_d\n");
            $idf = self::calcIdf($term, $inverted_index);
            // print("idf: $idf\n");
            $tf = self::tfBM25($f_t_d, $k, $b, $l_d, $l_avg);
            // print("tf: $tf\n");
            $score += $idf * $tf;
            // var_dump($f_t_d, $l_d, $idf, $tf, $score);
        }
        // var_dump($score);
        return $score;
    }

}