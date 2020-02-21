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
    $xw = xmlwriter_open_memory();
    xmlwriter_set_indent($xw, "  ");
    $res = xmlwriter_set_indent_string($xw, "  ");

    xmlwriter_start_document($xw, '1.0', 'UTF-8');
    xmlwriter_start_element($xw, 'program');
    xmlwriter_start_attribute($xw, 'language');
    xmlwriter_text($xw, 'IPPcode20');
    xmlwriter_end_attribute($xw);

    $firsttime = true;
    while($line = fgets(STDIN)){
        #ignore comments
        if (preg_match('/(.*#)/', $line, $output_line)){
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
                        echo "FUCK THIS SHIT IM ENDING RIGHT NOW!\n";
                        #TODO CHECK FOR RIGHT ERROR CODES
                        exit(30);
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
                    echo "FUCK THIS SHIT IM ENDING RIGHT NOW!\n";
                    #TODO CHECK FOR RIGHT ERROR CODES
                    exit(30);
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
    #TODO ODKONTROLOVAT NAZVY FUNKKCII
    $zero_args = array("CREATEFRAME","PUSHFRAME","POPFRAME","RETURN","BREAK");
    $one_args = array("DEFVAR","CALL","PUSHS","POPS","WRITE","LABEL","JUMP","EXIT","DPRINT");
    $two_args = array("MOVE","INT2CHAR","READ","STRLEN","TYPE","NOT");
    $three_args = array("ADD","SUB","MUL","IDIV","LT","GT","EQ","AND","OR","STRI2INT","CONCAT","GETCHAR","SETCHAR","JUMPIFEQ","JUMPIFNEQ");
    $removedws = preg_split('/\s+/', $string);
    $backtostring = implode(" ", $removedws);
    $edited = explode(" ", $backtostring);

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
        #TODO CHECK FOR RIGHT ERROR CODES
        exit(1);
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

    foreach($argsArZero as $a) {
        if (strcmp($string[0], $a) == 0) {
            #TODO SET TO XML
            xmlwriter_start_element($xw, 'instruction');
            xmlwriter_start_attribute($xw, 'order');
            xmlwriter_text($xw, "$ordercount");
            xmlwriter_end_attribute($xw);
            xmlwriter_start_attribute($xw, 'opcode');
            xmlwriter_text($xw, "$string[0]");
            xmlwriter_end_attribute($xw);
            xmlwriter_end_element($xw);
            $ordercount += 1;
            $wastherematch = true;
        }
    }
    if (!$wastherematch) {
        #TODO CHECK FOR RIGHT ERROR CODES
        exit(1);
    }
}

#################################################
#                                               #
#                   1 ARGUMENT                  #
#                                               #
#################################################

function oneArgs($string, $argsArOne, $xw)
{
    #TODO CHECK FOR REGEX
    global $ordercount;
    $wastherematch = false;

    #var REGEX: '/^\s(GF|LF|TF)@[a-zA-Z\-_$&%*!?]*\s*$/'
    #Label REGEX: '/^[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/'

    foreach($argsArOne as $a) {
        if (strtoupper($string[0]) == "DEFVAR") { //upravit tak aby to kontrolovalo uz specificke nazvy a to iste aj v twoArgs a threeArgs
            #TODO CHECK FOR REGEX
            if (preg_match('/^\s*(GF|LF|TF)@[a-zA-Z\-_$&%*!?]*\s*$/', $string[1], $output_array)) {
                //TU BUDE REGEX PRE DEFVAR LF TF GF @ xxx a takto pokracovat aj pre LABEL a vsetky ostatne veci... odkontrolovat a ideme dalej
                helpForOneArgsXML($a, $xw, $string, 'var');
                $wastherematch = true;
                return;
            }
            else{
                #TODO CHECK FOR RIGHT ERROR CODES
                exit(50);
            }
        }

        elseif (strtoupper($string[0]) == "LABEL" || strtoupper($a) == "CALL") { //upravit tak aby to kontrolovalo uz specificke nazvy a to iste aj v twoArgs a threeArgs
            if(preg_match('/^[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array)) {
                helpForOneArgsXML($a, $xw, $string, 'label');
                $wastherematch = true;
                return;
            }
            else{
                #TODO CHECK FOR RIGHT ERROR CODES
                exit(50);
            }
        }

        else if (strtoupper($string[0]) == "WRITE") { //upravit tak aby to kontrolovalo uz specificke nazvy a to iste aj v twoArgs a threeArgs
            declareWhichXML($a, $xw, $string);
            $wastherematch = true;
            return;
        }

        elseif (strtoupper($string[0]) == "PUSHS") { //upravit tak aby to kontrolovalo uz specificke nazvy a to iste aj v twoArgs a threeArgs
            declareWhichXML($a, $xw, $string);
            $wastherematch = true;
            return;
        }

        elseif (strtoupper($string[0]) == "POPS") { //upravit tak aby to kontrolovalo uz specificke nazvy a to iste aj v twoArgs a threeArgs
            if (preg_match('/^(GF|LF|TF)@[a-zA-Z\-_$&%*!?]*\s*$/', $string[1], $output_array)){
                helpForOneArgsXML($a, $xw, $string, 'var');
            }
            else {
                #TODO CHECK FOR RIGHT ERROR CODES
                exit(50);
            }
            $wastherematch = true;
            return;
        }

        elseif (strtoupper($string[0]) == "JUMP") { //upravit tak aby to kontrolovalo uz specificke nazvy a to iste aj v twoArgs a threeArgs
            if (preg_match('/^\s*[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array)){
                helpForOneArgsXML($a, $xw, $string, 'label');
            }
            else {
                #TODO CHECK FOR RIGHT ERROR CODES
                exit(50);
            }
            $wastherematch = true;
            return;
        }

        elseif (strtoupper($string[0]) == "EXIT") { //upravit tak aby to kontrolovalo uz specificke nazvy a to iste aj v twoArgs a threeArgs
            if (preg_match('/^\s*int@[0-9]+\s*$/', $string[1], $output_array)){
                helpForOneArgsXML($a, $xw, $string, 'var');
            }
            else {
                #TODO CHECK FOR RIGHT ERROR CODES
                exit(50);
            }
            $wastherematch = true;
            return;
        }

        elseif (strtoupper($string[0]) == "DPRINT") { //upravit tak aby to kontrolovalo uz specificke nazvy a to iste aj v twoArgs a threeArgs
            declareWhichXML($a, $xw, $string);
            $wastherematch = true;
            return;
        }
    }
    if (!$wastherematch) {
        #TODO CHECK FOR RIGHT ERROR CODES
        exit(1);
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
    elseif (preg_match('/^\s*int@[0-9]+\s*$/', $string[1], $output_array)){
        $string[1] = preg_replace('/^\s*int@/', '', $string[1]);
        helpForOneArgsXML($a, $xw, $string, 'int');
    }
    else {
        #TODO CHECK FOR RIGHT ERROR CODES
        exit(50);
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
    xmlwriter_text($xw, "$string[0]");
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
    elseif (preg_match('/^\s*int@[0-9]+\s*$/', $string[2], $output_array)){
        $string[2] = preg_replace('/^\s*int@/', '', $string[2]);
        helpForTwoArgsXML($a, $xw, $string, $firsttype, 'int');
    }
    else {
        #TODO CHECK FOR RIGHT ERROR CODES
        exit(50);
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
    xmlwriter_text($xw, "$string[0]");
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
    $wastherematch = false;
    foreach($argsArTwo as $a) {

        if (strtoupper($string[0]) == "MOVE"){
            if(preg_match('/^(GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array)) {
                declareWhichXML2($a, $xw, $string, 'var');
                $wastherematch = true;
                return;
            }
            else{
                #TODO CHECK FOR RIGHT ERROR CODES
                exit(50);
            }
        }
        elseif (strtoupper($string[0]) == "INT2CHAR"){
            if(preg_match('/^(GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array)) {
                declareWhichXML2($a, $xw, $string, 'var');
                $wastherematch = true;
                return;
            }
            else{
                #TODO CHECK FOR RIGHT ERROR CODES
                exit(50);
            }
        }
        elseif (strtoupper($string[0]) == "READ"){
            if(preg_match('/^(GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array) && preg_match('/^\s*(int|bool|string)\s*$/',$string[2],$output_array)) {
                helpForTwoArgsXML($a, $xw, $string, 'var', 'type');
                $wastherematch = true;
                return;
            }
            else{
                #TODO CHECK FOR RIGHT ERROR CODES
                exit(50);
            }
        }
        elseif (strtoupper($string[0]) == "STRLEN"){
            #TODO DRUHY ARGUMENT ESTE
            if(preg_match('/^(GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array) && preg_match('/^\s*string@\S*\s*$/', $string[2], $output_array)) {
                declareWhichXML2($a, $xw, $string, 'var');
                $wastherematch = true;
                return;
            }
            else{
                #TODO CHECK FOR RIGHT ERROR CODES
                exit(50);
            }
        }
        elseif (strtoupper($string[0]) == "TYPE"){
            if(preg_match('/^(GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array)) {
                if (preg_match('/^\s*string@\S*\s*$/', $string[1], $output_array)) {
                    helpForTwoArgsXML($a, $xw, $string, 'var','string');
                } elseif (preg_match('/^\s*bool@(true|false)\s*$/', $string[1], $output_array)) {
                    helpForTwoArgsXML($a, $xw, $string, 'var','bool');
                } elseif (preg_match('/^\s*int@[0-9]+\s*$/', $string[1], $output_array)) {
                    helpForTwoArgsXML($a, $xw, $string, 'var','int');
                } else {
                    #TODO CHECK FOR RIGHT ERROR CODES
                    exit(50);
                }
                $wastherematch = true;
                return;
            }
            else{
                #TODO CHECK FOR RIGHT ERROR CODES
                exit(50);
            }
        }
        elseif (strtoupper($string[0]) == "NOT"){
            if (preg_match('/^\s*(GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array) && preg_match('/^\s*bool@(true|false)\s*$/', $string[2], $output_array)) {
                $string[2] = preg_replace('/^\s*bool@/', '', $string[2]);
                helpForTwoArgsXML($a, $xw, $string, 'var','bool');
                $wastherematch = true;
                return;
            }
            else{
                #TODO CHECK FOR RIGHT ERROR CODES
                exit(50);
            }
        }
    }

    if (!$wastherematch) {
        #TODO CHECK FOR RIGHT ERROR CODES
        exit(1);
    }
    #TODO CHECK FOR REGEX
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
    xmlwriter_text($xw, "$string[0]");
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
    #TODO CHECK FOR REGEX
    global $ordercount;
    foreach($argsArThree as $a) {
        if ((strtoupper($string[0]) == "ADD" || strtoupper($string[0]) == "SUB" || strtoupper($string[0]) == "MUL" || strtoupper($string[0]) == "IDIV")) {
            if (preg_match('/^\s*(GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array1) && preg_match('/^\s*(int@[0-9]+)|((GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*)\s*$/', $string[2], $output_array2) && preg_match('/^\s*(int@[0-9]+)|((GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*)\s*$/', $string[3], $output_array3)) {
                $firsttype = 'var';
                $secondtype = 'var';
                $thirdtype = 'var';
                if (preg_match('/^\s*int@[0-9]+\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*int@/', '', $string[2]);
                    $secondtype = 'int';
                }
                if (preg_match('/^\s*int@[0-9]+\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*int@/', '', $string[3]);
                    $thirdtype = 'int';
                }

                helpForThreeArgsXML($a, $xw, $string, $firsttype, $secondtype, $thirdtype);
                return;
            }
            else {
                #TODO CHECK FOR RIGHT ERROR CODES
                exit(50);
            }
        }
        elseif (strtoupper($string[0]) == "LT" || strtoupper($string[0]) == "GT" || strtoupper($string[0]) == "EQ") {
            if (preg_match('/^\s*(GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array1) && preg_match('/^\s*((GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?])|(int@[0-9]+)|(bool@(true|false))|(string@\S*)\s*$/', $string[2], $output_array) && preg_match('/^\s*((GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?])|(int@[0-9]+)|(bool@(true|false))|(string@\S*)\s*$/', $string[3], $output_array)){
                $firsttype = 'var';
                $secondtype = 'var';
                $thirdtype = 'var';
                if (preg_match('/^\s*int@[0-9]+\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*int@/', '', $string[2]);
                    $secondtype = 'int';
                }
                if (preg_match('/^\s*bool@(true|false)\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*bool@/', '', $string[2]);
                    $secondtype = 'bool';
                }
                if (preg_match('/^\s*string@\S*\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*string@/', '', $string[2]);
                    $secondtype = 'string';
                }
                if (preg_match('/^\s*int@[0-9]+\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*int@/', '', $string[3]);
                    $thirdtype = 'int';
                }
                if (preg_match('/^\s*bool@(true|false)\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*bool@/', '', $string[3]);
                    $thirdtype = 'bool';
                }
                if (preg_match('/^\s*string@\S*\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*string@/', '', $string[3]);
                    $thirdtype = 'string';
                }

                helpForThreeArgsXML($a, $xw, $string, $firsttype, $secondtype, $thirdtype);
                return;
            }
        }
        #TODO ESTE RAZ TOTO DOBRE POZRIET AJ NOT V TWOARGS
        elseif (strtoupper($string[0]) == "AND" || strtoupper($string[0]) == "OR") {
            if (preg_match('/^\s*(GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array) && preg_match('/^\s*bool@(true|false)\s*$/', $string[2], $output_array) && preg_match('/^\s*bool@(true|false)\s*$/', $string[3], $output_array)) {
                $string[2] = preg_replace('/^\s*bool@/', '', $string[2]);
                $string[3] = preg_replace('/^\s*bool@/', '', $string[3]);
                helpForThreeArgsXML($a, $xw, $string, 'var', 'bool', 'bool');
                return;
            }
            else{
                #TODO CHECK FOR RIGHT ERROR CODES
                exit(50);
            }
        }
        elseif (strtoupper($string[0]) == "CONCAT") {
            if (preg_match('/^\s*(GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array1) && preg_match('/^\s*((GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?])|(string@\S*)\s*$/', $string[2], $output_array) && preg_match('/^\s*((GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?])|(string@\S*)\s*$/', $string[3], $output_array)) {
                echo "was here";
                $firsttype = 'var';
                $secondtype = 'var';
                $thirdtype = 'var';
                if (preg_match('/^\s*int@[0-9]+\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*int@/', '', $string[2]);
                    $secondtype = 'int';
                }
                if (preg_match('/^\s*string@\S*\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*string@/', '', $string[2]);
                    $secondtype = 'string';
                }
                if (preg_match('/^\s*int@[0-9]+\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*int@/', '', $string[3]);
                    $thirdtype = 'int';
                }
                if (preg_match('/^\s*string@\S*\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*string@/', '', $string[3]);
                    $thirdtype = 'string';
                }

                helpForThreeArgsXML($a, $xw, $string, $firsttype, $secondtype, $thirdtype);
                return;
            }
            else{
                #TODO CHECK FOR RIGHT ERROR CODES
                exit(50);
            }

        }
        elseif (strtoupper($string[0]) == "GETCHAR" || strtoupper($string[0]) == "SETCHAR"){
            if (preg_match('/^\s*(GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array1) && preg_match('/^\s*((GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?])|(int@[0-9]+)|(string@\S*)\s*$/', $string[2], $output_array) && preg_match('/^\s*((GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?])|(int@[0-9]+)|(string@\S*)\s*$/', $string[3], $output_array)) {
                $firsttype = 'var';
                $secondtype = 'var';
                $thirdtype = 'var';
                if (preg_match('/^\s*int@[0-9]+\s*$/', $string[2], $output_array)) {
                    $string[2] = preg_replace('/^\s*int@/', '', $string[2]);
                    $secondtype = 'int';
                }
                if (preg_match('/^\s*string@\S*\s*$/', $string[2], $output_array)) {
                    $string[2] = preg_replace('/^\s*string@/', '', $string[2]);
                    $secondtype = 'string';
                }
                if (preg_match('/^\s*int@[0-9]+\s*$/', $string[3], $output_array)) {
                    $string[3] = preg_replace('/^\s*int@/', '', $string[3]);
                    $thirdtype = 'int';
                }
                if (preg_match('/^\s*string@\S*\s*$/', $string[3], $output_array)) {
                    $string[3] = preg_replace('/^\s*string@/', '', $string[3]);
                    $thirdtype = 'string';
                }

                helpForThreeArgsXML($a, $xw, $string, $firsttype, $secondtype, $thirdtype);
                return;
            }
        }

        elseif (strtoupper($string[0]) == "JUMPIFEQ" || strtoupper($string[0]) == "JUMPIFNEQ") {
            if (preg_match('/^\s*[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?]*\s*$/', $string[1], $output_array1) && preg_match('/^\s*((GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?])|(int@[0-9]+)|(bool@(true|false))|(string@\S*)\s*$/', $string[2], $output_array) && preg_match('/^\s*((GF|LF|TF)@[a-zA-Z\-_$&%*!?][a-zA-Z0-9\-_$&%*!?])|(int@[0-9]+)|(bool@(true|false))|(string@\S*)\s*$/', $string[3], $output_array)){
                $firsttype = 'label';
                $secondtype = 'var';
                $thirdtype = 'var';
                if (preg_match('/^\s*int@[0-9]+\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*int@/', '', $string[2]);
                    $secondtype = 'int';
                }
                if (preg_match('/^\s*bool@(true|false)\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*bool@/', '', $string[2]);
                    $secondtype = 'bool';
                }
                if (preg_match('/^\s*string@\S*\s*$/', $string[2], $output_array)){
                    $string[2] = preg_replace('/^\s*string@/', '', $string[2]);
                    $secondtype = 'string';
                }
                if (preg_match('/^\s*int@[0-9]+\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*int@/', '', $string[3]);
                    $thirdtype = 'int';
                }
                if (preg_match('/^\s*bool@(true|false)\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*bool@/', '', $string[3]);
                    $thirdtype = 'bool';
                }
                if (preg_match('/^\s*string@\S*\s*$/', $string[3], $output_array)){
                    $string[3] = preg_replace('/^\s*string@/', '', $string[3]);
                    $thirdtype = 'string';
                }

                helpForThreeArgsXML($a, $xw, $string, $firsttype, $secondtype, $thirdtype);
                return;
            }
        }
    }

    #TODO CHECK FOR RIGHT ERROR CODES
    exit(1);
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

#TODO FIX REGEX PRE LABEL V JUMP, JUMPIFEQ a vsetky tam kde sa jumpuje na LABEL REGEX no
