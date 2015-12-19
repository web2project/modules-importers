<?php
if (!defined('W2P_BASE_DIR')){
  die('You should not access this file directly.');
}

//TODO:  This is the php4 way of parsing XML.  All of these functions should 
//   should be replaced with SimpleXML.

global $stack;
global $reset;
$stack = array();

function startTag($parser, $name, $attrs) {
    global $stack, $reset;
    $reset = false;

    $tag=array("name"=>$name,"attrs"=>$attrs);
    array_push($stack, $tag);
}

function cdata($parser, $cdata) {
    global $stack, $reset, $i;

    if(trim($cdata)) {
        if ($reset) {
            $stack[count($stack)-1]['cdata'] = $cdata;
        } else {
            $stack[count($stack)-1]['cdata'] .= $cdata;
        }
    }
}

function endTag($parser, $name) {
    global $stack, $reset;
    $reset = true;

    $stack[count($stack)-2]['children'][] = $stack[count($stack)-1];
    array_pop($stack);
}

function xmlParse($data) {
    global $stack;

    $xml_parser = xml_parser_create();
    xml_set_element_handler($xml_parser, "startTag", "endTag");
    xml_set_character_data_handler($xml_parser, "cdata");

    $error = xml_parse($xml_parser, $data);
    if(!$error) {
        die(sprintf("XML error: %s at line %d",
        xml_error_string(xml_get_error_code($xml_parser)),
        xml_get_current_line_number($xml_parser)));
    }

    xml_parser_free($xml_parser);

    return $stack;
}

function rebuildTree($tree) {
	foreach($tree as $tag) {
		if (isset($tag['children'])) {
            $newTree[$tag['name']][] = rebuildTree($tag['children']);
        } else {
            $newTree[$tag['name']] = $tag['cdata'];
        }
	}
	
	return $newTree;
}