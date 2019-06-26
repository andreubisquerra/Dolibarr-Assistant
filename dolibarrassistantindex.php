<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2019      Andreu Bisquerra Gaya<jove@bisquerra.com> 
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       dolibarrassistant/dolibarrassistantindex.php
 *	\ingroup    dolibarrassistant
 *	\brief      Home page of dolibarrassistant top menu
 */

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include substr($tmp, 0, ($i+1))."/main.inc.php";
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include dirname(substr($tmp, 0, ($i+1)))."/main.inc.php";
// Try main.inc.php using relative path
if (! $res && file_exists("../main.inc.php")) $res=@include "../main.inc.php";
if (! $res && file_exists("../../main.inc.php")) $res=@include "../../main.inc.php";
if (! $res && file_exists("../../../main.inc.php")) $res=@include "../../../main.inc.php";
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("dolibarrassistant@dolibarrassistant"));

$action=GETPOST('action', 'alpha');


// Securite acces client
if (! $user->rights->dolibarrassistant->read) accessforbidden();
$socid=GETPOST('socid', 'int');
if (isset($user->societe_id) && $user->societe_id > 0)
{
	$action = '';
	$socid = $user->societe_id;
}

$max=5;
$now=dol_now();


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", $langs->trans("Dolibarr Assistant"));

?>
<style>
.div1{
	height: 500px;
	width: 320px;
	float: left;
	text-align: center;
	box-sizing: border-box;
	overflow: auto;
	/* background-color:white; */

}
</style>
<?php

//print load_fiche_titre($langs->trans("Dolibarr Assistant"), '', 'dolibarrassistant.png@dolibarrassistant');

print '<div class="fichecenter"><div class="fichethirdleft">';
?>
<script>
$( document ).ready(function() {
    sendtochat();
});
function sendtochat() {
	$("#poslines").load(encodeURI("chat.php?text="+$("#search_usertext").val()), function() {
		$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
	});
}
</script>
<div id="poslines" class="div1">
</div>
<div class="fichecenter"><div class="fichethirdleft">
<?php
$htmlname="usertext";
$name="";
print '<input type="text" size="38" id="search_'.$htmlname.'" name="search_'.$htmlname.'" value="'.$name.'" />';
print ajax_autocompleter(0, $htmlname, 'ajax.php', '', 2, 0);
print '<button onclick="sendtochat();">'.$langs->trans("OK").'</button>';
print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


$NBMAX=3;
$max=3;


print '</div></div></div>';

// End of page
llxFooter();
$db->close();
