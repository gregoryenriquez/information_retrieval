<?php

namespace classes;

use classes\gammacode as GammaCode;

class InvertedIndex 
{

    const DEBUG = true;
    public $postings;

    public $first_posts;
    public $last_posts;
    public $postings_sizes;
    public $num_of_docs;
    public $avg_doc_length;
    public $doc_lengths;

    public $delta_table;
    public $delta_postings;
    public $string_dict;
    public $string_dict_pos;
    public $string_dict_indices;

    function __construct() {
        $this->postings = array();
        $this->postings_sizes = array();
        $this->num_of_docs = 0;
        $this->avg_doc_length = 0;
        $this->doc_lengths = array();
        $this->delta_postings = array();
        $this->delta_table = array();
        $this->string_dict = "";
        $this->string_dict_pos = 0;
        $this->string_dict_indices = array();
        $this->string_dict_indices[] = 0;
    }

    function addTerm($t, $doc_id, $position) 
    {
        if ($this->doc_lengths[$doc_id] == NULL) {
            $this->doc_lengths[$doc_id] = 0;
        }

        $this->doc_lengths[$doc_id] += 1;
        $this->num_of_docs = max($this->num_of_docs, $doc_id + 1);

        if ($this->postings[$t] == NULL) {
            $this->first_posts[$t] = "$doc_id:$position";
        }

        $this->postings[$t][$doc_id][] = $position;
        if ($this->postings_sizes[$t] == NULL) {
            $this->postings_sizes[$t][$doc_id] = 0;
        }

        $this->postings_sizes[$t][$doc_id]++;
        $this->last_posts[$t] = "$doc_id:$position";
    }

    function convertToDeltaOffsets()
    {
        $terms = array_keys($this->postings);
        foreach($terms as $term) {
            $new_term = array();

            $doc_ids = array_keys($this->postings[$term]);
            foreach ($doc_ids as $doc_id) {
                $tmp = self::off2Delta($this->postings[$term][$doc_id]);
                $new_term[$doc_id] = $tmp;
                // $this->postings[$term][$doc_id] = $tmp;
            }
            $this->delta_postings[$term] = $new_term;
        }
    }

    function off2Delta($offsets) 
    {
        $delta_offsets = array();
        for ($i = 0; $i < count($offsets); $i++) {
            if ($i == 0) {
                $delta_offsets[] = $offsets[$i];
                self::addToDeltaTable($offsets[$i]);
            } else {
                $tmp_delta = $offsets[$i] - $offsets[$i - 1];
                $delta_offsets[] = $tmp_delta;
                self::addToDeltaTable($tmp_delta);
            }
        }
        return $delta_offsets;
    }

    function addToDeltaTable($delta) 
    {
        if ($this->delta_table[$delta] == null) {
            $this->delta_table[$delta]["value"] = $delta;
            $this->delta_table[$delta]["count"] = 1;
        } else {
            $this->delta_table[$delta]["count"]++;
        }
    }

    function generateBinaryPostings($code_table)
    {
        $deltas = array();
        $terms = array_keys($this->delta_postings);
        foreach($terms as $term) {
            $tmp = "";
            $doc_ids = array_keys($this->delta_postings[$term]);
            foreach($doc_ids as $doc_id) {
                $tmp = $tmp . ":" . strval($doc_id) . ":";
                for ($i = 0; $i < count($this->delta_postings[$term][$doc_id]); $i++) {
                    // var_dump($code_table->codes[5]);
                    // var_dump($this->delta_postings[$term][$doc_id][$i]);
                    // var_dump($code_table->getCode($this->delta_postings[$term][$doc_id][$i]));

                    // print("----\n");
                    $val = $code_table->codes[intval($this->delta_postings[$term][$doc_id][$i])];
                    $tmp = $tmp . $val;
                }
            }
            $deltas[] = $tmp;
            $length = strlen($tmp);

            $last_val = $this->string_dict_indices[count($this->string_dict_indices) - 1];

            // print("term: $term last val: $last_val term length: " . strlen($term) . " posting length: " . strlen(strval($this->string_dict_pos + $length)) . "\n");
            $this->string_dict_indices[] = $last_val + strlen($term) + strlen(strval($this->string_dict_pos + $length));
            $this->string_dict = $this->string_dict . $term . ($this->string_dict_pos + $length);
            $this->string_dict_pos += $length;
        }
        return $deltas;
    }

