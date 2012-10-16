<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=pagelist.loop
[END_COT_EXT]
==================== */

defined('COT_CODE') or die('Wrong URL');

if ($cfg['plugin']['pagelist']['comments'] && cot_plugin_active('comments'))
{
	$rowe_urlp = empty($row['page_alias']) ? array('c' => $row['page_cat'], 'id' => $row['page_id']) : array('c' => $row['page_cat'], 'al' => $row['page_alias']);
	$t->assign(array(
		'PAGE_ROW_COMMENTS' => cot_comments_link('page', $rowe_urlp, 'page', $row['page_id'], $c, $row),
		'PAGE_ROW_COMMENTS_COUNT' => cot_comments_count('page', $row['page_id'], $row)
	));
}

?>
