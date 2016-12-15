<?php

namespace classes;

class Util
{
    public static function rankBM25Score($N, $N_t, $f_t_d, $l_d, $l_avg)
    {
        $k = 1.2;
        $b = 0.75;
        $score = log(floatval($N/$N_t), 2) * (($f_t_d * ($k + 1)) / ($f_t_d + $k*((1-$b) + $b * ($l_d/$l_avg))));
        return $score;
    }

    public static function stringDictToArray($dict_string)
    {
        $dict_array = array();
        $current_word = "";
        $current_num = 0;
        for ($i = 0; $i < strlen($dict_string); $i++) {
            $token = $dict_string[$i];
            if (is_numeric($token)) {
                $current_num = $current_num * 10 + intval($token);
                if ($dict_string[$i +1] == '\0') {
                    $dict_array[] = array("word" => $current_word, "posting_length" => $current_num);
                } else if (ctype_alpha($dict_string[$i + 1])) {
                    $dict_array[] = array("word" => $current_word, "posting_length" => $current_num);
                    $current_word = "";
                    $current_num = 0;
                }
            } else if (ctype_alpha($token)) {
                $current_word = $current_word . $token;
            }
        }


        return $dict_array;
    }

    public static function index2dictArray($indices, $dict_string) 
    {

        $dict_array = array();
        $old_sum_length = 0;

        for ($i = 0; $i < count($indices); $i++) {            
            $start = $indices[$i];
            if ($i + 1 == count($indices)) {
                break;
            } else {
                $length = $indices[$i+1] - $indices[$i];
            }

            $sub_str = substr($dict_string, $start, $length);
            $tmp = self::parseDictEntry($sub_str);
            $word = $tmp["word"];
            $length = $tmp["length"] - $old_sum_length;
            $old_sum_length += $length;
            $dict_array[] = array("word" => $word, "posting_length" => $length);
        }

        return $dict_array;
    }

    public static function dictArray2Postings($dict_array, $postings, $gamma)
    {
        $word_postings = array();
        $position = 0;
        foreach($dict_array as $dict_entry) {
            $word = $dict_entry["word"];
            $word_postings[$word] = array();
            $p_length = $dict_entry["posting_length"];
            $posting_string = substr($postings, $position, $p_length);
            $res_posting = self::parsePosting($posting_string);
            // var_dump($res_posting);
            foreach ($res_posting as $bin_posting) {
                $doc_id = $bin_posting["doc_id"];
                $tmp_posting = $bin_posting["posting"];
                $tmp_posting = self::decodeBinStr($tmp_posting, $gamma);
                $word_postings[$word][$doc_id]["posting"] = $tmp_posting;
            }
        }
        return $word_postings;
    }

    private function decodeBinStr($bin_str, $gamma)
    {
        $values = array();
        $current_num = "";
        $current_length = 0;
        $i = 0;
        while ($i < strlen($bin_str)) {
            if ($bin_str[$i] == "1") {
                $current_num = "1";
            } else if ($bin_str[$i] == "0") {
                $current_num = "0";
                $current_length = 1;
                $i++;
                while ($bin_str[$i] == "0") {
                    $current_num = $current_num . $bin_str[$i];
                    $current_length += 1;
                    $i++;
                } // at a 1
                for ($j = 0; $j < $current_length + 1; $j++) {
                    $current_num = $current_num . $bin_str[$i];
                    $i++;
                }
                $i--;
            }
            $values[] = $gamma->getDeltaOffset($current_num);
            $i++;
        }
        return $values;
    }

    private function parsePosting($str) 
    {
        $postings = array();
        $current_doc = 0;
        $current_posting = "";
        $i = 0;
        while ($i < strlen($str)) {
            $current_doc = 0;
            $current_posting = "";
            if ($str[$i] == ":") {
                $i++;
                while ($i < strlen($str) && $str[$i] != ":") {
                    $current_doc = $current_doc * 10 + intval($str[$i]);
                    $i++;
                }
            }

            $i++;

            while ($i < strlen($str) && $str[$i] != ":") {
                $current_posting = $current_posting . $str[$i];
                $i++;
            }
            $postings[] = array("doc_id" => $current_doc, "posting" => $current_posting);
        }
        return $postings;
    }

    private function parseDictEntry($str)
    {

        $word = "";
        $num = 0;
        for ($i = 0; $i < strlen($str); $i++) {
            $token = $str[$i];
            if (is_numeric($token)) {
                $num = $num * 10 + intval($token);
            } else if (ctype_alpha($token)) {
                $word = $word . $token;
            }
        }
        return array("word" => $word, "length" => $num);
    }



}