    function createDocumentMaps()
    {
        $docs = array();
        $avg_doc_length = 0;

        $terms = array_keys($this->postings);
        foreach($terms as $term) {
            $doc_ids = array_keys($this->postings[$term]);
            foreach($doc_ids as $doc_id) {
                if ($docs[$doc_id] == null) {
                    $docs[$doc_id] = array();
                    $docs[$doc_id]["length"] = 0;
                }
                if ($docs[$doc_id][$term] == null) {
                    $docs[$doc_id][$term] = array("term" => $term, "frequency" => 0);
                }
                $docs[$doc_id][$term]["frequency"] = count($this->postings[$term][$doc_id]);
                $docs[$doc_id]["length"] += count($this->postings[$term][$doc_id]);
            }
        }

        foreach($docs as $doc) {
            $avg_doc_length += $doc["length"];
        }

        $avg_doc_length = $avg_doc_length / count($docs);
        $doc["avg_length"] = $avg_doc_length;
        return $docs;
    }

    public function sortPostings() 
    {
        ksort($this->postings);
    }

    public function calcAvgDocLength()
    {
        $sum = 0;
        $count = count($this->doc_lengths);
        foreach ($this->doc_lengths as $length) {
            $sum += $length;
        }
        $this->avg_doc_length = $sum / $count;
        return $this->avg_doc_length;
    }

    public function termToString($t) 
    {
        $num_docs = count($this->postings[$t]);
        $total_occurrences = 0;
        $num = 0;
        $builder = array();
        $keys = array_keys($this->postings[$t]);
        foreach ($keys as $key) {
            $count = count($this->postings[$t][$key]);
            $total_occurrences += $count;
            $builder[] = "($key,".implode(",", $this->postings[$t][$key]).")";

        }
        $tmp = "$t:$num_docs:$total_occurrences:(".implode(",", $builder).")";;
        return $tmp;
    }

    public function getDocOccurrences($t, $doc)
    {
        return $this->postings[$t][$doc];
    }

    public function first($t)
    {
        return $this->first_posts[$t];
    }

    public function last($t)
    {
        return $this->last_posts[$t];
    }

    public function nextDoc($t, $position)
    {
        $temp = explode(":", $position);
        if ($temp[0] == "-INF") {
            return "0:0";
        } else {
            $start_doc = $temp[0];
        }

        $next_doc = $start_doc + 1;

        while ($this->postings[$t][$next_doc] == NULL) {
            $next_doc = $next_doc + 1;
            if ($next_doc >= $this->num_of_docs) {
               return INF.":".INF;
            }
        }


        if ($next_doc >= $this->num_of_docs) {
            return INF.":".INF;
        }
        return "$next_doc:0";
    }

    public function prevDoc($t, $position)
    {
        return self::prev($t, $position);
    }

