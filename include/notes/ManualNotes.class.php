<?php
/*
   +----------------------------------------------------------------------+
   | PEAR Web site version 1.0                                            |
   +----------------------------------------------------------------------+
   | Copyright (c) 2001-2007 The PHP Group                                |
   +----------------------------------------------------------------------+
   | This source file is subject to version 2.02 of the PHP license,      |
   | that is bundled with this package in the file LICENSE, and is        |
   | available at through the world-wide-web at                           |
   | http://www.php.net/license/2_02.txt.                                 |
   | If you did not receive a copy of the PHP license and are unable to   |
   | obtain it through the world-wide-web, please send a note to          |
   | license@php.net so we can mail you a copy immediately.               |
   +----------------------------------------------------------------------+
   | Authors: David Coallier <davidc@php.net>                             |
   |                                                                      |
   +----------------------------------------------------------------------+
 */
// {{{ class Manual_Notes
/**
 * Manual Notes
 *
 * This class will be handling most of the
 * manual notes adding, deleting, approving, etc
 *
 * @package pearweb
 * @author  David Coallier <davidc@php.net>
 * @uses    DB
 * @version 1.0
 */
class Manual_Notes
{
    // {{{ properties
    /**
     * Database Connection
     *
     * This variables holds the database connection
     * into a variable.
     *
     * @access protected
     * @var    Object    $dbc  Database Connection
     */
    var $dbc;

    /**
     * Notes table
     *
     * This is the variable that holds
     * the name of the manual notes table.
     *
     * @access protected
     * @var    string    $notesTableName The notes table name
     */
    var $notesTableName = 'manual_notes';

    // }}}
    // {{{ php5 Constructor
    function __construct()
    {
        global $dbh;
        $this->dbc = $dbh;
    }
    // }}}
    // {{{ public function addComment
    /**
     * Add a comment
     *
     * This function will add a comment to the database
     * using the credentials passed to it.
     *
     * @access public
     * @param  string $pageUrl  The page url
     * @param  string $userName The user adding the comment
     * @param  string $note     The note to add
     * @param  string $approved Is it approved ? "Default: pending"
     */
    function addComment($pageUrl, $userName, $note, $approved = 'pending')
    {
        $user = isset($GLOBALS['auth_user']) ? $GLOBALS['auth_user']->handle : '';
        if ($user) {
            $sql = "
                INSERT INTO {$this->notesTableName}
                (page_url, user_name, user_handle, note_text, note_time,
                 note_approved, note_approved_by, note_deleted)
                VALUES (?, ?, ?, ?, NOW(), ?, ?, 0)
            ";

            // always approve pear.dev account holder comments, moderate others
            $res = $this->dbc->query($sql, array($pageUrl, $userName, $user, $note,
                auth_check('pear.dev') ? 'yes' : $approved,
                auth_check('pear.dev') ? $user : ''));
        } else {
            $sql = "
                INSERT INTO {$this->notesTableName}
                (page_url, user_name, user_handle, note_text, note_time,
                 note_approved, note_approved_by, note_deleted)
                VALUES (?, ?, ?, ?, NOW(), ?, null, 0)
            ";

            $res = $this->dbc->query($sql, array($pageUrl, $userName, $user, $note, $approved));
        }

        if (PEAR::isError($res)) {
            return $res;
        }

        $this->_compileComment($this->dbc->getOne('SELECT LAST_INSERT_ID()'), $note);
        return true;
    }
    // }}}

