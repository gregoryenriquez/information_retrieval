<?php

namespace genriquez\search_program;

use seekquarry\yioop\library\CrawlConstants;
use seekquarry\yioop\library\PhraseParser; 
use seekquarry\yioop\library\processors\HtmlProcessor;
use classes\invertedindex as InvertedIndex;
use classes\rankbm25 as RankBM25;
use classes\rank as Rank;

spl_autoload_register();

require_once "vendor/autoload.php";

// positional args: some_dir query ranking_method tokenization_method
$cmd_line_dir = $argv[1];
$cmd_query = $argv[2];
$cmd_ranking = $argv[3];
$cmd_tokenization_method = $argv[4];


$doc_files = glob($cmd_line_dir."/*.html", GLOB_NOSORT);


// setup Inverted Index
// $inverted_index = readFiles($doc_files, $cmd_tokenization_method);
$inverted_index = readHtmlFiles($doc_files, $cmd_tokenization_method);
// exit();

// setup html files
// $html_proc = new HtmlProcessor(array(), 20000, HtmlProcessor::CENTROID_SUMMARIZER);
// // var_dump(HtmlProcessor\DESCRIPTION);
// print("hi\n");
// $contents = file_get_contents("./test_html_files/clashroyale1.html");
// // $contents = file_get_contents("./test_html_files/clashroyale3.html");
// print("hi\n");
// var_dump($html_proc);
// print("hi\n");
// // var_dump($contents);
// $result = $html_proc->process($contents, "dummy");
// // var_dump($result);
// print("Title: ".$result[CrawlConstants::TITLE]."\n");
// $result[CrawlConstants::DESCRIPTION] = preg_replace('!\s+!', ' ', $result[CrawlConstants::DESCRIPTION]);
// print("Description: ".$result[CrawlConstants::DESCRIPTION]."\n");

// exit();



//bm25f
// $result[CrawlConstants::DESCRIPTION] = str_replace(strtolower($result[CrawlConstants::TITLE]), "", 
// $result[CrawlConstants::DESCRIPTION]);
// print("Description: ".$result[CrawlConstants::DESCRIPTION]."\n");

// $terms = strtolower($result[CrawlConstants::TITLE]);
// $terms = preg_replace('!\s+!', ' ', $terms);
// var_dump($terms);
// exit();
print("here\n");
$rank = new Rank();
print("here\n");
print("here2\n");
$rankBM25 = new RankBM25($inverted_index);
print("here\n");
$terms = explode(" ", $cmd_query);
print("here\n");
$rankBM25->rankBM25fHtml($terms, 0.5, 10, $inverted_index);


// $rankBM25->rankBM25WithHeaps($terms, 3);
// $q = "puppies";
// $doc_id = 1;
// $rankBM25->scoreBM25($q, $doc_id, $k = 1.2, $b = 0.75);
exit();
// var_dump($inverted_index->postings);

// echo "dump: \n";
// $inverted_index->{'dump'}();
// echo "end program\n";


print("number of documents: ".$inverted_index->num_of_docs."\n");
// print("first puppies: ".$inverted_index->{'first'}('puppies')."\n");
// print("last puppies: ".$inverted_index->{'last'}('puppies')."\n");
// print("first monkies: ".$inverted_index->{'first'}('monkies')."\n");
// print("last monkies: ".$inverted_index->{'last'}('monkies')."\n");
// print("next buffer(0, 0): ".$inverted_index->{'next'}("buffer", "0:0")."\n");
// print("next buffer(0, 1): ".$inverted_index->{'next'}("buffer", "0:1")."\n");
// print("nextDoc buffer(0, 1): ".$inverted_index->{'nextDoc'}("buffer", "0:1")."\n");
// print("prev buffer(0, 5): ".$inverted_index->{'prev'}("buffer", "0:5")."\n");
// var_dump($inverted_index->postings["buffer"]);
// print($inverted_index->{'termToString'}("buffer")."\n");
// print("IDF: puppies = ".$rank->{'calcIdf'}("puppies", $inverted_index)."\n");
// print("TF: puppies, doc 0 = ".$rank->{'calcTf'}("puppies", 0, $inverted_index)."\n");
// print("TF: puppies, doc 1 = ".$rank->{'calcTf'}("puppies", 1, $inverted_index)."\n");
// print("TF: puppies, doc 2 = ".$rank->{'calcTf'}("puppies", 2, $inverted_index)."\n");
// // var_dump($inverted_index->postings);
// $tmp = $rank->{'calcTfIdf'}("puppies", $inverted_index, $inverted_index->num_of_docs);
// $rank->{'calcAllIdfs'}($inverted_index);
// $rank->{'calcAllTfs'}($inverted_index, $inverted_index->num_of_docs);
// print("TF-IDF values: \n");
// $rank->{'calcAllTfIdf'}($inverted_index, $inverted_index->num_of_docs);
// $rank->{'calcAllVecLength'}($inverted_index, $inverted_index->num_of_docs);
// // exit();
// $rank->{'createQueryVector'}($cmd_query);
// $terms = explode(" ", $cmd_query);
// $rank->{'cosineRank'}($terms, 2, $inverted_index);
// print($inverted_index->{'nextDoc'}($term, "2:0")."\n");

