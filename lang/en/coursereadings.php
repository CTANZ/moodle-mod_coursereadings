<?php

$string['pluginname'] = 'Course Material';
$string['modulename'] = 'Course Material';
$string['modulenameplural'] = 'Course Materials';
$string['modulename_help'] = '<strong>This module is for making PDF files of copyright materials available to students.</strong>
<ul style="margin:0 5px 0 10px;text-align:left"><li>Click and drag the PDF into your course page or add a "Course Material" module to active the copyright compliance prompts.</li>
<li>Where PDF files are freely available on the internet, please link to these - <strong>do not download them and upload into Learn</strong>.</li>
<li>Journal articles taken from any UC library catalogue should be linked using the URL resource module, <strong>using ezproxy links</strong>.</li></ul>';

$string['coursereadings:addinstance'] = 'Add Course Materials';
$string['coursereadings:manage'] = 'Manage Course Materials';
$string['coursereadings:managesite'] = 'Manage Course Materials (site-wide)';
$string['coursereadings:migratecontent'] = 'Migrate content into Course Materials';
$string['coursereadings:edit'] = 'Edit Course Materials';
$string['coursereadings:view'] = 'View Course Materials';
$string['coursereadings:viewreports'] = 'View Course Materials reports';

// Plugin settings.
$string['requiremodintro'] = "Require description";
$string['requiremodintro_desc'] = 'If enabled, users will be forced to enter a description for each instance of the module.';
$string['enablecombined'] = "Enable combined downloads";
$string['enablecombined_desc'] = 'Allow combined "readers" to be downloaded.  Disable if causing performance issues.';
$string['doiresolver'] = "DOI Resolver";
$string['doiresolver_desc'] = 'DOI Resolver URL - DOIs will be appended to this to form link URLs.';
$string['crossrefemail'] = "Crossref Query Services email address";
$string['crossrefemail_desc'] = "Email address for Crossref Query Services, used to look up DOI metadata.  Leave blank to disable lookup.";
$string['copyrightnoticecombined'] = "Copyright notice file (combined)";
$string['copyrightnoticecombined_desc'] = "Copyright notice to include in combined downloads, as a PDF.";
$string['copyrightnoticearticle'] = "Copyright notice file (article)";
$string['copyrightnoticearticle_desc'] = "Copyright notice to prepend to individual article downloads, as a PDF.";
$string['track_enrolments'] = 'Track course enrolment numbers';
$string['trackedenrolmethods'] = 'Enrolment methods to track';
$string['trackedenrolmethods_desc'] = 'Enrolment methods which should be tracked for student numbers.';
$string['trackedselfenrolpattern'] = 'Self-enrolment user pattern to track';
$string['trackedselfenrolpattern_desc'] = 'Username pattern for self-enrolled users to additionally track as students.';
$string['enroldecreasepercent'] = 'Enrolment decrease threshold (%)';
$string['enroldecreasepercent_desc'] = 'Decreases in number of enrolled students will only be tracked if the decrease is less than this percentage of the original number.';
$string['enroldecreasethreshold'] = 'Enrolment decrease threshold (fixed)';
$string['enroldecreasethreshold_desc'] = 'Decreases in number of enrolled students will only be tracked if the decrease is less than this many students.';
$string['courseidfield'] = 'Course ID field';
$string['courseidfield_desc'] = 'The ID field to match against when separating courses into reporting periods.';

$string['pluginadministration'] = 'Readings administration';
$string['manage'] = 'Manage';
$string['managematerials'] = 'Manage Course Materials';
$string['dnduploadresource'] = 'Add copyright material';
$string['dndupload_resource'] = '<strong>Create file resource</strong><br /><em>(I acknowledge that this is <u>not</u> from a published source)</em>';
$string['dndupload_coursereadings'] = '<strong>Add copyright material</strong><br /><em>(This is from a published source)</em>';
$string['viewcoursematerial'] = 'View all course materials';
$string['orderprint'] = 'Order printed copies';
$string['download_selected_as'] = 'Download selected readings as: ';
$string['download_instance_as'] = 'Download these readings as: ';
$string['download_as_zip'] = 'Zip archive';
$string['download_as_pdf'] = 'Combined PDF';

