<?php
/**
 * Displays a list of propably orphan proposals
 *
 * The <var>$proposalStatiMap</var> array is defined in
 * pearweb/include/pepr/pepr.php.
 *
 * This source file is subject to version 3.0 of the PHP license,
 * that is bundled with this package in the file LICENSE, and is
 * available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.
 * If you did not receive a copy of the PHP license and are unable to
 * obtain it through the world-wide-web, please send a note to
 * license@php.net so we can mail you a copy immediately.
 *
 * @category  pearweb
 * @package   PEPr
 * @author    Tobias Schlitt <toby@php.net>
 * @copyright Copyright (c) 1997-2005 The PHP Group
 * @license   http://www.php.net/license/3_0.txt  PHP License
 * @version   $Id$
 */

function getDays($date) {
    return ceil((time() - $date) / 60 / 60 / 24);
}

// {{{ Fetch for orphan drafts

$sql = <<<EOS
SELECT 
    p.id AS id,
    p.pkg_name AS pkg_name,
    p.user_handle AS user_handle,
    UNIX_TIMESTAMP(p.draft_date) AS draft_date
FROM 
    package_proposals AS p
WHERE 
    p.status = "draft" 
    AND p.draft_date < DATE_ADD(NOW(), INTERVAL -30 DAY)
ORDER BY draft_date DESC
EOS;

$res['orphan_drafts'] = $dbh->getAll($sql, DB_FETCHMODE_ASSOC);

// }}}
// {{{ Fetch orphan proposals

// Fetch proposals with proposal date before 30 days ago

// Get IDs from proposals with comments in the last 30 days
$sql = "SELECT pkg_prop_id FROM package_proposal_comments ppc WHERE FROM_UNIXTIME(timestamp) > DATE_ADD(NOW(), INTERVAL - 30 DAY);";

$resProposals = $dbh->getAll($sql, DB_FETCHMODE_ASSOC);

$proposalIds = array(0 => 0);

foreach ($resProposals as $proposal) {
    $proposalIds[$proposal['id']] = $proposal['id'];
}

// Get IDs from proposals with changes in the last 30 days
$sql = "SELECT pkg_prop_id FROM package_proposal_changelog ppc WHERE FROM_UNIXTIME(timestamp) > DATE_ADD(NOW(), INTERVAL - 30 DAY);";

$resProposals = $dbh->getAll($sql, DB_FETCHMODE_ASSOC);


foreach ($resProposals as $proposal) {
    $proposalIds[$proposal['id']] = $proposal['id'];
}

$sql = "SELECT
    p.id AS id,
    p.pkg_name AS pkg_name,
    p.user_handle AS user_handle,
    UNIX_TIMESTAMP(p.draft_date) AS draft_date,
    UNIX_TIMESTAMP(p.proposal_date) as proposal_date
FROM
    package_proposals AS p
WHERE
    p.status = 'proposal'
    AND p.id NOT IN (".implode(',', $proposalIds).")
    AND p.proposal_date < DATE_ADD(NOW(), INTERVAL -30 DAY)
    ORDER BY draft_date DESC;";

$res['orphan_proposals'] = $dbh->getAll($sql, DB_FETCHMODE_ASSOC);


// }}}
// {{{ Fetch results


// }}} 

response_header('PEPr :: Propably orphan proposals');

// {{{ HTML for orphan drafts
echo '<h1>Status &quot;draft&quot;</h1>';
echo '<table border="0" cellspacing="0">';
echo '<tr>';
echo '<th>Name</th>';
echo '<th>Draft-Date</th>';
echo '<th>Proposer</th>';
echo '</tr>';
$i = 0;
foreach ($res['orphan_drafts'] as $set) {
    echo '<tr style='.(($i++ % 2 == 0) ? '"background-color: #CCCCCC;"' : '').'>';
    echo '<td class="textcell"><a href="/pepr/pepr-proposal-show.php?id='.$set['id'].'">'.$set['pkg_name'].'</a></td>';
    echo '<td class="textcell">'.getDays($set['draft_date']).' days ago<br />('.make_utc_date($set['draft_date']).')</td>';
    echo '<td class="textcell">'.user_link($set['user_handle']).'</td>';
    echo '</tr>';
}
echo '</table>';
// }}}
// {{{ HTML for orphan proposals
echo '<h1>Status &quot;proposal&quot;</h1>';
echo '<table border="0" cellspacing="0">';
echo '<tr>';
echo '<th>Name</th>';
echo '<th>Draft-Date</th>';
echo '<th>Proposal-Date</th>';
// echo '<th>Last change</th>';
// echo '<th>Last comment</th>';
echo '<th>Proposer</th>';
echo '</tr>';
$i = 0;
foreach ($res['orphan_proposals'] as $set) {
    echo '<tr style='.(($i++ % 2 == 0) ? '"background-color: #CCCCCC;"' : '').'>';
    echo '<td class="textcell"><a href="/pepr/pepr-proposal-show.php?id='.$set['id'].'">'.$set['pkg_name'].'</a></td>';
    echo '<td class="textcell">'.getDays($set['draft_date']).' days ago<br />('.make_utc_date($set['draft_date']).')</td>';
    echo '<td class="textcell">'.getDays($set['proposal_date']).' days ago<br /> ('.make_utc_date($set['proposal_date']).')</td>';
//    echo '<td class="textcell">'.getDays($set['latest_change']).' days ago<br /> ('.make_utc_date($set['latest_change']).')</td>';
//    echo '<td class="textcell">'.getDays($set['latest_comment']).' days ago<br /> (<a href="/pepr-comment-show.php?id='.$set['id'].'">'.make_utc_date($set['latest_comment']).'</a>)</td>';
    echo '<td class="textcell">'.user_link($set['user_handle']).'</td>';
    
    echo '</tr>';
}
echo '</table>';
// }}}

