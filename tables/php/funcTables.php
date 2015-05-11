<?php

const ALL = 0;
const WARNING = 1;

function getCount($typeOfQuery){
	switch ($typeOfQuery) {
		case ALL:
			$query = "SELECT id FROM relations";
			break;
		case WARNING:
			$query = "SELECT id FROM relations where not exist(relations.tags,'network')
				or not exist(relations.tags,'complete') or not exist(relations.tags,'osmc:symbol')
				or not exist(relations.tags,'destinations')";
			break;	
		case ERROR:
			
			break;
		default:
			
			break;
	}
	
	return pg_num_rows(pg_query($query));
}