    function _compileComment($id, $note)
    {
        $split = explode('<?php', $note);
        if (count($split) == 1) {
            // no PHP code
            $compiled = nl2br(htmlspecialchars($note));
        } else {
            // compile PHP code
            $compiled = nl2br(htmlspecialchars(array_shift($split)));
            foreach ($split as $segment) {
                $segment = explode('?>', $segment);
                $compiled .= highlight_string('<?php' . $segment[0] . '?>', true);
                if (isset($segment[1])) {
                    $compiled .= nl2br(htmlspecialchars($segment[1]));
                }
            }
        }
        $sql = 'UPDATE ' . $this->notesTableName . ' SET note_compiled = ? WHERE note_id = ?';
        $this->dbc->query($sql, array($compiled, $id));
    }
    // {{{ public function getSingleCommentById
    /**
     * Get a single comment by id
     *
     * This function will retrieve the single
     * comment's information by note_id.
     *
     * @access public
     * @param  integer  $noteId  The note id to retrieve
     * @return mixed    $res     Error on query fail and associative
     *                           array on success.
     */
    function getSingleCommentById($noteId)
    {
        $sql = "
            SELECT note_id, page_url, user_name, user_handle,
            note_compiled as note_text, note_time, note_approved,
            note_approved_by, note_deleted
             FROM {$this->notesTableName}
              WHERE note_id = ?";
        $res = $this->dbc->getRow($sql, array($noteId), DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            return $res;
        }

        return $res;
    }
    // }}}
    // {{{ public function getPageComments
    /**
     * Get Page Comments
     *
     * This function will get the comments depending
     * on whether a method will need approved, unapproved
     * pending comments, etc. (Per manual page)
     *
     * @access public
     * @param  string      $url    The url of the comments
     * @param  string|bool $status The status of the comment.. whether
     *                             it's approved, unapproved, pending.  If
     *                             a boolean is passed in, determine whether to
     *                             display approved/pending, or just approved
     * @param  bool        $all    if true, return all comments matching this status
     *
     * @return mixed  $res    It returns an error object if there was an error
     *                        executing the query, will return an empty array
     *                        if there was nothing returned from the query, or
     *                        this will return an associative array of the comments
     *                        per page.
     */
    function getPageComments($url, $status = '1', $all = false)
    {

        if ($all) {
            $sql = "
                SELECT note_id, page_url, user_name, user_handle,
                    note_compiled as note_text, note_time, note_approved,
                    note_approved_by, note_deleted, note_text as unfiltered_note
                 FROM {$this->notesTableName}
                  WHERE
                  note_approved = ?
                  ORDER BY note_time DESC
            ";

            $res = $this->dbc->getAll($sql, array($status), DB_FETCHMODE_ASSOC);
        } else {
            if ($status === true) {
                $sql = "
                    SELECT note_id, page_url, user_name, user_handle,
                    note_compiled as note_text, note_time, note_approved,
                    note_approved_by, note_deleted, note_text as unfiltered_note
                     FROM {$this->notesTableName}
                      WHERE page_url = ?
                      AND (note_approved = 'yes' OR note_approved = 'pending')
                     ORDER BY note_time DESC
                ";
                $res = $this->dbc->getAll($sql, array($url), DB_FETCHMODE_ASSOC);
            } elseif ($status === false) {
                $sql = "
                    SELECT note_id, page_url, user_name, user_handle,
                    note_compiled as note_text, note_time, note_approved,
                    note_approved_by, note_deleted, note_text as unfiltered_note
                     FROM {$this->notesTableName}
                      WHERE page_url = ?
                      AND note_approved = 'yes'
                     ORDER BY note_time DESC
                ";
                $res = $this->dbc->getAll($sql, array($url), DB_FETCHMODE_ASSOC);
            } else {
                $sql = "
                    SELECT note_id, page_url, user_name, user_handle,
                    note_compiled as note_text, note_time, note_approved,
                    note_approved_by, note_deleted, note_text as unfiltered_note
                     FROM {$this->notesTableName}
                      WHERE page_url = ?
                      AND note_approved = ?
                     ORDER BY note_time DESC
                ";

                $res = $this->dbc->getAll($sql, array($url, $status), DB_FETCHMODE_ASSOC);
            }
        }

        if (PEAR::isError($res)) {
            return $res;
        }

        return (array)$res;
    }
    // }}}
    // {{{ public function updateCommentList
    /**
     * Update Comment List
     *
     * This function will update a current comment (status, note text, url,
     * username, etc)
     *
     * @access public
     * @param  integer $noteId   The id of the note to update
     *
     * @param  string $status    The status of the note, default = 'pending'
     *
     * @return mixed  $res       An error if an error object occured with the query
     */
    function updateCommentList($noteIds, $status)
    {
        $qs = array();
        $noteIdList = array($status);
        foreach ($noteIds as $noteId) {
            $noteIdList[]   = $noteId;
            $qs[] = 'note_id = ?';
        }
        $qs = implode(' OR ', $qs);

        $sql = "
            UPDATE {$this->notesTableName}
             SET note_approved = ?
              WHERE $qs
              LIMIT ?
        ";
        $noteIdList[] = count($noteIdList);

        $res = $this->dbc->query($sql, $noteIdList);
        if (PEAR::isError($res)) {
            return $res;
        }

        return true;
    }
    // }}}
    // {{{ public function updateComment
    /**
     * Update Comment
     *
     * This function will update a current comment (status, note text, url,
     * username, etc)
     *
     * @access public
     * @param  integer $noteId   The id of the note to update
     * @param  string  $url      The url of the page that the
     *                           note belongs to.
     *
     * @param  string  $userName The user[name|address] of the author
     *                           of the note.
     *
     * @param  string $approved  The status of the note, default = 'pending'
     *
     * @return mixed  $res       An error if an error object occured with the query
     */
    function updateComment($noteId, $url, $userName, $approved)
    {
        $sql = "
            UPDATE {$this->notesTableName}
             SET page_url   = ?,
                 user_name  = ?,
                 note_approved   = ?
              WHERE note_id = ?
              LIMIT 1
        ";

        $res = $this->dbc->query($sql, array($url, $userName, $approved, $noteId));
        if (PEAR::isError($res)) {
            return $res;
        }

        return true;
    }
    // }}}
    // {{{ public function deleteComments
    /**
     * Delete Comments
     *
     * This function will delete a comment by it's note_id
     * This function will mainly be used by administrators and
     * people with enough karma to manage comments.
     *
     * @access public
     * @param  Array $note_ids  The array of the notes to delete
     *
     * @return Mixed   $res      An error object if query was erroneous, bool
     *                           if it was successful
     */
    function deleteComments($note_ids)
    {
        if (!is_array($note_ids)) {
            return false;
        }

        /**
         * Let's just format the note ids so they are simple to
         * read within an IN()
         */
        $notes = "'" . implode(', ', $note_ids) . "'";

        $sql = "
            UPDATE {$this->notesTableName}
             SET note_deleted = 1, note_approved='no'
              WHERE note_id IN($notes)
        ";

        $res = $this->dbc->query($sql);
        if (PEAR::isError($res)) {
            return $res;
        }

        return true;
    }
    // }}}
    // {{{ public function deleteSingleComment
    /**
     * Delete a single comment
     *
     * This function will delete a single comment
     * by it's id.
     *
     * @access public
     * @param  Integer $note_id  The note id to delete
     * @return Mixed   $res      Error object if query is an error
     *                           otherwise return a bool on success
     */
    function deleteSingleComment($note_id)
    {
        $res = $this->deleteComments(array($note_id));
        if (PEAR::isError($res)) {
            return $res;
        }

        return true;
    }
    // }}}