    public function next($t, $current)
    {
        $pos = INF;
        $temp = explode(":", $current);
        $current_doc = $temp[0];
        $current_pos = $temp[1];

        $tmp_last_pos = $this->postings_sizes[$t][$current_doc] - 1;
        if ($tmp_last_pos < 0) {
            self::printDebug("WARN: tmp_last_pos less than 0!\n");
            return INF.":".INF;
        }

        if ($this->postings_sizes[$t][$current_doc] == NULL || $tmp_last_pos <= $current_pos) {
            self::printDebug("WARN: tmp_last_pos is zero or last_pos value is less than current\n");
            return INF.":".INF;
        }


        if ($this->postings[$t][$current_doc][0] > $current_pos) {
            $pos = $this->postings[$t][$current_doc][0];
            return "$current_doc:$pos";
        }

        $low = 0;
        $jump = 1;
        $high = $low + $jump;

        while ($high < $tmp_last_pos && $this->postings[$t][$current_doc][$high] <= $current_pos) {
            $low = $high;
            $jump = 2 * $jump;
            $high = $low + $jump;
        }

        if ($high > $tmp_last_pos) {
            $high = $tmp_last_pos;
        }

        $pos = self::binarySearch($t, $low, $high, $current_doc, $current_pos);
        $pos = $this->postings[$t][$current_doc][$pos];
        return "$current_doc:$pos";
    }

    public function prev($t, $current) {
        $pos = -INF;
        $temp = explode(":", $current);
        $current_doc = intval($temp[0]);
        $current_pos = intval($temp[1]);

        $tmp_size = $this->postings_sizes[$t][$current_doc];
        if ($tmp_size == NULL) {
            self::printDebug("ERROR: tmp_size is NULL!\n");
            return -INF;
        }

        if ($tmp_size == 0 || $this->postings[$t][$current_doc][0] >= $current_pos) {
            self::printDebug("ERROR: tmp_size is zero or first_pos value is greater than current\n");
            return -INF.":".-INF;
        }


        if ($this->postings[$t][$current_doc][$tmp_size - 1] < $current_pos) {
            $pos = $this->postings[$t][$current_doc][$tmp_size - 1];
            self::printDebug("last index is less than current");
            return "$current_doc:$pos";
        }

        $high = $tmp_size - 1;
        $jump = 1;
        $low = $high - $jump;

        while ($low > 0 && $this->postings[$t][$current_doc][$low] >= $current_pos) {
            $high = $low;
            $jump = 2 * $jump;
            $low = $high - $low;
        } 

        if ($low < 0) {
            $low = 0;
        }

        $pos = self::binarySearchPrev($t, $low, $high, $current_doc, $current_pos);
        $pos = $this->postings[$t][$current_doc][$pos];
        return "$current_doc:$pos";
    }

    public function binarySearch($t, $low, $high, $current_doc, $current_pos) {
        $low_val = $this->postings[$t][$current_doc][$low];
        $high_val = $this->postings[$t][$current_doc][$high];
        $mid = floor(($high + $low) / 2);
        $mid_val = $this->postings[$t][$current_doc][$mid];

        if ($high - $low <= 1) {
            if ($current_pos < $low_val) {
                return $low;
            } else {
                return $high;
            }
        } else if ($current_pos >= $mid_val) {
            return self::binarySearch($t, $mid, $high, $current_doc, $current_pos);
        } else if ($current_pos < $mid_val) {
            return self::binarySearch($t, $low, $mid, $current_doc, $current_pos);
        }
    }

    public function binarySearchPrev($t, $low, $high, $current_doc, $current_pos) {
        $low_val = $this->postings[$t][$current_doc][$low];
        $high_val = $this->postings[$t][$current_doc][$high];
        $mid = ceil(($high + $low) / 2);
        $mid_val = $this->postings[$t][$current_doc][$mid];

        if ($high - $low <= 1) {
            if ($current_pos > $high_val) {
                return $high;
            } else {
                return $low;
            }
        } else if ($current_pos >= $mid_val) {
            return self::binarySearch($t, $mid, $high, $current_doc, $current_pos);
        } else if ($current_pos < $mid_val) {
            return self::binarySearch($t, $low, $mid, $current_doc, $current_pos);
        }
    }

    public function writeToDisk($file_path) 
    {

    }

    public function dump() 
    {
        var_dump($this->postings);
    }

    public function printDebug($s) {
        if (self::DEBUG == true) {
            print($s);
        }
    }
}

?>