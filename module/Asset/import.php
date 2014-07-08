<?php
/*
	Copyright 2014 - Mhd Sulhan
	Authors:
		- mhd.sulhan (m.shulhan@gmail.com)
*/

require_once "../init.php";

$table = "asset";

$fields = [
	"type_id"
,	"merk"
,	"model"
,	"sn"
,	"barcode"
,	"service_tag"
,	"label"
,	"detail"

,	"warranty_length"
,	"warranty_info"

,	"procurement_id"
,	"procurement_date"
,	"procurement_company"
,	"procurement_price"
];

Jaring::db_init ();

Jaring::db_prepare_insert ($table, $fields);

$import_file	= $_FILES["import_file"]["tmp_name"];
$nchunk			= 1000;

$f		= fopen ($import_file, "r");
$data	= [];
$c		= 0;

while (! feof ($f)) {
	$row = fgetcsv ($f);

	if (FALSE === $row) {
		break;
	}
	if (0 === $c) {
		// skip first row
		$c++;
		continue;
	}

	$data[] = $row;
	$c++;

	// insert per $nchunk row
	if (0 === ($c % $nchunk)) {
		Jaring::$_db->beginTransaction ();

		foreach ($data as $d) {
			Jaring::$_db->execute ($d);
			Jaring::$_db->closeCursor ();
		}

		Jaring::$_db->commit ();

		$data = [];
	}
}

if (count ($data) > 0) {
	Jaring::$_db->beginTransaction ();

	foreach ($data as $d) {
		Jaring::$_db_ps->execute ($d);
		Jaring::$_db_ps->closeCursor ();
	}

	Jaring::$_db->commit ();
}

fclose ($f);

Jaring::$_out["success"]	= true;
Jaring::$_out["data"]		= "File has been imported.";

echo json_encode (Jaring::$_out);
