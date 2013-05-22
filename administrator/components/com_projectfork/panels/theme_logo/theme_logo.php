<?php
/**
* $Id: theme_logo.php 837 2010-11-17 12:03:35Z eaxs $
* @package   Projectfork
* @copyright Copyright (C) 2006-2010 Tobias Kuhn. All rights reserved.
* @license   http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.php
*
* This file is part of Projectfork.
*
* Projectfork is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
*
* Projectfork is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Projectfork.  If not, see <http://www.gnu.org/licenses/gpl.html>.
**/

defined( '_JEXEC' ) or die( 'Restricted access' );

$load = PFload::GetInstance();
$user = PFuser::GetInstance();

$ws   = $user->GetWorkspace();
$logo = $load->Logo($ws);

if($ws && $user->Access('display_details', 'projects')) {
    echo '<a href="'.PFformat::Link("section=projects&task=display_details&id=$ws").'">'.$logo."</a>";
}
else {
    if($user->Access('', 'projects')) {
        echo '<a href="'.PFformat::Link("section=projects").'">'.$logo."</a>";
    }
    else {
        echo '<a href="'.PFformat::Link("").'">'.$logo."</a>";
    }
}
unset($load,$user);
?>