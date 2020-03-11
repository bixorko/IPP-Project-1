<?php

#################################################
#                                               #
#                  MAIN PROGRAM                 #
#                                               #
#################################################

//globalne premenne + rozsirenie

$ordercount = 1;
$comments = 0;
$labels = 0;
$jumps = 0;
$cliarguments = array("--loc", "--comments", "--labels", "--jumps");
$filename = '';
$rozsirenia = false;
$labelnames = array();

//rozparsovanie argumentov
//vystup = naparsovane argumenty
if ($argc != 1) {
    #1 argument
    if ($argc == 2) {
        if (strcmp($argv[1], '--help') == 0) {
            printHelp();
        } elseif (preg_match('/^--stats=\S+$/', $argv[1], $patternmatch)) {
            $filename = explode('=', $argv[1])[1];
            $statsfile = fopen($filename, "w");
            fclose($statsfile);
        } else {
            exit(10);
        }
    } #2 a viac argumentov
    else {
        //first kvoli ignorovaniu argv[0]
        $first = true;
        $rozsirenia = true;
        $isstats = 0;
        $givenargs = array();
        foreach ($argv as $arg) {
            if ($first){
                $first = false;
            }
            elseif (preg_match('/^--stats=\S+$/', $arg, $patternmatch)) {
                $isstats += 1;
                $filename = explode('=', $arg)[1];
            }
            elseif (in_array($arg, $cliarguments)) {
                array_push($givenargs, $arg);
            }
            else{
                #ZLE ZADANY ARGUEMNT NEROZPOZNANY
                exit(10);
            }
        }
        # VIACKRAT ZADANE STATS s FILOM
        if ($isstats != 1){
            exit(10);
        }
    }
}

//zadefinovanie regexov + jednotlivych instrukcii zoradenych podla poctu operandov
    $symbol_regex = '/^\s*((GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*)|(nil@nil)|(int@[+|-]?[0-9]+)|(bool@(true|false))|(string@\S*)\s*$/';
    $xw = xmlwriter_open_memory();
    xmlwriter_set_indent($xw, "  ");
    $res = xmlwriter_set_indent_string($xw, "  ");

    $zero_args = array("CREATEFRAME","PUSHFRAME","POPFRAME","RETURN","BREAK");
    $one_args = array("DEFVAR","CALL","PUSHS","POPS","WRITE","LABEL","JUMP","EXIT","DPRINT");
    $two_args = array("MOVE","INT2CHAR","READ","STRLEN","TYPE","NOT");
    $three_args = array("ADD","SUB","MUL","IDIV","LT","GT","EQ","AND","OR","STRI2INT","CONCAT","GETCHAR","SETCHAR","JUMPIFEQ","JUMPIFNEQ");

    // hlavicka vystupneho XML programu
    xmlwriter_start_document($xw, '1.0', 'UTF-8');
    xmlwriter_start_element($xw, 'program');
    xmlwriter_start_attribute($xw, 'language');
    xmlwriter_text($xw, 'IPPcode20');
    xmlwriter_end_attribute($xw);

    $firsttime = true;
    while($line = fgets(STDIN)){
        if (feof(STDIN)){
            $line = $line . "\n";
        }

        //ak je riadok prazdny skip
        if ($line[0] == "\n"){
            continue;
        }
        #ignore comments
        elseif (preg_match('/(.*#)/', $line, $output_line)){
            $comments += 1;
            $updated = substr_replace($output_line[0],"\n",-1);
            #ignore empty lines
            if (strlen($updated) === 1){
                continue;
            }
            else{
                //regex pre korektny zaciatok suboru (este som nevedel ako sa to robi predtym tak je to trosku hlupe, ale funguje to :wesmart:)
                if($firsttime){
                    if(preg_match('/^\s*(.)(i|I)(p|P)(p|P)(c|C)(o|O)(d|D)(e|E)(2)(0)\s*$/', $updated, $output_header)){
                        $firsttime = false;
                    }else{
                        exit(21);
                    }
                }else {
                    #XML
                    //odkontrolovali sme hlavicku, ideme analyzovat dalsie riadky
                    syntaxAnalyze($updated, $xw);
                }
            }
        }
        #XML
        else {
            //rovnake ako pre elseif nad tymto len pocita s tym, ze v riadku nie je komentar - ak je odchyti ho elsif vyssie
            if($firsttime){
                if(preg_match('/^\s*(.)(i|I)(p|P)(p|P)(c|C)(o|O)(d|D)(e|E)(2)(0)\s*$/', $line, $output_header)){
                    $firsttime = false;
                }else{
                    exit(21);
                }
            }else {
                #XML
                syntaxAnalyze($line, $xw);
            }
        }
    }

    //ak boli zadane rozsirenia, do suboru zadaneho v parametri --stats vpiseme jednotlive statistiky
    if ($rozsirenia){
        $statsfile = fopen($filename, "w");
        foreach ($givenargs as $stat){
            if ($stat == '--loc'){
                fwrite($statsfile,$ordercount-1);
                fwrite($statsfile,"\n");
            }
            elseif ($stat == '--comments'){
                fwrite($statsfile,$comments);
                fwrite($statsfile,"\n");
            }
            elseif ($stat == '--labels'){
                fwrite($statsfile,$labels);
                fwrite($statsfile,"\n");
            }
            elseif ($stat == '--jumps'){
                fwrite($statsfile,$jumps);
                fwrite($statsfile,"\n");
            }
        }
        fclose($statsfile);
    }

    //output pre XML
    xmlwriter_end_element($xw);
    echo xmlwriter_output_memory($xw);

