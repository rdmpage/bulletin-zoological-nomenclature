<?php

$sheet = "51603 The Bulletin of Zoological Nomenclature - NK copy.tsv";

$sheet_keys = array(
'ItemID',
'SegmentID',
'Volume',
'Issue',
'Date',
'Article Title',
'AuthorIDs',
'Authors',
'StartPage',
'EndPage',
'StartPageURL',
'EndPage URL',
'StartPageID',
'EndPageID',
'Additional Pages',
'case',
'related_case',
'zns',
'Contributors',
);



$sheet_key_map = array(
'ItemID' => 'itemid',
'SegmentID' => 'partid',
'Article Title' => 'title',
'Volume' => 'volume',
'Issue' => 'issue',
'Date' => 'date',
'Authors' => 'authors',
'StartPage' => 'spage',
'EndPage' => 'epage',
'StartPageID' => 'bhl',

'case' => 'case',
'related_case' => 'related_case',
'zns' => 'zns',


);

$std_keys = array_values($sheet_key_map);

$std_keys[] = 'doi';
$std_keys[] = 'year';

array_unshift($std_keys, 'id');

if (0)
{

echo "CREATE TABLE articles\n";
foreach ($std_keys as $k => $v)
{
	echo ",`$v` TEXT NULL\n";

}
echo ");\n";

exit();
}

//echo join("\t", $std_keys) . "\n";

$headings = array();

$row_count = 0;

$filename = $sheet;

$file_handle = fopen($filename, "r");
while (!feof($file_handle)) 
{
	$line = trim(fgets($file_handle));
		
	$row = explode("\t",$line);
	
	$go = is_array($row) && count($row) > 1;
	
	if ($go)
	{
		if ($row_count == 0)
		{
			$headings = $row;		
		}
		else
		{
			$obj = new stdclass;
			
			$std_obj = new stdclass;
			$std_obj->id = 'bnz' . str_pad($row_count, 5, '0', STR_PAD_LEFT);
			
			foreach ($row as $k => $v)
			{
				if ($v != '')
				{
					$obj->{$headings[$k]} = $v;
				}
			}
		
			// print_r($obj);	
			
			foreach ($obj as $k => $v)
			{
				switch ($k)
				{				
					case 'StartPageID':
						$std_obj->{$sheet_key_map[$k]} = $v;
						$std_obj->{$sheet_key_map[$k]} = preg_replace('/https?:\/\/(www.)?biodiversitylibrary.org\/page\//', '', $std_obj->{$sheet_key_map[$k]});
						break;				

					case 'Authors':
						$std_obj->{$sheet_key_map[$k]} = $v;
						$std_obj->{$sheet_key_map[$k]} = preg_replace('/;\s+/', ';', $std_obj->{$sheet_key_map[$k]});
						break;				
						
					case 'Date':
						$std_obj->year = substr($v, 0, 4);
						$std_obj->{$sheet_key_map[$k]} = $v;
						break;
						
					default:
						if (isset($sheet_key_map[$k]))
						{
							$std_obj->{$sheet_key_map[$k]} = $v;
						}
						break;
				
				}
			
			}			
			
			//print_r($std_obj);
			
			/*
			$output = array();	
			
			foreach ($std_keys as $k)
			{
				if (isset($std_obj->{$k}))
				{
					$output[] = $std_obj->{$k};
				}
				else
				{
					$output[] = '';
				}
			}
			echo join("\t", $output) . "\n";
			*/
			
			$keys = array();
			$values = array();

			foreach ($std_obj as $k => $v)
			{
				$keys[] = '"' . $k . '"'; // must be double quotes

				if (is_array($v))
				{
					$values[] = "'" . str_replace("'", "''", json_encode(array_values($v))) . "'";
				}
				elseif(is_object($v))
				{
					$values[] = "'" . str_replace("'", "''", json_encode($v)) . "'";
				}
				elseif (preg_match('/^POINT/', $v))
				{
					$values[] = "ST_GeomFromText('" . $v . "', 4326)";
				}
				else
				{				
					$values[] = "'" . str_replace("'", "''", $v) . "'";
				}					
			}

			$sql = 'REPLACE INTO articles (' . join(",", $keys) . ') VALUES (' . join(",", $values) . ');';					
			$sql .= "\n";		
			
			echo $sql;	
			
			
		}
	}	
	$row_count++;	
	
}	

?>
