<?php

namespace genriquez\search_program;

use seekquarry\yioop\library\CrawlConstants;
use seekquarry\yioop\library\processors\HtmlProcessor;
use classes\invertedindex as InvertedIndex;
use classes\rankbm25 as RankBM25;
use classes\rankbm25f as RankBM25f;
use classes\rank as Rank;

spl_autoload_register();

require_once "vendor/autoload.php";

// positional args: some_dir query ranking_method tokenization_method
$args = array("files_dir" => $argv[1], "query" => $argv[2], "ranking_method" => $argv[3], "tokenization_method" => $argv[4]);

if (count($argv) == 6) {
    $args["alpha"] = $argv[5];
} else {
    $args["alpha"] = 0.5;
}

$doc_files = glob($args["files_dir"]."/*.html", GLOB_NOSORT);


// setup Inverted Index
if ($args["ranking_method"] == "bm25") {
    $inverted_index = readHtmlFiles($doc_files, $args["tokenization_method"]);
    $inverted_index = $inverted_index["desc"];
    $rankBM25 = new RankBM25($inverted_index);
    $terms = explode(" ", $args["query"]);
    $results_heap = $rankBM25->rankBM25WithHeaps($terms, 10, $inverted_index);
    $results_heap->printHeap();

} else if ($args["ranking_method"] == "bm25f") {
    $inverted_indices = readHtmlFiles($doc_files, $args["tokenization_method"]);
    $inverted_index_title = $inverted_indices["title"];
    $inverted_index_desc = $inverted_indices["desc"];
    $rankBM25f = new RankBM25f($inverted_index_title);
    $terms = explode(" ", $args["query"]);
    $results_heap = $rankBM25f->rankBM25fHtml($terms, $args["alpha"], 10, $inverted_index_title, $inverted_index_desc);
    $results_heap->printHeap();
    
} else if ($args["ranking_method"] == "cosine") {
    $inverted_index = readHtmlFiles($doc_files, $args["tokenization_method"]);
    $inverted_index = $inverted_index["desc"];
    $rank = new Rank();
    $terms = explode(" ", $args["query"]);
    $rank->cosineRank($terms, 2, $inverted_index);
    $rank->printResults();
}


function readHtmlFiles($doc_files, $tok_method)
{
    $html_proc = new HtmlProcessor(array(), 20000, HtmlProcessor::CENTROID_SUMMARIZER);
    $inverted_index_desc = new InvertedIndex();
    $inverted_index_title = new InvertedIndex();

    foreach ($doc_files as $doc_index=>$doc) {
        // open file
        $contents = file_get_contents($doc);
        $res = $html_proc->process($contents, "dummy");

        $line = preg_replace('!\s+!', ' ', $res[CrawlConstants::DESCRIPTION]);
        // lowercase tokens
        $line = strtolower($line);
        $word_counter = 0;
        // remove punctuations and special characters
        $line = preg_replace('/[^A-Za-z0-9\- ]/', "", $line);

        // split DESCRIPTION tokens
        $tokens = explode(" ", trim($line));
        foreach ($tokens as $token) {
            if ($token == "") {
                continue;
            } else {
                // case: chargram selected
                if ($tok_method == "chargram") {
                    $tokens_gram = PhraseParser::getNGramsTerm(array($token), 5);
                    foreach ($tokens_gram as $gram) {
                        $inverted_index_desc->{'addTerm'}($gram, $doc_index, $word_counter);
                    }
                // case: stem selected
                } else {
                    if ($tok_method == "stem") {
                        $result = PhraseParser::stemTerms($token, 'en-US');
                        $token = $result[0];
                    }
                    $inverted_index_desc->{'addTerm'}($token, $doc_index, $word_counter);
                }
            }
            $word_counter++;
        }

        $line = preg_replace('!\s+!', ' ', $res[CrawlConstants::TITLE]);
        // lowercase tokens
        $line = strtolower($line);
        $word_counter = 0;
        // remove punctuations and special characters
        $line = preg_replace('/[^A-Za-z0-9\- ]/', "", $line);

        // split DESCRIPTION tokens
        $tokens = explode(" ", trim($line));
        foreach ($tokens as $token) {
            if ($token == "") {
                continue;
            } else {
                // case: chargram selected
                if ($tok_method == "chargram") {
                    $tokens_gram = PhraseParser::getNGramsTerm(array($token), 5);
                    foreach ($tokens_gram as $gram) {
                        $inverted_index_title->{'addTerm'}($gram, $doc_index, $word_counter);
                    }
                // case: stem selected
                } else {
                    if ($tok_method == "stem") {
                        $result = PhraseParser::stemTerms($token, 'en-US');
                        $token = $result[0];
                    }
                    $inverted_index_title->{'addTerm'}($token, $doc_index, $word_counter);
                }
            }
            $word_counter++;
        }


        
    }
    $inverted_index_desc->sortPostings();
    $inverted_index_title->sortPostings();
    print("Average TITLE doc length: ".$inverted_index_title->calcAvgDocLength()."\n");
    print("Average DESC doc length: ".$inverted_index_desc->calcAvgDocLength()."\n");

    return array("title" => $inverted_index_title, "desc" => $inverted_index_desc);    
}
?>