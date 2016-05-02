<?php



namespace genriquez\search_program;

use seekquarry\yioop\library\PhraseParser; 
use seekquarry\yioop\library\CrawlConstants;
use seekquarry\yioop\library\processors\HtmlProcessor;
use classes\invertedindex as InvertedIndex;
use classes\rankbm25 as RankBM25;
use classes\rankbm25f as RankBM25f;
use classes\cosinerank as RankCosine;
use classes\humanrankings as HumanRanking;

spl_autoload_register();

require_once "vendor/autoload.php";

set_time_limit(0);

// positional args: some_dir index_filename
$args = array("files_dir" => $argv[1], "index_filename" => $argv[2]);

print("here\n");
// get list of docs from folder
$doc_files = glob($args["files_dir"]."/*.txt", GLOB_NOSORT);
print("here\n");
$inverted_index = readFiles($doc_files);
// var_dump($inverted_index);
print("here\n");

function readFiles($doc_files)
{
    $inverted_index = new InvertedIndex();

    print("here2\n");
    foreach ($doc_files as $doc_index=>$doc) {
        // open file
        print("here2\n");
        $line = file_get_contents($doc);
        print("here2\n");
        if ($line == "") {
            continue;
        }

        $line = preg_replace('!\s+!', ' ', $line);
        // lowercase tokens
        $line = strtolower($line);
        $word_counter = 0;
        // remove punctuations and special characters
        $line = preg_replace('/[^A-Za-z0-9 ]/', "", $line);

        $tokens = explode(" ", trim($line));
        foreach ($tokens as $token) {
            if ($token == "") {
                continue;
            } else {
                $result = PhraseParser::stemTerms($token, 'en-US');
                $token = $result[0];
                $inverted_index->addTerm($token, $doc_index, $word_counter);
            }
            $word_counter++;
        }
        print("here2\n");
    }
    $inverted_index->sortPostings();
    print("Average doc length: ".$inverted_index->calcAvgDocLength()."\n");

    return $inverted_index;
}
?>