// Reading upload form fields.
$string['reading_file'] = '<strong>Choose Reading file</strong>';
$string['source_type'] = 'This article is from a: (please select)';
$string['source_book'] = 'book';
$string['source_journal'] = 'journal';
$string['source_other'] = 'other';
$string['source_subtype'] = 'Type of source';
$string['source_subtypes'] = 'Magazine,Newspaper,Conference paper,Case study,Law or Act,Music,Website,Artwork,Brochure/flyer,Other';
$string['title_of_article'] = 'Title of Article (ie, chapter)';
$string['title_of_source'] = 'Title of Source';
$string['author_of_source'] = 'Author  of Source';
$string['editor_of_source'] = 'Editors  of Source';
$string['year_of_publication'] = 'Year  of publication';
$string['publisher'] = 'Publisher  (i.e. Location: Publishing House)';
$string['isbn'] = 'ISBN/ISSN/ASIN  Number';
$string['page_range'] = 'Page  range of copies (ie, 23-48)';
$string['total_pages'] = 'Total page count';
$string['pages'] = 'Total  number of pages in book';
$string['furtherinfo'] = 'Further information';
$string['sourceurl'] = 'Source URL';
$string['externalurl'] = 'Article URL';
$string['doi'] = 'DOI';

$string['volume_number'] = 'Volume  Number';
$string['edition'] = 'Edition of Journal';
$string['author_of_periodical'] = 'Author of Article (if applicable)';
$string['journal_notice'] = '<strong>Does UC have an online subscription to this journal?</strong><br />If so, please add an EZProxy URL or a DOI for the article above.';

$string['article'] = 'Article';
$string['articles'] = 'Articles';
$string['loading'] = 'Loading, please wait...';
$string['confirmRemoveArticle'] = 'Are you sure you want to remove this article?';
$string['addarticle'] = 'Add article';
$string['editarticle'] = 'Edit article';
$string['findarticle'] = 'Find article';
$string['editsource'] = 'Edit source';
$string['findsource'] = 'Find source';
$string['articleusage'] = 'Instances containing this article:';
$string['articlesfromsource'] = 'Articles from this source:';
$string['articlesearchintro'] = '<h3>Search for Articles</h3>Search for articles within Learn by typing into one of the fields below.<br>Results will appear below; click on an article to select it.<br>If it&apos;s not already in the system, you&apos;ll be given the opportunity to add it.<h4>Search by:</h4>';
$string['noresults'] = '<strong>No matching articles found</strong><br />Please refine your search terms, or <button type="button">upload a new article</button>.';
$string['noresults_external'] = '<strong>No matching articles found</strong><br />Please refine your search terms, or <button type="button">add a new external article</button>.';
$string['noresults_brief'] = '<strong>No matching articles found</strong><br />Please refine your search terms.';
$string['noarticlesinsource_brief'] = '<strong>The selected source does not contain any articles</strong><br />Please refine your search terms.';
$string['noresults_source'] = '<strong>No matching sources found</strong><br />Please refine your search terms.';
$string['doi_or_url'] = 'Article DOI or URL';
$string['articleuploadintro'] = '<strong>Upload a new article</strong>';
$string['articlelinkintro'] = '<strong>Or, link to an online article by URL or DOI</strong>';
$string['choosefile'] = 'Choose file';
$string['newfile'] = 'Choose file (if replacing existing file)';

