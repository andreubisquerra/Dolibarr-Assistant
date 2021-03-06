<?php
/**
 * Copyright (C) 2019    Andreu Bisquerra    <jove@bisquerra.com>
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
 *	\file       htdocs/takepos/invoice.php
 *	\ingroup    takepos
 *	\brief      Page to generate section with list of lines
 */

// if (! defined('NOREQUIREUSER'))    define('NOREQUIREUSER', '1');    // Not disabled cause need to load personalized language
// if (! defined('NOREQUIREDB'))        define('NOREQUIREDB', '1');        // Not disabled cause need to load personalized language
// if (! defined('NOREQUIRESOC'))        define('NOREQUIRESOC', '1');
// if (! defined('NOREQUIRETRAN'))        define('NOREQUIRETRAN', '1');
if (!defined('NOCSRFCHECK'))    { define('NOCSRFCHECK', '1'); }
if (!defined('NOTOKENRENEWAL')) { define('NOTOKENRENEWAL', '1'); }
if (!defined('NOREQUIREMENU'))  { define('NOREQUIREMENU', '1'); }
if (!defined('NOREQUIREHTML'))  { define('NOREQUIREHTML', '1'); }
if (!defined('NOREQUIREAJAX'))  { define('NOREQUIREAJAX', '1'); }

require '../../main.inc.php';

//$langs->loadLangs(array("bills", "cashdesk"));
$langs->loadLangs(array("bills", "dolibarrassistant@dolibarrassistant"));

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$text = GETPOST('text', 'alpha');
$text=urldecode($text);


// Retrieve opened conversation
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."dolibarrassistant_conversation WHERE finished=0";
$resql = $db->query($sql);
if($resql){
	$obj = $db->fetch_object($resql);
	$conversation_id=$obj->rowid;
	$conversation_subject=$obj->subject;
}
// Create new one if necesary
if ($conversation_id<1)
{
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."dolibarrassistant_conversation VALUES (NULL, NULL, '', '', NULL, NULL, 0);";
	$resql = $db->query($sql);
	$conversation_id=1;
	
	// Retrieve created conversation
	$sql = "SELECT * FROM ".MAIN_DB_PREFIX."dolibarrassistant_conversation WHERE finished=0";
	$resql = $db->query($sql);
	if($resql){
		$obj = $db->fetch_object($resql);
		$conversation_id=$obj->rowid;
	}
}

// If text writted, add to conversation
if ($text!="" and $text!="reset")
{
	// Save user text to the conversation
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."dolibarrassistant_messages VALUES (NULL, $conversation_id, '$text', 0);";
	$resql = $db->query($sql);
	
	// If subject not started
	if ($conversation_subject=="")
	{
		if (strpos($text, $langs->trans('CreateBill')) !== false)
		{
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."dolibarrassistant_messages VALUES (NULL, $conversation_id, '".$langs->trans('WhatCustomer')."', 1);";
			$resql = $db->query($sql);
			$sql = "UPDATE ".MAIN_DB_PREFIX."dolibarrassistant_conversation SET subject='CreateBill', question='WhatCustomer' where rowid=$conversation_id";
			$resql = $db->query($sql);
		}
	}
	else if ($conversation_subject=='CreateBill')
	{
		if ($obj->question=="WhatCustomer")
		{
			$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."societe";
			$sql.=" WHERE nom LIKE '%".$text."%'";
			$resql=$db->query($sql);
			$obj=$db->fetch_object($resql);
			if ($obj->rowid>0)
			{
				//Create invoice
				require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
				$invoice = new Facture($db);
				$invoice->socid=$obj->rowid;
				$invoice->date = dol_now();
				$invoiceid = $invoice->create($user);
				//End Create invoice
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."dolibarrassistant_messages VALUES (NULL, $conversation_id, '".$langs->trans('WhatProduct')."', 1);";
				$resql = $db->query($sql);
				$sql = "UPDATE ".MAIN_DB_PREFIX."dolibarrassistant_conversation SET question='WhatProduct', fk_soc=".$obj->rowid.", fk_invoice=".$invoiceid." where rowid=$conversation_id";
				$resql = $db->query($sql);
			}
			else
			{
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."dolibarrassistant_messages VALUES (NULL, $conversation_id, '".$langs->trans('CustomerNotFound')."', 1);";
				$resql = $db->query($sql);
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."dolibarrassistant_messages VALUES (NULL, $conversation_id, '".$langs->trans('WhatCustomer')."', 1);";
				$resql = $db->query($sql);
			}
		}

		
		else if ($obj->question=="AnythingElse" and $text=="No, ".$langs->trans('Validate'))
		{
			require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
			$invoice = new Facture($db);
			$invoice->fetch($obj->fk_invoice);
			$invoice->validate($user);
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."dolibarrassistant_messages VALUES (NULL, $conversation_id, '".$invoice->ref." ".$langs->trans('Validated')."', 1);";
			$resql = $db->query($sql);
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."dolibarrassistant_messages VALUES (NULL, $conversation_id, '".$langs->trans('NeedAnythingElse')."', 1);";
			$resql = $db->query($sql);
		}
		
		else if ($obj->question=="WhatProduct" or $obj->question=="AnythingElse")
		{
			$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."product";
			$sql.=" WHERE label LIKE '%".$text."%'";
			$resql=$db->query($sql);
			$obj_prod=$db->fetch_object($resql);
			if ($obj_prod->rowid>0)
			{
				require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
				$invoice = new Facture($db);
				$invoice->fetch($obj->fk_invoice);
				$prod = new Product($db);
				$prod->fetch($obj_prod->rowid);
				$price = $prod->price;
				$tva_tx = $prod->tva_tx;
				$price_ttc = $prod->price_ttc;
				$price_base_type = $prod->price_base_type;
				$idoflineadded = $invoice->addline($prod->description, $price, 1, $tva_tx, $prod->localtax1_tx, $prod->localtax2_tx, $idproduct, $prod->remise_percent, '', 0, 0, 0, '', $price_base_type, $price_ttc, $prod->type, -1, 0, '', 0, 0, null, 0, '', 0, 100, '', null, 0);
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."dolibarrassistant_messages VALUES (NULL, $conversation_id, '".$langs->trans('AnythingElse')."', 1);";
				$resql = $db->query($sql);
				$sql = "UPDATE ".MAIN_DB_PREFIX."dolibarrassistant_conversation SET question='AnythingElse' where rowid=$conversation_id";
				$resql = $db->query($sql);
			}
			else
			{
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."dolibarrassistant_messages VALUES (NULL, $conversation_id, '".$langs->trans('ProductNotFound')."', 1);";
				$resql = $db->query($sql);
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."dolibarrassistant_messages VALUES (NULL, $conversation_id, '".$langs->trans('WhatProduct')."', 1);";
				$resql = $db->query($sql);
			}
		}
		
		
	}
	
}

