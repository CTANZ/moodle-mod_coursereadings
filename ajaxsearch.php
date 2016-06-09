<?php

// Even the "AXAX_SCRIPT" init is too heavy - we need to be able to process requests rapidly for auto-suggest.
// Initialise the bare necessities to serve this request.
require_once('minimal_db_init.php');

$type = required_param('t', PARAM_TEXT);
$query = required_param('q', PARAM_TEXT);
$exclude = optional_param('x', 0, PARAM_INT);
$limit = 50; // Limit on number of results

$results = array();
switch ($type) {
	case 'article':
		$ids = array();
		$sql = 'SELECT a.id AS articleid, a.title AS articletitle, a.pagerange, a.author AS periodicalAuthor, s.*
					FROM {coursereadings_article} a INNER JOIN {coursereadings_source} s ON a.source = s.id
					WHERE a.id <> :exclude AND '.$DB->sql_like('a.title', ':title', false);
		if ($articles = $DB->get_records_sql($sql, array('exclude'=>$exclude, 'title'=>$query.'%'), 0, $limit)) {
			foreach ($articles as $article) {
				$results[] = $article;
				$ids[] = $article->articleid;
			}
		}
		if (count($results) < 1) {
			if (count($ids)) {
				list($extrasql, $params) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'articleid', false);
			} else {
				$extrasql = ' IS NOT NULL';
				$params = array();
			}
			$params['title'] = '%'.$query.'%';
			$params['exclude'] = $exclude;
			$sql = 'SELECT a.id AS articleid, a.title AS articletitle, a.pagerange, a.author AS periodicalAuthor, s.*
						FROM {coursereadings_article} a INNER JOIN {coursereadings_source} s ON a.source = s.id
						WHERE a.id '.$extrasql.' AND a.id <> :exclude AND '.$DB->sql_like('a.title', ':title', false);
			if ($fuzzyarticles = $DB->get_records_sql($sql, $params, 0, $limit-count($results))) {
				foreach ($fuzzyarticles as $article) {
					$results[] = $article;
				}
			}
		}
		break;
	case 'articlebyid':
        $query = required_param('q', PARAM_TEXT);
        $sql = 'SELECT a.id, a.title, a.pagerange, a.totalpages, a.author AS periodicalAuthor, a.externalurl, a.doi,
						s.id AS source, s.title AS sourcetitle, s.author AS sourceauthor, s.editor, s.year
					FROM {coursereadings_article} a INNER JOIN {coursereadings_source} s ON a.source = s.id
					WHERE a.id=:articleid';
        if ($article = $DB->get_record_sql($sql, array('articleid'=>$query))) {
            $results = $article;
        } else {
            $results->error = "Couldn't retrieve article from database.  Has it been deleted?  Please refresh the page and try again.";
        }
        break;
	case 'articledoi':
		if (substr($query, 0, 4) === 'doi:') {
			$query = substr($query, 4);
		}
		$sql = 'SELECT a.id AS articleid, a.title AS articletitle, a.pagerange, a.author AS periodicalAuthor, s.*
					FROM {coursereadings_article} a INNER JOIN {coursereadings_source} s ON a.source = s.id
					WHERE '.$DB->sql_like('a.doi', ':query', false).' OR '.$DB->sql_like('a.doi', ':query2', false).' OR '.$DB->sql_like('a.externalurl', ':query3', false);
		if ($articles = $DB->get_records_sql($sql, array('query'=>$query.'%', 'query2'=>'10.'.$query.'%', 'query3'=>$query.'%'), 0, $limit)) {
			$ids = array();
			foreach ($articles as $article) {
				$results[] = $article;
				$ids[] = $article->articleid;
			}
		}
		break;
	case 'source':
		$ids = array();
		$basesql = "SELECT s.*, q.id AS queueid FROM {coursereadings_source} s LEFT JOIN {coursereadings_queue} q ON (q.type='source' and s.id=q.objectid) WHERE s.id <> :exclude AND ";
		if ($sources = $DB->get_records_sql($basesql . $DB->sql_like('title', ':title', false), array('title'=>$query.'%', 'exclude'=>$exclude), 0, $limit)) {
			foreach ($sources as $source) {
				$results[] = $source;
				$ids[] = $source->id;
			}
		}
		if (count($results) < 1) {
			if (count($ids)) {
				list($sql, $params) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'sourceid', false);
			} else {
				$sql = ' IS NOT NULL';
				$params = array();
			}
			$params['title'] = '%'.$query.'%';
			$params['exclude'] = $exclude;
			if ($fuzzysources = $DB->get_records_sql($basesql . 's.id '.$sql.' AND '.$DB->sql_like('title', ':title', false), $params, 0, $limit-count($results))) {
				foreach ($fuzzysources as $source) {
					$results[] = $source;
				}
			}
		}
		break;
	case 'isbn':
		$basesql = "SELECT s.*, q.id AS queueid FROM {coursereadings_source} s LEFT JOIN {coursereadings_queue} q ON (q.type='source' and s.id=q.objectid) WHERE ";
		if ($sources = $DB->get_records_sql($basesql . $DB->sql_like('isbn', ':isbn', false), array('isbn'=>$query.'%'), 0, $limit)) {
			foreach ($sources as $source) {
				$results[] = $source;
			}
		}
		break;
	case 'sourcearticles':
		$sourceid = intval($query);
		if ($articles = $DB->get_records_sql('SELECT a.id AS articleid, a.title AS articletitle, a.pagerange, a.author AS periodicalAuthor, s.* FROM {coursereadings_article} a INNER JOIN {coursereadings_source} s ON a.source = s.id WHERE s.id = :sourceid', array('sourceid'=>$sourceid), 0, $limit)) {
			foreach ($articles as $article) {
				if(empty($article->author) && !empty($article->periodicalAuthor)) {
					$article->author = $article->periodicalAuthor;
				}
				$article->sourcetitle = $article->title;
				$article->title = $article->articletitle;
				unset($article->articletitle);
				$article->sourceid = $article->id;
				$article->id = $article->articleid;
				unset($article->articleid);
				$results[] = $article;
			}
		}
		break;
	case 'xreflookup':
		$results = new stdClass();
		// No get_config due to minimal DB init.
		$configrecord = $DB->get_records_sql('SELECT name, value FROM {config_plugins} WHERE plugin = ? AND name = ?', array('coursereadings', 'crossrefemail'));
		if (!empty($configrecord) && count($configrecord) === 1 && !empty($configrecord['crossrefemail'])) {
			$crossrefemail = $configrecord['crossrefemail']->value;
		} else {
			$crossrefemail = '';
		}
		if (empty($crossrefemail)) {
			$results->error = 'This feature is not currently enabled.';
		} else {
			// Include filelib.php for curl wrapper class.
			require_once($CFG->libdir.'/filelib.php');

			$url = 'https://doi.crossref.org/servlet/query?pid='.$crossrefemail;
			$url .= '&format=json&id='.$query;

			$curl = new curl();

			$data = $curl->get($url, array(), array(
				'CURLOPT_SSL_VERIFYHOST' => 2,
				'CURLOPT_SSL_VERIFYPEER' => true,
			));

			if ($json = json_decode($data)) {
				$source = new stdClass();
				// Default source values - not all source types have the same fields.
				$source->isbn = '';
				$source->volume = '';
				$source->edition = '';
				switch ($json->created->type) {
					case 'journal_article':
						$source->type = 'journal';
						$source->isbn = (count($json->created->ISSN) > 1) ? $json->created->ISSN[1] : $json->created->ISSN[0];
						$source->volume = $json->created->volume;
						$source->edition = $json->created->issue;

						$sql = "SELECT id, title, year, publisher
								FROM {coursereadings_source}
								WHERE type='journal' AND isbn=? AND volume=? AND edition=?";
						if ($dbsource = $DB->get_records_sql($sql, array($source->isbn, $source->volume, $source->edition))) {
							$i = array_shift(array_keys($dbsource));
							$dbsource = $dbsource[$i];
							$source->id = $dbsource->id;
							$source->title = $dbsource->title;
							$source->year = $dbsource->year;
							$source->publisher = $dbsource->publisher;
						} else {
							$source->id = 0; // No source exists yet.
							$source->title = $json->{'container-title'};
							$source->year = $json->issued->{'date-parts'}[0];
							$source->publisher = $json->created->publisher;
						}
						break;
					case 'book_title':
					case 'book_content':
						$source->type = 'book';
						$source->isbn = (count($json->created->ISBN) > 1) ? $json->created->ISBN[1] : $json->created->ISBN[0];

						$sql = "SELECT id, title, year, publisher
								FROM {coursereadings_source}
								WHERE type='book' AND isbn=?";
						if ($dbsource = $DB->get_records_sql($sql, array($source->isbn))) {
							$i = array_shift(array_keys($dbsource));
							$dbsource = $dbsource[$i];
							$source->id = $dbsource->id;
							$source->title = $dbsource->title;
							$source->year = $dbsource->year;
							$source->publisher = $dbsource->publisher;
						} else {
							$source->id = 0; // No source exists yet.
							if ($json->created->type === 'book_title') {
								// Complete book - no "container" exists.
								$source->title = $json->created->title;
							} else {
								// Chapter / part of book, "container" is the source.
								$source->title = $json->{'container-title'};
							}
							$source->year = $json->issued->{'date-parts'}[0];
							$source->publisher = $json->created->publisher;
						}
						break;
					case 'conference_paper':
						$source->type = 'other';
						$source->subtype = 'Conference paper';
						$source->title = $json->{'container-title'};
						$source->year = $json->issued->{'date-parts'}[0];
						$source->publisher = $json->created->publisher;
						$source->id = 0;

						$sql = "SELECT id, title, year, publisher
								FROM {coursereadings_source}
								WHERE type='other' AND subtype='Conference paper' AND title=? AND year=?";
						if ($dbsource = $DB->get_records_sql($sql, array($source->title, $source->year))) {
							$i = array_shift(array_keys($dbsource));
							$dbsource = $dbsource[$i];
							$source->id = $dbsource->id;
						}
						break;
					case 'standard_title':
						$source->type = 'other';
						$source->subtype = 'Other';
						$source->title = $json->created->publisher; // Use publsher name as title for standards.
						$source->year = $json->issued->{'date-parts'}[0];
						$source->publisher = $json->created->publisher;
						$source->id = 0;

						$sql = "SELECT id, title, year, publisher
								FROM {coursereadings_source}
								WHERE type='other' AND subtype='Other' AND title=? AND year=?";
						if ($dbsource = $DB->get_records_sql($sql, array($source->title, $source->year))) {
							$i = array_shift(array_keys($dbsource));
							$dbsource = $dbsource[$i];
							$source->id = $dbsource->id;
						}
						break;
				}
				$results->source = $source;

				$article = new stdClass();
				$article->title = $json->created->title;
				if (empty($json->page)) {
					$article->pagerange = ''; // No page range present for complete books.
				} else {
					$article->pagerange = $json->page;
				}
				$article->author = $json->author[0]->family;
				if (!empty($json->author[0]->given)) {
					$article->author .= ', ' . $json->author[0]->given;
				}
				if (count($json->author) > 1) {
					$max = count($json->author);
					for ($i = 1; $i < $max; $i++) {
						$article->author .= ', ';
						if ($i === ($max - 1)) {
							$article->author .= '& ';
						}
						$article->author .= $json->author[$i]->family;
						if (!empty($json->author[$i]->given)) {
							$article->author .= ', ' . $json->author[$i]->given;
						}
					}
				}
				$results->article = $article;
			} else {
				$results->error = 'No valid response received from Crossref lookup.';
			}

			$curlerrno = $curl->get_errno();

			if (!empty($curlerrno)) {
				$results->error = 'cURL: Error '.$curlerrno.' when calling '.$url;
			}

			$info = $curl->get_info();

			if (isset($info['ssl_verify_result']) and $info['ssl_verify_result'] != 0) {
				$results->error = 'cURL/SSL: Unable to verify remote service response when calling '.$url;
			}

		}
		break;
	case 'draftfile':
		// Include lib so we have coursereadings_find_matching_file().
		require_once($CFG->dirroot.'/mod/coursereadings/lib.php');
		$filename = required_param('f', PARAM_FILE);

		$results['articleid'] = coursereadings_find_matching_file($query, $filename);

		break;

	default:
		$results[] = 'Error - incorrect type specified';
}

header('Content-type: application/json');
echo json_encode($results);
exit;