$string['dashboard'] = 'Dashboard';
$string['dashboard_newarticles'] = 'New articles';
$string['dashboard_newsources'] = 'New sources';
$string['dashboard_flaggedarticles'] = 'Flagged articles';
$string['dashboard_flaggedsources'] = 'Flagged sources';
$string['dashboard_possiblebreaches'] = 'Possible license breaches';
$string['dashboard_mergesplitarticle'] = 'Combine split article';
$string['dashboard_findarticle'] = 'Find article';
$string['dashboard_findsource'] = 'Find source';
$string['dashboard_reports'] = 'Reports';
$string['contentreportthiscourse'] = 'View this course\'s content usage';
$string['source_type_editing'] = 'Source type:';
$string['source'] = 'Source:';
$string['scanned'] = 'Total scanned pages:';
$string['scanned_notall'] = 'Not all articles have total page counts';
$string['approve'] = 'Approve';
$string['approve_within_limits'] = 'Approve - within limits';
$string['approve_with_notes'] = 'Approve with notes';
$string['confirmdelete'] = 'Are you sure you want to delete the {$a->type} "{$a->title}"?';
$string['dashboard_deletearticle'] = 'Delete article';
$string['dashboard_deletesource'] = 'Delete source';
$string['dashboard_mergearticle'] = 'Merge article';
$string['dashboard_mergesource'] = 'Merge source';
$string['dashboard_mergearticle_link'] = 'Merge this article into another';
$string['dashboard_mergesource_link'] = 'Merge this source into another';
$string['dashboard_mergearticle_intro'] = 'This article will be <strong>merged into</strong> the article chosen below.';
$string['dashboard_mergesource_intro'] = 'This source will be <strong>merged into</strong> the source chosen below.';
$string['dashboard_mergearticle_findtarget'] = 'Find target article';
$string['dashboard_mergesource_findtarget'] = 'Find target source';
$string['dashboard_mergearticle_target'] = 'Target article';
$string['dashboard_mergesource_target'] = 'Target source';
$string['dashboard_mergearticle_duplicate'] = 'Duplicate article';
$string['dashboard_mergesource_duplicate'] = 'Duplicate source';
$string['dashboard_mergearticle_copmlete'] = 'The selected articles have been merged.';
$string['dashboard_mergearticle_usefile'] = 'Use this file';
$string['dashboard_mergearticle_file'] = 'File to keep';
$string['dashboard_mergearticle_nofile'] = 'N/A - neither article has a file.';
$string['dashboard_mergesource_complete'] = 'The selected sources have been merged.';
$string['dashboard_mergearticle_complete'] = 'The selected articles have been merged.';
$string['dashboard_articlestomerge'] = 'Articles to combine';
$string['dashboard_articlestomerge_intro'] = 'Please select the articles to be combined, and order them as they should appear in the combined article.';
$string['dashboard_newfilename'] = 'Combined file name';
$string['dashboard_notenoughfiles'] = 'Not enough articles specified.  Please choose at least two articles to combine.';
$string['dashboard_mergesplitarticle_complete'] = 'The selected articles have been combined.  Please make any necessary amendments to the combined article, such as removing "part 1" from the title if present, on the next page.';
$string['dashboard_mergesplitarticle_failed'] = 'Something appears to have gone wrong while combining the selected PDFs.  Please report this issue if it persists.';
$string['deleted'] = 'The {$a->type} "{$a->title}" has been deleted.';
$string['dashboard_reportperiod'] = 'Reporting period';
$string['dashboard_reportperiods'] = 'Reporting periods';
$string['reportperiod_name'] = 'Reporting period name';
$string['reportperiod_pattern'] = 'Search pattern';
$string['reportperiod_add_new'] = 'Add a new reporting period';
$string['sqlregexnotsupported'] = 'Your database does not support regular expressions, so the following report contains ALL data (not restricted to the selected reporting period).';
$string['sqlregexnotsupported_reset'] = 'Your database does not support regular expressions, so tracked enrolment figures for a single period cannot be reset.';
$string['trackedenrolments_reset'] = 'Reset tracked enrolment figures';
$string['trackedenrolments_reset_intro'] = 'You have chosen to reset the tracked enrolment figures for the period "{$a}".';
$string['trackedenrolments_reset_warning'] = 'Please ensure that the final report for the chosen period has been generated <strong>before</strong> you reset tracked enrolment figures, as this cannot be undone.';
$string['trackedenrolments_reset_completed'] = 'Tracked enrolment figures for the selected period have been reset.';

$string['contentmigration'] = 'Content Migration';
$string['contentmigrationthiscourse'] = "Migrate this course's content";
$string['migratenewfiles'] = 'Migrate new files';
$string['uploadsourcespreview'] = 'Upload sources preview';
$string['uploadsourcesresult'] = 'Upload sources results';
$string['csvdelimiter'] = 'CSV delimiter';
$string['encoding'] = 'Encoding';
$string['rowpreviewnum'] = 'Preview rows';
$string['uploadsources'] = 'Upload sources';
$string['csvline'] = 'CSV line';
$string['sourcescreated'] = 'Sources created';
$string['errors'] = 'Errors';

$string['articlenotincluded'] = 'Sorry, this article could not be included in the combined PDF file.';
$string['downloadindividually'] = 'Please download it individually.';

$string['event_bundled_pdf_downloaded'] = 'Bundled pdf downloaded';
$string['event_bundled_zip_downloaded'] = 'Bundled zip downloaded';
$string['event_article_downloaded'] = 'Article downloaded';
$string['event_article_notice_failed'] = 'Warning notice failed';

$string['url_resource_viewed'] = 'URL resource viewed';
