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
use classes\gammacode as GammaCode;

spl_autoload_register();

require_once "vendor/autoload.php";

set_time_limit(0);

// positional args: some_dir index_filename
$args = array("files_dir" => $argv[1], "index_filename" => $argv[2]);


// get list of docs from folder
$doc_files = glob($args["files_dir"]."/*.txt", GLOB_NOSORT);
$inverted_index = readFiles($doc_files);
// var_dump($inverted_index);

$inverted_index->convertToDeltaOffsets();
// var_dump($inverted_index->delta_postings);
// var_dump($inverted_index->delta_table);

$gamma = new GammaCode($inverted_index->delta_table);
// var_dump($gamma->codes);

$deltas = $inverted_index->generateBinaryPostings($gamma);
// var_dump($deltas);

// var_dump($inverted_index->string_dict);

// var_dump($inverted_index->string_dict_indices);

$document_maps = $inverted_index->createDocumentMaps();

$content = array("index" => $inverted_index->string_dict_indices, "string_dict" => $inverted_index->string_dict, "postings" => $deltas, "document_maps" => $document_maps);

var_dump($content);

$content = serialize($content);

file_put_contents($args["index_filename"], $content);

// calculate document-wide gamma codes

// calculate delta frequency

// create code map
// -store code map

// store dictionary as string
// -stemmed word
// -start: gamma code position

// store indexes
// -start: numchars(stemmed word + gamma code position)

// write code map to file
// write indexes to file
// write dictionary as string to file
// write gamma code postings lists to file

function readFiles($doc_files)
{
    $inverted_index = new InvertedIndex();

    foreach ($doc_files as $doc_index=>$doc) {
        // open file
        $line = file_get_contents($doc);
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
    }
    $inverted_index->sortPostings();
    print("Average doc length: ".$inverted_index->calcAvgDocLength()."\n");

    return $inverted_index;
}
?>