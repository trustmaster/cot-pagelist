<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=pagelist.query
[END_COT_EXT]
==================== */

defined('COT_CODE') or die('Wrong URL');

if ($cfg['plugin']['pagelist']['comments'] && cot_plugin_active('comments'))
{
	global $db_com;

	require_once cot_incfile('comments', 'plug');

	$cns_join_columns .= ", (SELECT COUNT(*) FROM `$db_com` WHERE com_area = 'page' AND com_code = p.page_id) AS com_count";
}

?>