if ($text=="reset")
{
	$sql = "UPDATE ".MAIN_DB_PREFIX."dolibarrassistant_conversation SET finished=1 where rowid=$conversation_id";
	$resql = $db->query($sql);
	$conversation_id=0;
}


 
?>
<style>
HTML  CSS Result
EDIT ON
 @import url(http://weloveiconfonts.com/api/?family=typicons);
[class*="typicons-"]:before {
  font-family: 'Typicons', sans-serif;
}

.module {
  width: 300px;
  margin: 20px auto;
}

.top-bar {
  background: #666;
  color: white;
  padding: 0.5rem;
  position: relative;
  overflow: hidden;
}
.top-bar h1 {
  display: inline;
  font-size: 1.1rem;
}
.top-bar .typicons-message {
  display: inline-block;
  padding: 4px 5px 2px 5px;
}
.top-bar .typicons-minus {
  position: relative;
  top: 3px;
}
.top-bar .left {
  float: left;
}
.top-bar .right {
  float: right;
  padding-top: 5px;
}
.top-bar > * {
  position: relative;
}
.top-bar::before {
  content: "";
  position: absolute;
  top: -100%;
  left: 0;
  right: 0;
  bottom: -100%;
  opacity: 0.25;
  background: radial-gradient(#ffffff, #000000);
  animation: pulse 1s ease alternate infinite;
}

.discussion {
  list-style: none;
  background: #e5e5e5;
  margin: 0;
  padding: 0 0 50px 0;
}
.discussion li {
  padding: 0.5rem;
  overflow: hidden;
  display: flex;
}
.discussion .avatar {
  width: 40px;
  position: relative;
}
.discussion .avatar img {
  display: block;
  width: 100%;
}

.other .avatar:after {
  content: "";
  position: absolute;
  top: 0;
  right: 0;
  width: 0;
  height: 0;
  border: 5px solid white;
  border-left-color: transparent;
  border-bottom-color: transparent;
}

.self {
  justify-content: flex-end;
  align-items: flex-end;
}
.self .messages {
  order: 1;
  border-bottom-right-radius: 0;
}
.self .avatar {
  order: 2;
}
.self .avatar:after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 0;
  width: 0;
  height: 0;
  border: 5px solid white;
  border-right-color: transparent;
  border-top-color: transparent;
  box-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
}

.messages {
  background: white;
  padding: 10px;
  border-radius: 2px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}
.messages p {
  font-size: 0.8rem;
  margin: 0 0 0.2rem 0;
}
.messages time {
  font-size: 0.7rem;
  color: #ccc;
}

@keyframes pulse {
  from {
    opacity: 0;
  }
  to {
    opacity: 0.5;
  }
}


</style>


 <section class="module">
  
  <header class="top-bar">
    
    <div class="left">
      <span class="icon typicons-message"></span>
      <h1>Dolibarr Assistant</h1>
    </div>
    
    <div class="right">
      <span class="icon typicons-minus"></span>
      <span class="icon typicons-times"></span>
    </div>
    
  </header>
  
  <ol class="discussion">
  
    <li class="other">
      <div class="avatar">
        <img src="../../favicon.ico" />
      </div>
      <div class="messages">
        <p><?php echo $langs->trans("IamAssistant", $user->login);?></p>
      </div>
    </li>
	
	<?php
	$sql="SELECT * FROM ".MAIN_DB_PREFIX."dolibarrassistant_messages where fk_conversation=".$conversation_id;
    $resql = $db->query($sql);
    $rows = array();
    while($row = $db->fetch_array($resql)){
        //$rows[] = $row;
		echo '
		<li class="';
		if ($row[3]==0) echo 'self';
		if ($row[3]==1) echo 'other';
		echo '">
			<div class="avatar">';
			if ($row[3]==0) echo '<img src="../../public/theme/common/user_anonymous.png" />';
			if ($row[3]==1) echo '<img src="../../favicon.ico" />';
			echo '
			</div>
			<div class="messages">
				<p>'.$row[2].'</p>
			</div>
		</li>
		';
    }
	?>
	
  </ol>
  
</section>