// $cos_rank = $rank->{'simVec'}(0, "buffer puppies");
// print("Cosine rank: doc 0, \"buffer puppies\" = $cos_rank\n");
// print("next(puppies, 0:0): ".$inverted_index->{'next'}('puppies', "0:0")."\n");
// print("prev(puppies, 1): ".$inverted_index->{'prev'}('puppies', 1)."\n");

function readHtmlFiles($doc_files, $cmd_tokenization_method)
{
    // setup html files
    $html_proc = new HtmlProcessor(array(), 20000, HtmlProcessor::CENTROID_SUMMARIZER);

    $inverted_index = new InvertedIndex();
    
    foreach ($doc_files as $doc_index=>$doc) {
        print("$doc\n");
        // open file
        $contents = file_get_contents($doc);
        $res = $html_proc->process($contents, "dummy");
        print("Title: ".$res[CrawlConstants::TITLE]."\n");
        $line = preg_replace('!\s+!', ' ', $res[CrawlConstants::DESCRIPTION]);
        print("Description: ");
        // lowercase tokens
        $line = strtolower($line);
        // print($line."\n");

        $word_counter = 0;
        // remove punctuations and special characters
        $line = preg_replace('/[^A-Za-z0-9\- ]/', "", $line);


        // split tokens
        $tokens = explode(" ", trim($line));
        foreach ($tokens as $token) {
            if ($token == "") {
                continue;
            } else {
                // case: chargram selected
                if ($cmd_tokenization_method == "chargram") {
                    $tokens_gram = PhraseParser::getNGramsTerm(array($token), 5);
                    foreach ($tokens_gram as $gram) {
                        $inverted_index->{'addTerm'}($gram, $doc_index, $word_counter);
                    }
                // case: stem selected
                } else {
                    if ($cmd_tokenization_method == "stem") {
                        $result = PhraseParser::stemTerms($token, 'en-US');
                        $token = $result[0];
                    }
                    $inverted_index->{'addTerm'}($token, $doc_index, $word_counter);
                }
            }
            $word_counter++;
        }
        
    }
    $inverted_index->sortPostings();
    print("Average doc length: ".$inverted_index->calcAvgDocLength()."\n");
    // var_dump($inverted_index);
    return $inverted_index;    
}

function readFiles($doc_files, $cmd_tokenization_method) 
{
    $inverted_index = new InvertedIndex();
    foreach ($doc_files as $doc_index=>$doc) {
        // open file
        $handle = @fopen($doc, "r");
        $word_counter = 0;
        while (!feof($handle)) {
            $line = fgets($handle);
            // remove punctuations and special characters
            $line = preg_replace('/[^A-Za-z0-9\- ]/', "", $line);
            // lowercase tokens
            $line = strtolower($line);
            // split tokens
            $tokens = explode(" ", trim($line));
            foreach ($tokens as $token) {
                if ($token == "") {
                    continue;
                } else {
                    // case: chargram selected
                    if ($cmd_tokenization_method == "chargram") {
                        $tokens_gram = PhraseParser::getNGramsTerm(array($token), 5);
                        foreach ($tokens_gram as $gram) {
                            $inverted_index->{'addTerm'}($gram, $doc_index, $word_counter);
                        }
                    // case: stem selected
                    } else {
                        if ($cmd_tokenization_method == "stem") {
                            $result = PhraseParser::stemTerms($token, 'en-US');
                            $token = $result[0];
                        }
                        $inverted_index->{'addTerm'}($token, $doc_index, $word_counter);
                    }
                }
                $word_counter++;
            }
        }
    }
    $inverted_index->sortPostings();
    print("Average doc length: ".$inverted_index->calcAvgDocLength()."\n");
    return $inverted_index; 
}
?>