    function display($comment)
    {
        // MySQL 4.1 displays timestamps as if they were datetimes, so make
        // sure this doesn't break on upgrade
        if (is_numeric($comment['note_time'])) {
            $pretime = strptime($comment['note_time'], '%Y%m%d%H%M%S');
            $mytime = mktime($pretime['tm_hour'], $pretime['tm_min'], $pretime['tm_sec'],
                   $pretime['tm_mon'] + 1, $pretime['tm_mday'], $pretime['tm_year'] + 1900);
            $time = date('Y-m-d H:i', $mytime - date('Z', $mytime)) . ' UTC';
        } else {
            $date = strtotime($comment['note_time']);
            $time = date('Y-m-d H:i', $date - date('Z', $date)) . ' UTC';
        }
        $noteId     =  (int)$comment['note_id'];
        $userHandle = $comment['user_handle'] ?
            '<a href="/user/' . $comment['user_handle'] . '">' . $comment['user_handle'] .
            '</a>' :
            $this->obfuscateAnonLink($comment['user_name']);
        $pending    = $comment['note_approved'] == 'pending';
        $id = $comment['page_url'];
        $comment    = $comment['note_text'];
        $linkUrl    = '<a href="#' . $noteId . '">' . $time . '</a>';
        $linkName   = '<a name="' . $noteId . '"></a>';
        include PEARWEB_TEMPLATEDIR . '/notes/note.tpl.php';
    }

    // {{{ public function obfuscateAnonLink
    /**
     * Obfuscate Anonymous link
     *
     * This function will take a parameter and
     * make it obfuscated in a manner that no
     * script can find @ . , etc. This is the same
     * method used for bugs and all mailto_links
     * on the site (site-wide)
     *
     * @access public
     * @param  string $text   The text to obfuscate
     * @return string $obText The text obfuscated
     */
    public function obfuscateAnonLink($text)
    {
        $tmp = '';
        for ($i = 0, $l = strlen($text); $i<$l; $i++) {
            if ($i % 2) {
                $tmp .= '&#' . ord($text[$i]) . ';';
            } else {
                $tmp .= '&#x' . dechex(ord($text[$i])) . ';';
            }
        }
        return $tmp;
    }
    // }}}
}
// }}}
