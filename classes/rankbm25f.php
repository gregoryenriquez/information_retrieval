<?php

namespace classes;

class RankBM25f {

    public $results;
    public $terms;
    public $num_of_docs;
    public $term_idfs_title;
    public $term_idfs_desc;

    public function __construct(&$inverted_index_title) {
        print("here\n");
        $this->term_idfs_title = array();
        $this->term_idfs_desc = array();
        $this->results = new RankHeap();
        $this->terms = new TermHeap();
        $this->num_of_docs = $inverted_index_title->num_of_docs;
    }

    public function rankBM25fHtml($terms, $alpha, $k, &$inverted_index_title, &$inverted_index_desc) 
    {
        foreach ($terms as $term) {
            $n1 = $inverted_index_title->nextDoc($term, -INF.":".-INF);
            $n2 = $inverted_index_desc->nextDoc($term, -INF.":".-INF);
            $n1 = explode(":", $n1)[0];
            $n2 = explode(":", $n2)[0];
            $next = min($n1, $n2);

            $temp_arr = array("term" => $term, "next_doc" => $next);
            $this->terms->insert($temp_arr);
        }

        while (true) {
            $d = explode(":", $this->terms->top()["next_doc"])[0];
            if ($d >= $this->num_of_docs) {
                break;
            }

            if ($d == "INF") {
                break;
            }

            $score = 0;
            while (explode(":", $this->terms->top()["next_doc"])[0] == $d) {
                $t = $this->terms->top()["term"];
                $score += self::scoreBM25f($t, $d, $inverted_index_title, $inverted_index_desc, $alpha);
                self::nextDocTop($inverted_index_title, $inverted_index_desc);
            }

            if ($this->results->isEmpty()) {
                $temp_arr = array("doc_id" => $d, "score" => $score);
                $this->results->insert($temp_arr);                
            } else if ($score > $this->results->top()["score"]) {
                $temp_arr = array("doc_id" => $d, "score" => $score);
                $this->results->insert($temp_arr);
            }
        }
        print("results:\n");
        var_dump($this->terms);
        var_dump($this->results);
    }

    public function nextDocTop(&$inverted_index_title, &$inverted_index_desc) {
        $temp_top = $this->terms->top();
        $this->terms->extract();

        $term = $temp_top["term"];
        $position = $temp_top["next_doc"];
        $exp = explode(":", $position);
        $doc_id = $exp[0];
        $pos = $exp[1];
        
        // $next_doc = $inverted_index->nextDoc($term, "$doc_id:$pos");

        $n1 = $inverted_index_title->nextDoc($term, "$doc_id:$pos");
        $n2 = $inverted_index_desc->nextDoc($term, "$doc_id:$pos");

        $v1 = explode(":", $n1)[0];
        $v2 = explode(":", $n2)[0];
        if ($v1 < $v2) {
            $next_doc = $n1;
        } else {
            $next_doc = $n2;
        }

        $new_arr = array("term" => $term, "next_doc" => $next_doc);
        $this->terms->insert($new_arr);
    }

    public function calcAllIdfs(&$inverted_index_title, &$inverted_index_desc) 
    {
        $keys = array_keys($inverted_inde_title->postings);
        foreach ($keys as $term) {
            $t_idf = self::calcIdf($term, $inverted_index_title);
            $this->term_idfs_title[$term] = $t_idf;
        }

        $keys = array_keys($inverted_inde_desc->postings);
        foreach ($keys as $term) {
            $t_idf = self::calcIdf($term, $inverted_index_desc);
            $this->term_idfs_desc[$term] = $t_idf;
        }
        // var_dump($this->term_idfs);
    }

    public function calcIdf($t, &$inverted_index)
    {
        $num_of_docs = $inverted_index->num_of_docs;
        // print("num_of_docs: $num_of_docs\n");
        if ($inverted_index->postings[$t] == NULL) {
            $docs_with_term = 0;
        } else {    
            $docs_with_term = count($inverted_index->postings[$t]);            
        }

        // print("docs_with_term: $docs_with_term\n");
        $tmp = floatval($num_of_docs/$docs_with_term);
        // var_dump($t);
        // var_dump($inverted_index->postings["royale"]);
        // exit();
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

    public function scoreBM25f($q, $doc_id, &$inverted_index_title, &$inverted_index_desc, $alpha = 0.5) 
    {
        $title_score = self::scoreBM25($q, $doc_id, $inverted_index_title);
        $desc_score = self::scoreBM25($q, $doc_id, $inverted_index_desc);
        $score = $alpha * $title_score + (1 - $alpha) * $desc_score;
        return $score;
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
            print("dump\n");
            var_dump($f_t_d, $l_d, $idf, $tf, $score);
        }
        // print("score:\n");
        // var_dump($score);
        return $score;
    }

}