#################################################
#                                               #
#                   FUNCTIONS                   #
#                                               #
#################################################

function syntaxAnalyze($string, $xw)
{
    $string = ltrim($string);
    $removedws = preg_split('/\s+/', $string);
    $backtostring = implode(" ", $removedws);
    $edited = explode(" ", $backtostring);

    global $zero_args;
    global $one_args;
    global $two_args;
    global $three_args;

    // tu rozhodneme kam mame skoncit podla poctu operandov instrukcie
    if (sizeof($edited) === 2){
        zeroArgs($edited, $zero_args, $xw);
    }
    elseif (sizeof($edited) === 3){
        oneArgs($edited, $one_args, $xw);
    }
    elseif (sizeof($edited) === 4){
        twoArgs($edited, $two_args, $xw);
    }
    elseif (sizeof($edited) === 5){
        threeArgs($edited, $three_args, $xw);
    }
    else {
        exit(23);
    }
}

function checkIfBadCountArgs($string, $firstCnt, $secondCnt, $thirdCnt)
{
    //funkcia pre kontrolu spravnosti poctu argumentov
    //aby sa vracal spravny errorcode
    foreach ($firstCnt as $a){
        if (strcmp($string[0], $a) == 0){
            exit(23);
        }
    }
    foreach ($secondCnt as $b){
        if (strcmp($string[0], $b) == 0){
            exit(23);
        }
    }
    foreach ($thirdCnt as $c){
        if (strcmp($string[0], $c) == 0){
            exit(23);
        }
    }
}

#################################################
#                                               #
#                  0 ARGUMENTS                  #
#                                               #
#################################################

function zeroArgs($string, $argsArZero, $xw)
{
    //0 operandov + rozsirenie pre kalkulaciu returnu
    global $ordercount;
    $wastherematch = false;

    global $jumps;
    global $zero_args;
    global $one_args;
    global $two_args;
    global $three_args;
    checkIfBadCountArgs($string, $one_args, $two_args, $three_args);

    foreach($argsArZero as $a) {
        if (strcmp(strtoupper($string[0]), $a) == 0) {
            if (strcmp(strtoupper($string[0]), "RETURN") == 0){
                $jumps += 1;
            }
            xmlwriter_start_element($xw, 'instruction');
            xmlwriter_start_attribute($xw, 'order');
            xmlwriter_text($xw, "$ordercount");
            xmlwriter_end_attribute($xw);
            xmlwriter_start_attribute($xw, 'opcode');
            xmlwriter_text($xw, strtoupper($string[0]));
            xmlwriter_end_attribute($xw);
            xmlwriter_end_element($xw);
            $ordercount += 1;
            $wastherematch = true;
        }
    }
    if (!$wastherematch) {
        exit(22);
    }
}

#################################################
#                                               #
#                   1 ARGUMENT                  #
#                                               #
#################################################

