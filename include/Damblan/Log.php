<?php
/*
   +----------------------------------------------------------------------+
   | PEAR Web site version 1.0                                            |
   +----------------------------------------------------------------------+
   | Copyright (c) 2003-2005 The PEAR Group                               |
   +----------------------------------------------------------------------+
   | This source file is subject to version 2.02 of the PHP license,      |
   | that is bundled with this package in the file LICENSE, and is        |
   | available at through the world-wide-web at                           |
   | http://www.php.net/license/2_02.txt.                                 |
   | If you did not receive a copy of the PHP license and are unable to   |
   | obtain it through the world-wide-web, please send a note to          |
   | license@php.net so we can mail you a copy immediately.               |
   +----------------------------------------------------------------------+
   | Author: Martin Jansen <mj@php.net>                                   |
   +----------------------------------------------------------------------+
   $Id$
*/

require_once 'Log.php';
require_once 'Log/syslog.php';

/**
 * Basic class for logging to syslog
 *
 * @author Martin Jansen <mj@php.net>
 * @extends Log_syslog
 * @version $Revision$
 * @package Damblan
 */
class Damblan_Log extends Log_syslog
{
    var $_name = LOG_SYSLOG;
    var $_ident = 'pearweb';
    var $_mask = null;

    function __construct()
    {
        $this->_mask = Log::MAX(PEAR_LOG_DEBUG);
    }
}
