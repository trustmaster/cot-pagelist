<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=global
[END_COT_EXT]
==================== */

defined('COT_CODE') or die('Wrong URL');

require_once cot_incfile('page', 'module');

/**
 * Generates page list widget
 * @param  string  $tpl        Template code
 * @param  integer $items      Number of items to show. 0 - all items
 * @param  string  $order      Sorting order (SQL)
 * @param  string  $condition  Custom selection filter (SQL)
 * @param  string  $cat        Custom parent category code
 * @param  string  $blacklist  Category black list, semicolon separated
 * @param  string  $whitelist  Category white list, semicolon separated
 * @param  boolean $sub        Include subcategories TRUE/FALSE
 * @param  string  $pagination Pagination parameter name for the URL, e.g. 'pld'. Make sure it does not conflict with other paginations.
 * @param  boolean $noself     Exclude the current page from the rowset for pages.
 * @return string              Parsed HTML
 */
function pagelist($tpl = 'pagelist', $items = 0, $order = '', $condition = '', $cat = '', $blacklist = '', $whitelist = '', $sub = true, $pagination = 'pld', $noself = false)
{
	global $db, $db_pages, $env, $structure;

	// Compile lists
	if (!empty($blacklist))
	{
		$bl = explode(';', $blacklist);
	}

	if (!empty($whitelist))
	{
		$wl = explode(';', $whitelist);
	}

	// Get the cats
	$cats = array();
	if (empty($cat) && (!empty($blacklist) || !empty($whitelist)))
	{
		// All cats except bl/wl
		foreach ($structure['page'] as $code => $row)
		{
			if (!empty($blacklist) && !in_array($code, $bl)
				|| !empty($whitelist) && in_array($code, $wl))
			{
				$cats[] = $code;
			}
		}
	}
	elseif (!empty($cat) && $sub)
	{
		// Specific cat
		$cats = cot_structure_children('page', $cat, $sub);
	}

	if (count($cats) > 0)
	{
		if (!empty($blacklist))
		{
			$cats = array_diff($cats, $bl);
		}

		if (!empty($whitelist))
		{
			$cats = array_intersect($cats, $wl);
		}

		$where_cat = "AND page_cat IN ('" . implode("','", $cats) . "')";
	}
	elseif (!empty($cat))
	{
		$where_cat = "AND page_cat = " . $db->quote($cat);
	}

	$where_condition = (empty($condition)) ? '' : "AND $condition";

	if ($noself && defined('COT_PAGES') && !defined('COT_LIST'))
	{
		global $id;
		$where_condition .= " AND page_id != $id";
	}

	// Get pagination number if necessary
	if (!empty($pagination))
	{
		list($pg, $d, $durl) = cot_import_pagenav($pagination, $items);
	}
	else
	{
		$d = 0;
	}

	// Display the items
	$t = new XTemplate(cot_tplfile($tpl, 'plug'));

	/* === Hook === */
	foreach (array_merge(cot_getextplugins('customnews.query'), cot_getextplugins('pagelist.query')) as $pl)
	{
		include $pl;
	}
	/* ===== */

	$totalitems = $db->query("SELECT COUNT(*)
		FROM $db_pages AS p $cns_join_tables
		WHERE page_state='0' $where_cat $where_condition")->fetchColumn();

	$sql_order = empty($order) ? '' : "ORDER BY $order";
	$sql_limit = ($items > 0) ? "LIMIT $d, $items" : '';

	$res = $db->query("SELECT p.* $cns_join_columns
		FROM $db_pages AS p $cns_join_tables
		WHERE page_state='0' $where_cat $where_condition
		$sql_order $sql_limit");

	$jj = 1;
	while ($row = $res->fetch())
	{
		$t->assign(cot_generate_pagetags($row, "PAGE_ROW_"));

		$t->assign(array(
			'PAGE_ROW_NUM'     => $jj,
			'PAGE_ROW_ODDEVEN' => cot_build_oddeven($jj),
			'PAGE_ROW_RAW'     => $row
		));

		/* === Hook === */
		foreach (cot_getextplugins('pagelist.loop') as $pl)
		{
			include $pl;
		}
		/* ===== */

		$t->parse("MAIN.PAGE_ROW");
		$jj++;
	}

	// Render pagination
	$url_area = defined('COT_PLUG') ? 'plug' : $env['ext'];
	if (defined('COT_LIST'))
	{
		global $list_url_path;
		$url_params = $list_url_path;
	}
	elseif (defined('COT_PAGES'))
	{
		global $al, $id, $pag;
		$url_params = empty($al) ? array('c' => $pag['page_cat'], 'id' => $id) :  array('c' => $pag['page_cat'], 'al' => $al);
	}
	else
	{
		$url_params = array();
	}
	$url_params[$pagination] = $durl;
	$pagenav = cot_pagenav($url_area, $url_params, $d, $totalitems, $items, $pagination);

	$t->assign(array(
		'PAGE_TOP_PAGINATION'  => $pagenav['main'],
		'PAGE_TOP_PAGEPREV'    => $pagenav['prev'],
		'PAGE_TOP_PAGENEXT'    => $pagenav['next'],
		'PAGE_TOP_FIRST'       => $pagenav['first'],
		'PAGE_TOP_LAST'        => $pagenav['last'],
		'PAGE_TOP_CURRENTPAGE' => $pagenav['current'],
		'PAGE_TOP_TOTALLINES'  => $totalitems,
		'PAGE_TOP_MAXPERPAGE'  => $items,
		'PAGE_TOP_TOTALPAGES'  => $pagenav['total']
	));

	/* === Hook === */
	foreach (cot_getextplugins('pagelist.tags') as $pl)
	{
		include $pl;
	}
	/* ===== */

	$t->parse();
	return $t->text();
}