function oneArgs($string, $argsArOne, $xw)
{
    //funkcia pre jeden operand -> musime zacat kontrolvat regexy!
    global $ordercount;
    global $labels, $jumps;
    $wastherematch = false;
    global $symbol_regex;
    global $labelnames;

    global $zero_args;
    global $one_args;
    global $two_args;
    global $three_args;
    checkIfBadCountArgs($string, $zero_args, $two_args, $three_args);

    foreach($argsArOne as $a) {
        if (strtoupper($string[0]) == "DEFVAR") {
            if (preg_match('/^\s*(GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array)) {
                //TU BUDE REGEX PRE DEFVAR LF TF GF @ xxx a takto pokracovat aj pre LABEL a vsetky ostatne veci... odkontrolovat a ideme dalej
                helpForOneArgsXML($a, $xw, $string, 'var');
                $wastherematch = true;
                return;
            }
            else{
                exit(23);
            }
        }

        elseif (strtoupper($string[0]) == "LABEL" || strtoupper($a) == "CALL") {
            if (strtoupper($string[0]) == "LABEL"){
                if (!in_array($string[1], $labelnames)) {
                    array_push($labelnames, $string[1]);
                    $labels += 1;
                }
            }else{
                $jumps += 1;
            }
            if(preg_match('/^[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array)) {
                helpForOneArgsXML($a, $xw, $string, 'label');
                $wastherematch = true;
                return;
            }
            else{
                exit(23);
            }
        }

        else if (strtoupper($string[0]) == "WRITE") {
            if(preg_match($symbol_regex, $string[1], $output_array)) {
                declareWhichXML($a, $xw, $string);
                $wastherematch = true;
                return;
            }else{
                exit(23);
            }
        }

        elseif (strtoupper($string[0]) == "PUSHS") {
            if(preg_match($symbol_regex, $string[1], $output_array)) {
                declareWhichXML($a, $xw, $string);
                $wastherematch = true;
                return;
            }else{
                exit(23);
            }
        }

        elseif (strtoupper($string[0]) == "POPS") {
            if (preg_match('/^\s*(GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array)){
                helpForOneArgsXML($a, $xw, $string, 'var');
            }
            else {
                exit(23);
            }
            $wastherematch = true;
            return;
        }

        elseif (strtoupper($string[0]) == "JUMP") {
            $jumps += 1;
            if (preg_match('/^\s*[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array)){
                helpForOneArgsXML($a, $xw, $string, 'label');
            }
            else {
                exit(23);
            }
            $wastherematch = true;
            return;
        }

        //exit ma specialnejsi regex, nestaci len var
        elseif (strtoupper($string[0]) == "EXIT") {
            if (preg_match($symbol_regex, $string[1], $output_array)){
                if (preg_match('/^\s*(GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array)){
                    helpForOneArgsXML($a, $xw, $string, 'var');
                }
                elseif (preg_match('/^\s*string@\S*\s*$/', $string[1], $output_array)){
                    $string[1] = preg_replace('/^\s*string@/', '', $string[1]);
                    helpForOneArgsXML($a, $xw, $string, 'string');
                }
                elseif (preg_match('/^\s*bool@(true|false)\s*$/', $string[1], $output_array)){
                    $string[1] = preg_replace('/^\s*bool@/', '', $string[1]);
                    helpForOneArgsXML($a, $xw, $string, 'bool');
                }
                elseif (preg_match('/^\s*int@[+|-]?[0-9]+\s*$/', $string[1], $output_array)){
                    $string[1] = preg_replace('/^\s*int@/', '', $string[1]);
                    helpForOneArgsXML($a, $xw, $string, 'int');
                }
                elseif (preg_match('/^\s*nil@nil\s*$/', $string[1], $output_array)){
                    $string[1] = preg_replace('/^\s*nil@/', '', $string[1]);
                    helpForOneArgsXML($a, $xw, $string, 'nil');
                }

                else {
                    exit(23);
                }
            }
            else {
                exit(23);
            }
            $wastherematch = true;
            return;
        }

        elseif (strtoupper($string[0]) == "DPRINT") {
            if(preg_match($symbol_regex, $string[1], $output_array)) {
                declareWhichXML($a, $xw, $string);
                $wastherematch = true;
                return;
            }else{
                exit(23);
            }
        }
    }
    if (!$wastherematch) {
        exit(22);
    }
}

function declareWhichXML($a, $xw, $string)
{
    //pomocna funkcia pre operacie s 1 operandom
    if (preg_match('/^\s*(GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array)){
        helpForOneArgsXML($a, $xw, $string, 'var');
    }
    elseif (preg_match('/^\s*string@\S*\s*$/', $string[1], $output_array)){
        $string[1] = preg_replace('/^\s*string@/', '', $string[1]);
        helpForOneArgsXML($a, $xw, $string, 'string');
    }
    elseif (preg_match('/^\s*bool@(true|false)\s*$/', $string[1], $output_array)){
        $string[1] = preg_replace('/^\s*bool@/', '', $string[1]);
        helpForOneArgsXML($a, $xw, $string, 'bool');
    }
    elseif (preg_match('/^\s*int@[+|-]?[0-9]+\s*$/', $string[1], $output_array)){
        $string[1] = preg_replace('/^\s*int@/', '', $string[1]);
        helpForOneArgsXML($a, $xw, $string, 'int');
    }
    elseif (preg_match('/^\s*nil@nil\s*$/', $string[1], $output_array)){
        $string[1] = preg_replace('/^\s*nil@/', '', $string[1]);
        helpForOneArgsXML($a, $xw, $string, 'nil');
    }

    else {
        exit(23);
    }
}

    //generovanie XML pre operacie s 1 operandom
function helpForOneArgsXML($a, $xw, $string, $whattype)
{
    global $ordercount;
    xmlwriter_start_element($xw, 'instruction');
    xmlwriter_start_attribute($xw, 'order');
    xmlwriter_text($xw, "$ordercount");
    xmlwriter_end_attribute($xw);
    xmlwriter_start_attribute($xw, 'opcode');
    xmlwriter_text($xw, strtoupper($string[0]));
    xmlwriter_start_element($xw, 'arg1');
    xmlwriter_start_attribute($xw, 'type');
    xmlwriter_text($xw, $whattype);
    xmlwriter_end_attribute($xw);
    xmlwriter_text($xw, "$string[1]");
    xmlwriter_end_attribute($xw);
    xmlwriter_end_element($xw);
    xmlwriter_end_element($xw);
    $ordercount += 1;
}

#################################################
#                                               #
#                  2 ARGUMENTS                  #
#                                               #
#################################################

//rovnaka funkcia ako predtym ale pre operacie s 2 operandmi
function declareWhichXML2($a, $xw, $string, $firsttype)
{
    if (preg_match('/^\s*(GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[2], $output_array)){
        helpForTwoArgsXML($a, $xw, $string, $firsttype, 'var');
    }
    elseif (preg_match('/^\s*string@\S*\s*$/', $string[2], $output_array)){
        $string[2] = preg_replace('/^\s*string@/', '', $string[2]);
        helpForTwoArgsXML($a, $xw, $string, $firsttype, 'string');
    }
    elseif (preg_match('/^\s*bool@(true|false)\s*$/', $string[2], $output_array)){
        $string[2] = preg_replace('/^\s*bool@/', '', $string[2]);
        helpForTwoArgsXML($a, $xw, $string, $firsttype, 'bool');
    }
    elseif (preg_match('/^\s*int@[+|-]?[0-9]+\s*$/', $string[2], $output_array)){
        $string[2] = preg_replace('/^\s*int@/', '', $string[2]);
        helpForTwoArgsXML($a, $xw, $string, $firsttype, 'int');
    }
    elseif (preg_match('/^\s*nil@nil\s*$/', $string[2], $output_array)){
        $string[2] = preg_replace('/^\s*nil@/', '', $string[2]);
        helpForTwoArgsXML($a, $xw, $string, $firsttype, 'nil');
    }
    else {
        exit(23);
    }
}

// -||-
function helpForTwoArgsXML($a, $xw, $string, $whattype1, $whattype2)
{
    global $ordercount;
    xmlwriter_start_element($xw, 'instruction');
    xmlwriter_start_attribute($xw, 'order');
    xmlwriter_text($xw, "$ordercount");
    xmlwriter_end_attribute($xw);
    xmlwriter_start_attribute($xw, 'opcode');
    xmlwriter_text($xw, strtoupper($string[0]));
    xmlwriter_start_element($xw, 'arg1');
    xmlwriter_start_attribute($xw, 'type');
    xmlwriter_text($xw, $whattype1);
    xmlwriter_end_attribute($xw);
    xmlwriter_text($xw, "$string[1]");
    xmlwriter_end_attribute($xw);
    xmlwriter_end_element($xw);
    xmlwriter_start_element($xw, 'arg2');
    xmlwriter_start_attribute($xw, 'type');
    xmlwriter_text($xw, $whattype2);
    xmlwriter_end_attribute($xw);
    xmlwriter_text($xw, "$string[2]");
    xmlwriter_end_attribute($xw);
    xmlwriter_end_element($xw);
    xmlwriter_end_element($xw);
    $ordercount += 1;
}

function twoArgs($string, $argsArTwo, $xw)
{
    global $ordercount;
    global $symbol_regex;
    $wastherematch = false;

    global $zero_args;
    global $one_args;
    global $two_args;
    global $three_args;
    checkIfBadCountArgs($string, $zero_args, $one_args, $three_args);

    foreach($argsArTwo as $a) {

        if (strtoupper($string[0]) == "MOVE"){
            if(preg_match('/^\s*(GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array) && preg_match($symbol_regex,$string[2],$output_array)) {
                declareWhichXML2($a, $xw, $string, 'var');
                $wastherematch = true;
                return;
            }
            else{
                exit(23);
            }
        }
        elseif (strtoupper($string[0]) == "INT2CHAR"){
            if(preg_match('/^\s*(GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array) && preg_match($symbol_regex,$string[2],$output_array)) {

                declareWhichXML2($a, $xw, $string, 'var');
                $wastherematch = true;
                return;
            }
            else{
                exit(23);
            }
        }
        elseif (strtoupper($string[0]) == "READ"){
            if(preg_match('/^\s*(GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array) && preg_match('/^\s*(int|bool|string)\s*$/',$string[2],$output_array)) {
                helpForTwoArgsXML($a, $xw, $string, 'var', 'type');
                $wastherematch = true;
                return;
            }
            else{
                exit(23);
            }
        }
        elseif (strtoupper($string[0]) == "STRLEN"){
            if(preg_match('/^\s*(GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array) && preg_match($symbol_regex,$string[2],$output_array)) {
                declareWhichXML2($a, $xw, $string, 'var');
                $wastherematch = true;
                return;
            }
            else{
                exit(23);
            }
        }
        elseif (strtoupper($string[0]) == "TYPE"){
            if(preg_match('/^\s*(GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array) && preg_match($symbol_regex,$string[2],$output_array)) {
                if (preg_match('/^\s*int@[+|-]?[0-9]+\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*int@/', '', $string[2]);
                    helpForTwoArgsXML($a, $xw, $string, 'var','int');
                }
                elseif (preg_match('/^\s*bool@(true|false)\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*bool@/', '', $string[2]);
                    helpForTwoArgsXML($a, $xw, $string, 'var','bool');
                }
                elseif (preg_match('/^\s*string@\S*\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*string@/', '', $string[2]);
                    helpForTwoArgsXML($a, $xw, $string, 'var','string');
                }
                elseif (preg_match('/^\s*nil@\S*\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*nil@/', '', $string[2]);
                    helpForTwoArgsXML($a, $xw, $string, 'var','nil');
                }
                elseif(preg_match('/^\s*(GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array)){
                    helpForTwoArgsXML($a, $xw, $string, 'var','var');
                } else {
                    exit(23);
                }
                $wastherematch = true;
                return;
            }
            else{
                exit(23);
            }
        }
        elseif (strtoupper($string[0]) == "NOT"){
            if(preg_match('/^\s*(GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array) && preg_match($symbol_regex,$string[2],$output_array)) {
                if (preg_match('/^\s*int@[+|-]?[0-9]+\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*int@/', '', $string[2]);
                    helpForTwoArgsXML($a, $xw, $string, 'var','int');
                }
                elseif (preg_match('/^\s*bool@(true|false)\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*bool@/', '', $string[2]);
                    helpForTwoArgsXML($a, $xw, $string, 'var','bool');
                }
                elseif (preg_match('/^\s*string@\S*\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*string@/', '', $string[2]);
                    helpForTwoArgsXML($a, $xw, $string, 'var','string');
                }
                elseif (preg_match('/^\s*nil@\S*\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*nil@/', '', $string[2]);
                    helpForTwoArgsXML($a, $xw, $string, 'var','nil');
                }
                elseif(preg_match('/^\s*(GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array)){
                    helpForTwoArgsXML($a, $xw, $string, 'var','var');
                } else {
                    exit(23);
                }
                $wastherematch = true;
                return;
            }
            else{
                exit(23);
            }
        }
    }

    if (!$wastherematch) {
        exit(22);
    }
}

#################################################
#                                               #
#                  3 ARGUMENTS                  #
#                                               #
#################################################

function helpForThreeArgsXML($a, $xw, $string, $whattype1, $whattype2, $whattype3)
{
    global $ordercount;
    xmlwriter_start_element($xw, 'instruction');
    xmlwriter_start_attribute($xw, 'order');
    xmlwriter_text($xw, "$ordercount");
    xmlwriter_end_attribute($xw);
    xmlwriter_start_attribute($xw, 'opcode');
    xmlwriter_text($xw, strtoupper($string[0]));
    xmlwriter_start_element($xw, 'arg1');
    xmlwriter_start_attribute($xw, 'type');
    xmlwriter_text($xw, $whattype1);
    xmlwriter_end_attribute($xw);
    xmlwriter_text($xw, "$string[1]");
    xmlwriter_end_attribute($xw);
    xmlwriter_end_element($xw);
    xmlwriter_start_element($xw, 'arg2');
    xmlwriter_start_attribute($xw, 'type');
    xmlwriter_text($xw, $whattype2);
    xmlwriter_end_attribute($xw);
    xmlwriter_text($xw, "$string[2]");
    xmlwriter_end_attribute($xw);
    xmlwriter_end_element($xw);
    xmlwriter_start_element($xw, 'arg3');
    xmlwriter_start_attribute($xw, 'type');
    xmlwriter_text($xw, $whattype3);
    xmlwriter_end_attribute($xw);
    xmlwriter_text($xw, "$string[3]");
    xmlwriter_end_attribute($xw);
    xmlwriter_end_element($xw);
    xmlwriter_end_element($xw);
    $ordercount += 1;
}

function threeArgs($string, $argsArThree, $xw)
{
    global $ordercount;
    global $symbol_regex;

    global $jumps;
    global $zero_args;
    global $one_args;
    global $two_args;
    global $three_args;
    checkIfBadCountArgs($string, $one_args, $two_args, $zero_args);

    foreach($argsArThree as $a) {
        if ((strtoupper($string[0]) == "ADD" || strtoupper($string[0]) == "SUB" || strtoupper($string[0]) == "MUL" || strtoupper($string[0]) == "IDIV")) {
            if (preg_match('/^\s*(GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array1) && preg_match($symbol_regex, $string[2], $output_array2) && preg_match($symbol_regex, $string[3], $output_array3)) {
                $firsttype = 'var';
                $secondtype = 'var';
                $thirdtype = 'var';
                if (preg_match('/^\s*int@[+|-]?[0-9]+\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*int@/', '', $string[2]);
                    $secondtype = 'int';
                }
                elseif (preg_match('/^\s*bool@(true|false)\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*bool@/', '', $string[2]);
                    $secondtype = 'bool';
                }
                elseif (preg_match('/^\s*string@\S*\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*string@/', '', $string[2]);
                    $secondtype = 'string';
                }
                elseif (preg_match('/^\s*nil@\S*\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*nil@/', '', $string[2]);
                    $secondtype = 'nil';
                }

                if (preg_match('/^\s*int@[+|-]?[0-9]+\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*int@/', '', $string[3]);
                    $thirdtype = 'int';
                }
                elseif (preg_match('/^\s*bool@(true|false)\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*bool@/', '', $string[3]);
                    $thirdtype = 'bool';
                }
                elseif (preg_match('/^\s*string@\S*\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*string@/', '', $string[3]);
                    $thirdtype = 'string';
                }
                elseif (preg_match('/^\s*nil@\S*\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*nil@/', '', $string[3]);
                    $thirdtype = 'nil';
                }

                helpForThreeArgsXML($a, $xw, $string, $firsttype, $secondtype, $thirdtype);
                return;
            }else{
                exit(23);
            }
        }
        elseif (strtoupper($string[0]) == "LT" || strtoupper($string[0]) == "GT" || strtoupper($string[0]) == "EQ") {
            if (preg_match('/^\s*(GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array1) && preg_match($symbol_regex, $string[2], $output_array2) && preg_match($symbol_regex, $string[3], $output_array3)) {
                $firsttype = 'var';
                $secondtype = 'var';
                $thirdtype = 'var';
                if (preg_match('/^\s*int@[+|-]?[0-9]+\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*int@/', '', $string[2]);
                    $secondtype = 'int';
                }
                elseif (preg_match('/^\s*bool@(true|false)\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*bool@/', '', $string[2]);
                    $secondtype = 'bool';
                }
                elseif (preg_match('/^\s*string@\S*\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*string@/', '', $string[2]);
                    $secondtype = 'string';
                }
                elseif (preg_match('/^\s*nil@\S*\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*nil@/', '', $string[2]);
                    $secondtype = 'nil';
                }

                if (preg_match('/^\s*int@[+|-]?[0-9]+\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*int@/', '', $string[3]);
                    $thirdtype = 'int';
                }
                elseif (preg_match('/^\s*bool@(true|false)\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*bool@/', '', $string[3]);
                    $thirdtype = 'bool';
                }
                elseif (preg_match('/^\s*string@\S*\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*string@/', '', $string[3]);
                    $thirdtype = 'string';
                }
                elseif (preg_match('/^\s*nil@\S*\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*nil@/', '', $string[3]);
                    $thirdtype = 'nil';
                }

                helpForThreeArgsXML($a, $xw, $string, $firsttype, $secondtype, $thirdtype);
                return;
            }else{
                exit(23);
            }
        }
        elseif (strtoupper($string[0]) == "AND" || strtoupper($string[0]) == "OR") {
            if (preg_match('/^\s*(GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array1) && preg_match($symbol_regex, $string[2], $output_array2) && preg_match($symbol_regex, $string[3], $output_array3)) {
                $firsttype = 'var';
                $secondtype = 'var';
                $thirdtype = 'var';
                if (preg_match('/^\s*int@[+|-]?[0-9]+\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*int@/', '', $string[2]);
                    $secondtype = 'int';
                }
                elseif (preg_match('/^\s*bool@(true|false)\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*bool@/', '', $string[2]);
                    $secondtype = 'bool';
                }
                elseif (preg_match('/^\s*string@\S*\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*string@/', '', $string[2]);
                    $secondtype = 'string';
                }
                elseif (preg_match('/^\s*nil@\S*\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*nil@/', '', $string[2]);
                    $secondtype = 'nil';
                }

                if (preg_match('/^\s*int@[+|-]?[0-9]+\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*int@/', '', $string[3]);
                    $thirdtype = 'int';
                }
                elseif (preg_match('/^\s*bool@(true|false)\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*bool@/', '', $string[3]);
                    $thirdtype = 'bool';
                }
                elseif (preg_match('/^\s*string@\S*\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*string@/', '', $string[3]);
                    $thirdtype = 'string';
                }
                elseif (preg_match('/^\s*nil@\S*\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*nil@/', '', $string[3]);
                    $thirdtype = 'nil';
                }

                helpForThreeArgsXML($a, $xw, $string, $firsttype, $secondtype, $thirdtype);
                return;
            }else{
                exit(23);
            }
        }
        elseif (strtoupper($string[0]) == "CONCAT" || strtoupper($string[0]) == "STRI2INT") {
            if (preg_match('/^\s*(GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array1) && preg_match($symbol_regex, $string[2], $output_array2) && preg_match($symbol_regex, $string[3], $output_array3)) {
                $firsttype = 'var';
                $secondtype = 'var';
                $thirdtype = 'var';
                if (preg_match('/^\s*int@[+|-]?[0-9]+\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*int@/', '', $string[2]);
                    $secondtype = 'int';
                }
                elseif (preg_match('/^\s*bool@(true|false)\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*bool@/', '', $string[2]);
                    $secondtype = 'bool';
                }
                elseif (preg_match('/^\s*string@\S*\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*string@/', '', $string[2]);
                    $secondtype = 'string';
                }
                elseif (preg_match('/^\s*nil@\S*\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*nil@/', '', $string[2]);
                    $secondtype = 'nil';
                }

                if (preg_match('/^\s*int@[+|-]?[0-9]+\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*int@/', '', $string[3]);
                    $thirdtype = 'int';
                }
                elseif (preg_match('/^\s*bool@(true|false)\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*bool@/', '', $string[3]);
                    $thirdtype = 'bool';
                }
                elseif (preg_match('/^\s*string@\S*\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*string@/', '', $string[3]);
                    $thirdtype = 'string';
                }
                elseif (preg_match('/^\s*nil@\S*\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*nil@/', '', $string[3]);
                    $thirdtype = 'nil';
                }
                helpForThreeArgsXML($a, $xw, $string, $firsttype, $secondtype, $thirdtype);
                return;
            }else{
                exit(23);
            }

        }
        elseif (strtoupper($string[0]) == "GETCHAR" || strtoupper($string[0]) == "SETCHAR"){
            if (preg_match('/^\s*(GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array1) && preg_match($symbol_regex, $string[2], $output_array2) && preg_match($symbol_regex, $string[3], $output_array3)) {
                $firsttype = 'var';
                $secondtype = 'var';
                $thirdtype = 'var';
                if (preg_match('/^\s*int@[+|-]?[0-9]+\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*int@/', '', $string[2]);
                    $secondtype = 'int';
                }
                elseif (preg_match('/^\s*bool@(true|false)\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*bool@/', '', $string[2]);
                    $secondtype = 'bool';
                }
                elseif (preg_match('/^\s*string@\S*\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*string@/', '', $string[2]);
                    $secondtype = 'string';
                }
                elseif (preg_match('/^\s*nil@\S*\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*nil@/', '', $string[2]);
                    $secondtype = 'nil';
                }

                if (preg_match('/^\s*int@[+|-]?[0-9]+\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*int@/', '', $string[3]);
                    $thirdtype = 'int';
                }
                elseif (preg_match('/^\s*bool@(true|false)\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*bool@/', '', $string[3]);
                    $thirdtype = 'bool';
                }
                elseif (preg_match('/^\s*string@\S*\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*string@/', '', $string[3]);
                    $thirdtype = 'string';
                }
                elseif (preg_match('/^\s*nil@\S*\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*nil@/', '', $string[3]);
                    $thirdtype = 'nil';
                }

                helpForThreeArgsXML($a, $xw, $string, $firsttype, $secondtype, $thirdtype);
                return;
            }else{
                exit(23);
            }
        }

        elseif (strtoupper($string[0]) == "JUMPIFEQ" || strtoupper($string[0]) == "JUMPIFNEQ") {
            $jumps += 1;
            if (preg_match('/^\s*[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array1) && preg_match($symbol_regex, $string[2], $output_array2) && preg_match($symbol_regex, $string[3], $output_array3)) {
                $firsttype = 'label';
                $secondtype = 'var';
                $thirdtype = 'var';
                if (preg_match('/^\s*int@[+|-]?[0-9]+\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*int@/', '', $string[2]);
                    $secondtype = 'int';
                }
                elseif (preg_match('/^\s*bool@(true|false)\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*bool@/', '', $string[2]);
                    $secondtype = 'bool';
                }
                elseif (preg_match('/^\s*string@\S*\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*string@/', '', $string[2]);
                    $secondtype = 'string';
                }
                elseif (preg_match('/^\s*nil@\S*\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*nil@/', '', $string[2]);
                    $secondtype = 'nil';
                }

                if (preg_match('/^\s*int@[+|-]?[0-9]+\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*int@/', '', $string[3]);
                    $thirdtype = 'int';
                }
                elseif (preg_match('/^\s*bool@(true|false)\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*bool@/', '', $string[3]);
                    $thirdtype = 'bool';
                }
                elseif (preg_match('/^\s*string@\S*\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*string@/', '', $string[3]);
                    $thirdtype = 'string';
                }
                elseif (preg_match('/^\s*nil@\S*\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*nil@/', '', $string[3]);
                    $thirdtype = 'nil';
                }

                helpForThreeArgsXML($a, $xw, $string, $firsttype, $secondtype, $thirdtype);
                return;
            } else {
                exit(23);
            }
        }
    }

    exit(22);
}

#################################################
#                                               #
#                     HELP                      #
#                                               #
#################################################

function printHelp()
{
    echo "PARSE.PHP [PHP 7.4]\n";
    echo "Autor: Peter Vinarcik\n";
    echo "Login: xvinar00\n\n";
    echo "O programe:\n";
    echo "Program nacita zo standardneho vstupu zdrojovy kod v IPPcode20\n";
    echo "a spusti sa lexikalna a syntakticka analyza, ktora overi spravnost kodu\n";
    echo "a vypise na standardni vystup XML reprezentaciu programu.\n\n";
    echo "Parametre:\n";
    echo "--help - Vypise tuto napovedu\n\n";
    echo "Chybove navratove kody:\n";
    echo "21 - Chybajuca hlavicka v zdrojovom kode\n";
    echo "22 - Neznamy alebo chybny operacny kod v zdrojovom kode\n";
    echo "23 - Ina lexikalna alebo syntakticka chyba v zdrojovom kode\n";
    exit(0);
}