<?php

namespace classes;

class InvertedIndex 
{

    const DEBUG = true;
    public $postings;
    public $first_posts;
    public $last_posts;
    public $postings_sizes;
    public $num_of_docs;
    // public $formatted_data;
    public $avg_doc_length;
    public $doc_lengths;

    function __construct() {
        $this->postings = array();
        $this->postings_sizes = array();
        $this->num_of_docs = 0;
        $this->avg_doc_length = 0;
        $this->doc_lengths = array();
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

        if ($this->postings_sizes[$t][$current_doc] == NULL 
            || $tmp_last_pos <= $current_pos) {
            // || $this->postings[$t][$current_doc][$tmp_last_pos] <= $current_pos) {
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
        // var_dump($this->postings[$t][$current_doc]);
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

        // var_dump($tmp_size);
        // var_dump($this->postings[$t][$current_doc]);
        // var_dump($this->postings[$t][$current_doc][0]);
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

        // var_dump($low, $high, $current_doc, $current_pos);
        // print("\n");
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
                // print "here";
                return $low;
                // return $this->postings[$t][$current_doc][$low];                
            } else {
                // print "here2"; this
                // var_dump($this->postings[$t][$current_doc][$high]);
                return $high;
                // return $this->postings[$t][$current_doc][$high];
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