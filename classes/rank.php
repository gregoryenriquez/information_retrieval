<?php

namespace classes;

class Rank 
{
    const DEBUG = true;

    public $term_idfs;
    public $term_tfs;
    public $doc_tfs_idfs;
    public $doc_vec_length;
    public $qvec_tf_idfs;
    public $qvec_length;
    public $results;

    public function __construct()
    {
        $this->term_idfs = array();
        $this->term_tfs = array();
        $this->doc_tfs_idfs = array(); // vector of all term tf idfs
        $this->doc_vec_length = array();
        $this->qvec_tf_idfs = array();
        $this->qvec_length = 0;
        $this->results = null;
    }

    public function printResults()
    {
        print("Cosine Rank Results: \n");
        $i = 0;
        foreach ($this->results as $result) {
            $i++;
            print("Rank #$i"." Doc ID: ".$result["document"]." Score: ".$result["score"]."\n");
            if ($i == 20) {
                break;
            }
        }
    }

    public function cosineRank($terms, $k_docs = 20, &$inverted_index) 
    {
        self::calcAllTfIdf($inverted_index, $inverted_index->num_of_docs);
        self::calcAllVecLength($inverted_index, $inverted_index->num_of_docs);
        self::createQueryVector($terms);

        $j = 0;
        $results = array();
        $result = self::cosMin($terms, -1, 0, $inverted_index);
        $temp = explode(":", $result);
        $doc_id = $temp[0];
        $pos = $temp[1];
        if ($doc_id == INF) {
            debugPrint("Error: doc_id is INF");
        }
        $q = implode(" ", $terms);
        while ($doc_id != "INF") {
            $results[$j]["document"] = $doc_id;
            $score = self::simVec($doc_id, $q);
            $results[$j]["score"] = $score;
            $j++;

            $res = self::cosMin($terms, $doc_id, $pos, $inverted_index);
            $temp = explode(":", $res);
            $doc_id = $temp[0];
            $pos = $temp[1];
        }
        usort($results, "self::cmpScore");
        $this->results = $results;
        return $results;

    }

    public function cmpScore($result_one, $result_two)
    {
        if ($result_one["score"] == $result_two["score"]) {
            return 0;
        }
        if ($result_one["score"] > $result_two["score"]) {
            return -1;
        }
        return 1;
    }

    public function cosMin($terms, $start_doc, $start_pos, &$inverted_index) {
        $doc = INF;
        $pos = INF;
        foreach ($terms as $term) {
            $temp = explode(":", $inverted_index->{'nextDoc'}($term, "$start_doc:$start_pos"));
            if ($temp[0] == "INF") {
                $temp_doc = INF;
            } else {
                $temp_doc = $temp[0];
            }
            $temp_pos = intval($temp[1]);
            if ($doc > $temp_doc) {
                $doc = $temp_doc;
                $pos = $temp_pos;
            } 
        }
        return "$doc:$pos";
    }

    public function calcAllVecLength(&$inverted_index, $num_docs) 
    {
        if (empty($this->doc_tfs_idfs)) {
            self::calcAllTfIdf($inverted_index, $num_docs);
        }

        for ($doc_id = 0; $doc_id < $num_docs; $doc_id++) {
            $sum = 0;
            foreach ($this->doc_tfs_idfs[$doc_id] as $term_tf_idf) {
                $sum += pow($term_tf_idf, 2);
            }
            $length = sqrt($sum);

            $this->doc_vec_length[$doc_id] = $length;
        }
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

    public function calcAllTfs(&$inverted_index, $num_docs) 
    {
        $terms = array_keys($inverted_index->postings);
        $t_tf = 0;
        foreach ($terms as $term) {
            for ($doc_id = 0; $doc_id < $num_docs; $doc_id++) {
                $t_tf = self::calcTf($term, $doc_id, $inverted_index);
                $this->term_tfs[$term][$doc_id] = $t_tf;
            }
        }
        // var_dump($this->term_tfs);
    }

    public function calcAllTfIdf(&$inverted_index, $num_docs) 
    {
        if (empty($this->term_idfs)) {
            self::calcAllIdfs($inverted_index);
        }
        if (empty($this->term_tfs)) {
            self::calcAllTfs($inverted_index, $num_docs);
        }
        $terms = array_keys($inverted_index->postings);
        foreach ($terms as $term) {
            for ($doc_id = 0; $doc_id < $num_docs; $doc_id++) {

                $t_tf_idf = $this->term_tfs[$term][$doc_id] * $this->term_idfs[$term];
                $this->doc_tfs_idfs[$doc_id][$term] = $t_tf_idf;
            }
        }
    }

    public function calcIdf($t, &$inverted_index)
    {
        $num_of_docs = $inverted_index->num_of_docs;
        $docs_with_term = count($inverted_index->postings[$t]);
        $tmp = $num_of_docs/$docs_with_term;
        return log($tmp, 2);
    }

    public function calcTf($t, $doc_id, &$inverted_index)
    {
        if ($inverted_index->postings[$t][$doc_id] != null) {
            printf(count($inverted_index->postings[$t][$doc_id])."\n");
            return 1 + log(count($inverted_index->postings[$t][$doc_id], 2));
        } else {
            return 0;
        }
    }

    public function createQueryVector($q)
    {
        $this->qvec_tf_idfs = $this->doc_tfs_idfs[0];
        $keys = array_keys($this->qvec_tf_idfs);
        foreach ($keys as $key) {
            $this->qvec_tf_idfs[$key] = 0;
        }

        $terms = $q;
        foreach ($terms as $term) {
            $this->qvec_tf_idfs[$term] = 1;
        }

        return $this->qvec_tf_idfs;
    }

    public function calcVectorLength($v) 
    {
        $sum = 0;
        foreach ($v as $component) {
            $sum += pow($component, 2);
        }

        $len = sqrt($sum);
        return $len;

    }

    public function simVec($doc_id, $q) 
    {
        $terms = explode(" ", $q);
        $q = self::createQueryVector($terms);
        $d = $this->doc_tfs_idfs[$doc_id];

        if (count($d) != count($q)) {
            self::printDebug("WARN: vector lengths are different, q = ".count($q)." d = ".count($d)."\n");
        }
        $keys = array_keys($d);
        $numerator = 0;
        foreach ($keys as $key) {
            $numerator += $d[$key] * $q[$key];
        }

        $l1 = $this->doc_vec_length[$doc_id];
        $l2 = self::calcVectorLength($q);

        $denominator = $l1 * $l2;
        return $numerator/$denominator;
    }

    public function printDebug($s) 
    {
        if (self::DEBUG == true) {
            print($s."\n");
        }
    }
}