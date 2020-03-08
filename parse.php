<?php

#################################################
#                                               #
#                  MAIN PROGRAM                 #
#                                               #
#################################################

$ordercount = 1;

if ($argc == 2) {
    #TODO ROZSIRENIE STATISTIKY
    if (strcmp($argv[1], '--help') == 0) {
        printHelp();
    } else {
        exit(10);
    }
}

elseif ($argc == 1){
    $symbol_regex = '/^\s*((GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*)|(nil@nil)|(int@[+|-]?[0-9]+)|(bool@(true|false))|(string@\S*)\s*$/';
    $xw = xmlwriter_open_memory();
    xmlwriter_set_indent($xw, "  ");
    $res = xmlwriter_set_indent_string($xw, "  ");

    $zero_args = array("CREATEFRAME","PUSHFRAME","POPFRAME","RETURN","BREAK");
    $one_args = array("DEFVAR","CALL","PUSHS","POPS","WRITE","LABEL","JUMP","EXIT","DPRINT");
    $two_args = array("MOVE","INT2CHAR","READ","STRLEN","TYPE","NOT");
    $three_args = array("ADD","SUB","MUL","IDIV","LT","GT","EQ","AND","OR","STRI2INT","CONCAT","GETCHAR","SETCHAR","JUMPIFEQ","JUMPIFNEQ");

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

        if ($line[0] == "\n"){
            continue;
        }
        #ignore comments
        elseif (preg_match('/(.*#)/', $line, $output_line)){
            $updated = substr_replace($output_line[0],"\n",-1);
            #ignore empty lines
            if (strlen($updated) === 1){
                continue;
            }
            else{
                if($firsttime){
                    if(preg_match('/^\s*(.)(i|I)(p|P)(p|P)(c|C)(o|O)(d|D)(e|E)(2)(0)\s*$/', $updated, $output_header)){
                        $firsttime = false;
                    }else{
                        exit(21);
                    }
                }else {
                    #XML
                    syntaxAnalyze($updated, $xw);
                }
            }
        }
        #XML
        else {
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
    xmlwriter_end_element($xw);
    echo xmlwriter_output_memory($xw);
}

else{
    exit(10);
}

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
    global $ordercount;
    $wastherematch = false;

    global $zero_args;
    global $one_args;
    global $two_args;
    global $three_args;
    checkIfBadCountArgs($string, $one_args, $two_args, $three_args);

    foreach($argsArZero as $a) {
        if (strcmp(strtoupper($string[0]), $a) == 0) {
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
    global $ordercount;
    $wastherematch = false;
    global $symbol_regex;

    global $zero_args;
    global $one_args;
    global $two_args;
    global $three_args;
    checkIfBadCountArgs($string, $zero_args, $two_args, $three_args);

    foreach($argsArOne as $a) {
        if (strtoupper($string[0]) == "DEFVAR") { //upravit tak aby to kontrolovalo uz specificke nazvy a to iste aj v twoArgs a threeArgs
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

        elseif (strtoupper($string[0]) == "LABEL" || strtoupper($a) == "CALL") { //upravit tak aby to kontrolovalo uz specificke nazvy a to iste aj v twoArgs a threeArgs
            if(preg_match('/^[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array)) {
                helpForOneArgsXML($a, $xw, $string, 'label');
                $wastherematch = true;
                return;
            }
            else{
                exit(23);
            }
        }

        else if (strtoupper($string[0]) == "WRITE") { //upravit tak aby to kontrolovalo uz specificke nazvy a to iste aj v twoArgs a threeArgs
            if(preg_match($symbol_regex, $string[1], $output_array)) {
                declareWhichXML($a, $xw, $string);
                $wastherematch = true;
                return;
            }else{
                exit(23);
            }
        }

        elseif (strtoupper($string[0]) == "PUSHS") { //upravit tak aby to kontrolovalo uz specificke nazvy a to iste aj v twoArgs a threeArgs
            if(preg_match($symbol_regex, $string[1], $output_array)) {
                declareWhichXML($a, $xw, $string);
                $wastherematch = true;
                return;
            }else{
                exit(23);
            }
        }

        elseif (strtoupper($string[0]) == "POPS") { //upravit tak aby to kontrolovalo uz specificke nazvy a to iste aj v twoArgs a threeArgs
            if (preg_match('/^\s*(GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array)){
                helpForOneArgsXML($a, $xw, $string, 'var');
            }
            else {
                exit(23);
            }
            $wastherematch = true;
            return;
        }

        elseif (strtoupper($string[0]) == "JUMP") { //upravit tak aby to kontrolovalo uz specificke nazvy a to iste aj v twoArgs a threeArgs
            if (preg_match('/^\s*[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array)){
                helpForOneArgsXML($a, $xw, $string, 'label');
            }
            else {
                exit(23);
            }
            $wastherematch = true;
            return;
        }

        elseif (strtoupper($string[0]) == "EXIT") { //upravit tak aby to kontrolovalo uz specificke nazvy a to iste aj v twoArgs a threeArgs
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

        elseif (strtoupper($string[0]) == "DPRINT") { //upravit tak aby to kontrolovalo uz specificke nazvy a to iste aj v twoArgs a threeArgs
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