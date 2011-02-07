<?php
//Added deposits and withdrawls, including max values
//Added the commentary/log thingy at the bottom of the page.

	$dwid = httpget("dwid");
	$subop = httpget('subop');
	$g = httpget('g');
	$amount = httppost('amount');
	if ($amount == "") $amount = httpget('amount');
	if (!is_numeric($amount)) $amount = 0;

	page_header("Coffers");

	$sql = "SELECT ownerid,name,gold,gems,type FROM " . db_prefix("dwellings") . " WHERE dwid='$dwid'";
	$result = db_query($sql);
	$row = db_fetch_assoc($result);
	$type = $row['type'];
	$ownerid=$row['ownerid'];
	$cofferdeps = get_module_setting("maxcofferdeps")-get_module_pref("cofferdeps"); 
	$cofferwiths = get_module_setting("maxcofferwiths")-get_module_pref("cofferwiths"); 
	
	switch ($subop){
		case "with":
			if ($cofferwiths > 0){
				$amount = abs((int)$amount);
				$cofg = $row[$g];
				$xfer = get_module_setting("gemsxfer",$type);
				if($g == "gold") $xfer = get_module_setting("goldxfer",$type)*$session['user']['level'];
				if ($amount == 0) $amount = $cofg;
				if ($amount > $cofg){
					output("You don't have enough %s available in your coffers.",translate_inline($g));
				}elseif(($amount>$xfer) && $xfer>0){
					output("You are not allowed to transfer more than %s %s per transaction.",$xfer,translate_inline($g));
				}else{
					debuglog("withdrew $amount $g from dwelling $dwid in ".$session['user']['location']);
					$tamount = $cofg - $amount;
					$session['user'][$g]+=$amount;
					$message = sprintf_translate("::withdrew `4%s %s%s`&.", $amount, $g=="gems"?"`%":"`^", translate_inline($g));	
					require_once("lib/commentary.php");
					injectrawcomment("coffers-$dwid", $session['user']['acctid'], $message);
					db_query("UPDATE ".db_prefix("dwellings")." SET $g=$g-$amount WHERE dwid=$dwid");
					increment_module_pref("cofferwiths",1);
					$cofferwiths--;
					output("`2You withdraw %s %s.",$amount,translate_inline($g));
					output("`n`@The coffers now hold a balance of `^%s`@ %s.`n",$tamount,translate_inline($g));
				}
			}
			break;
		case "dep":
			if ($cofferdeps > 0){
				$amount = abs((int)$amount);
				if ($amount > 0){
					$cofg = $row[$g];			
					$sql2 = "SELECT SUM($g) AS $g FROM ".db_prefix("dwellings")."  WHERE ownerid=$ownerid AND status=1";
					$result2 = db_query($sql2);
					$row2 = db_fetch_assoc($result2);
					$globalg = $row2[$g];
					$xfer = get_module_setting("gemsxfer",$type);
					if($g == "gold") $xfer = get_module_setting("goldxfer",$type)*$session['user']['level'];
					if ($amount > $session['user'][$g]){
						output("Not enough %s on hand to deposit.",translate_inline($g));
					}elseif((($amount + $globalg) > get_module_setting("maxcoffer$g")) && get_module_setting("maxcoffer$g")>0){
						output("The owner has reached their global limit for coffers. $globalg -");
					}elseif((($amount + $cofg) > get_module_setting("max$g",$type)) && get_module_setting("max$g",$type)!=12345){
						output("This %s`0 cannot hold that amount.",translate_inline(get_module_setting("dwname",$type)));
					}elseif(($amount>$xfer) && $xfer>0){
						output("You are not allowed to transfer more than %s %s per transaction.",$xfer,translate_inline($g));
					}else{
						// Added check to remove commentary post after refreshing or posting a comment.
						debuglog("deposited $amount $g in dwelling $dwid in ".$session['user']['location']);
						$tamount = $cofg + $amount;
						$row[$g] = $tamount;
						$session['user'][$g]-=$amount;
						$message = sprintf_translate("::deposited `4%s %s%s`&.", $amount, $g=="gems"?"`%":"`^", $g);
						require_once("lib/commentary.php");
						injectrawcomment("coffers-$dwid", $session['user']['acctid'], $message);
						db_query("UPDATE ".db_prefix("dwellings")." SET $g=$g+$amount WHERE dwid=$dwid");
						increment_module_pref("cofferdeps",1);
						$cofferdeps--;
						output("`2You deposited %s %s.",$amount,translate_inline($g));
						output("`n`@The coffers now hold a balance of `^%s`@ %s.`n",$tamount,translate_inline($g));
					}
				}
			}
			break;	
		}
		if($subop != ""){
			$sql = "SELECT gold,gems,type FROM " . db_prefix("dwellings") . " WHERE dwid='$dwid'";
			$result = db_query($sql);
			$row = db_fetch_assoc($result);
			$type = $row['type'];
			output("`n`n");
		}
		output("`2You walk up to your coffers, open them and try to decide what you want to do with your gold and gems.`n`n");
	
		$coffersgold = $row['gold'];
		$coffersgems = $row['gems'];
		$dwgold = translate_inline("gold");
		$dwgems = translate_inline("gems");

		if(get_module_setting("maxgold",$type)!=0){
	        $goldwith = get_module_setting("goldxfer",$type)*$session['user']['level'];
	        $golddep = $goldwith;
	        $sql = "SELECT gold FROM ".db_prefix("dwellings")." WHERE dwid=$dwid";
	      	$result = db_query($sql);
	        $row = db_fetch_assoc($result);
	        $gold = $row['gold'];
	        if($gold < $goldwith) $goldwith = $gold;
	        
	        $gold2 = $session['user']['gold'];
	        if($gold2 < $golddep) $golddep = $gold2;
	        if($golddep > get_module_setting("maxgold",$type)-$gold 
				&& get_module_setting("maxgold",$type) != "12345") 
					$golddep = get_module_setting("maxgold",$type)-$gold;
	        
	    }
		if(get_module_setting("maxgems",$type) != 0){
	        $gemwith = get_module_setting("gemsxfer",$type);
	        $gemdep = $gemwith;
	        $sql = "SELECT gems FROM ".db_prefix("dwellings")." WHERE dwid=$dwid";
	      	$result = db_query($sql);
	        $row = db_fetch_assoc($result);
	        $gems = $row['gems'];
	        if($gems < $gemwith) $gemwith = $gems;

	        $gems2 = $session['user']['gems'];
	        if($gems2 < $gemdep) $gemdep = $gems2;
	        if($gemdep > get_module_setting("maxgems",$type)-$gems 
				&& get_module_setting("maxgems",$type)!="12345") 
					$gemdep = get_module_setting("maxgems",$type)-$gems;
	    }
		rawoutput("<table border=0 cellpadding=2 cellspacing=1 bgcolor='#999999' width=95% align=center>");

		$maxallow = translate_inline(", the maximum amount allowed is");		
		$doot_coffer = translate_inline("Dwelling's Coffers");
		$goldcof = translate_inline("Gold in Coffers");
		$gemscof = translate_inline("Gems in Coffers");
		$maxallow = translate_inline(", the maximum amount allowed is");
		rawoutput("<tr><td colspan=2 class='trhead' style='text-align:center;'>$doot_coffer</td></tr>");
		rawoutput("<tr height=30px class='trlight'><td>");
		output_notl($goldcof);
		rawoutput("</td><td>");
		output_notl("`^%s `0%s%s `^%s `0%s.",$coffersgold,$dwgold,$maxallow,get_module_setting("maxgold",$type),$dwgold);
		rawoutput("</td></tr>");
		rawoutput("<tr height=30px class='trlight'><td>");
		output_notl($gemscof);
		rawoutput("</td><td>");
		output_notl("`%%s `0%s%s `%%s `0%s.",$coffersgems,$dwgems,$maxallow,get_module_setting("maxgems",$type),$dwgems);
		rawoutput("</td></tr>");
		
		$doot_deposit = translate_inline("Coffers - Deposit");
		rawoutput("<tr><td colspan=2 class='trhead' style='text-align:center;'>$doot_deposit</td></tr>");

		$trdepositgold = translate_inline("Deposit gold");
		$trdepositgems = translate_inline("Deposit gems");
		$trdepositmax = translate_inline("Deposit max");
		$trwithdrawgold = translate_inline("Withdraw gold");
		$trwithdrawgems = translate_inline("Withdraw gems");
		$trwithmax = translate_inline("Withdraw max");
		if (get_module_setting("maxgold",$type) != 0){
			rawoutput("<tr height=30px class='trlight'><td rowspan=2>$trdepositgold</td><td>");
			if($golddep > 0 && $cofferdeps > 0){
					rawoutput("<a href='runmodule.php?module=dwellings&op=coffers&subop=dep&g=gold&dwid=$dwid&amount=$golddep'>$trdepositmax $dwgold ($golddep $dwgold)</a></td></tr>");
					addnav("","runmodule.php?module=dwellings&op=coffers&subop=dep&g=gold&dwid=$dwid&amount=$golddep");
					rawoutput("<tr height=30px class='trdark'><td><form action='runmodule.php?module=dwellings&op=coffers&subop=dep&g=gold&dwid=$dwid' method='POST'>");
					rawoutput("<input id='input' name='amount' width=5> <input type='submit' class='button' value='".translate_inline("Deposit only this amount")."'>");
					rawoutput("</form></td></tr>");
					addnav("","runmodule.php?module=dwellings&op=coffers&subop=dep&g=gold&dwid=$dwid");
			}elseif($golddep==0 && $cofferdeps>0 && $session['user']['gold']>0){
					$coffull = translate_inline("Your coffers are full.");
					rawoutput("$coffull</td></tr><tr height=30px class='trdark'><td>");
					output("You can't deposit any more gold.");
					rawoutput("</td></tr>");
			}elseif($session['user']['gold']==0){
					$pocketemp = translate_inline("Your pockets are empty.");
					$nodeposithave = translate_inline("You can't deposit what you don't have.");
					rawoutput("$pocketemp</td></tr><tr height=30px class='trdark'><td>$nodeposithave</td></tr>");
			}else{
					$nodeptoday = translate_inline("You can't deposit anything else today.");
					rawoutput("$nodeptoday</td></tr><tr height=30px class='trdark'><td>&nbsp;</td></tr>");
			}
		}
		if(get_module_setting("maxgems",$type) != 0){
			rawoutput("<tr height=30px class='trdark'><td rowspan=2>$trdepositgems</td><td>");
			if($gemdep>0 && $cofferdeps>0){
					rawoutput("<a href='runmodule.php?module=dwellings&op=coffers&subop=dep&g=gems&dwid=$dwid&amount=$gemdep'>$trdepositmax $dwgems ($gemdep $dwgems)</a></td></tr>");
					addnav("","runmodule.php?module=dwellings&op=coffers&subop=dep&g=gems&dwid=$dwid&amount=$gemdep");
					rawoutput("<tr height=30px class='trlight'><td><form action='runmodule.php?module=dwellings&op=coffers&subop=dep&g=gems&dwid=$dwid' method='POST'>");
					rawoutput("<input id='input' name='amount' width=5> <input type='submit' class='button' value='".translate_inline("Deposit only this amount")."'>");
					rawoutput("</form></td></tr>");
					addnav("","runmodule.php?module=dwellings&op=coffers&subop=dep&g=gems&dwid=$dwid");
			}
			elseif($gemdep==0 && $cofferdeps>0 && $session['user']['gems']>0){
					$coffull = translate_inline("Your coffers are full.");
					rawoutput("$coffull</td></tr><tr height=30px class='trlight'><td>");
					output("You can't deposit any more gems.");
					rawoutput("</td></tr>");
			}
			elseif($session['user']['gems']==0){
					$pocketemp = translate_inline("Your pockets are empty.");
					$nodeposithave = translate_inline("You can't deposit what you don't have.");
					rawoutput("$pocketemp</td></tr><tr height=30px class='trdark'><td>$nodeposithave</td></tr>");
			}
			else{
					$nodeptoday = translate_inline("You can't deposit anything else today.");
					rawoutput("$nodeptoday</td></tr><tr height=30px class='trdark'><td>&nbsp;</td></tr>");
			}
		}
		rawoutput("<tr height=30px class='trlight'><td colspan=2>");
		output("You are allowed to make %s more deposits today.",$cofferdeps);
		rawoutput("</td></tr>");
		$doot_withdraw = translate_inline("Coffers - Withdrawl");
		rawoutput("<tr><td colspan=2 class='trhead' style='text-align:center;'>$doot_withdraw</td></tr>");
		if (get_module_setting("maxgold",$type) != 0){
			rawoutput("<tr height=30px class='trlight'><td rowspan=2>$trwithdrawgold</td><td>");
			if($goldwith>0 && $cofferwiths>0){
					rawoutput("<a href='runmodule.php?module=dwellings&op=coffers&subop=with&g=gold&dwid=$dwid&amount=$goldwith'>$trwithmax $dwgold ($goldwith $dwgold)</a></td></tr>");
					addnav("","runmodule.php?module=dwellings&op=coffers&subop=with&g=gold&dwid=$dwid&amount=$goldwith");
					rawoutput("<tr height=30px class='trdark'><td><form action='runmodule.php?module=dwellings&op=coffers&subop=with&g=gold&dwid=$dwid' method='POST'>");
					rawoutput("<input id='input' name='amount' width=5> <input type='submit' class='button' value='".translate_inline("Withdraw only this amount")."'>");
					rawoutput("</form></td></tr>");
					addnav("","runmodule.php?module=dwellings&op=coffers&subop=with&g=gold&dwid=$dwid");
			}elseif($goldwith==0 && $cofferwiths>0){
					$cofempty = translate_inline("Your coffers are empty.");
					rawoutput("$cofempty</td></tr><tr class='trdark'><td>");
					output("You can't withdraw any more gold.");
					rawoutput("</td></tr>");
			}else{
					$nowithtoday = translate_inline("You can't withdraw anything else today.");
					rawoutput("$nowithtoday</td></tr><tr height=30px class='trdark'><td>&nbsp;</td></tr>");
			}
		}
		if(get_module_setting("maxgems",$type) != 0){
			rawoutput("<tr height=30px class='trdark'><td rowspan=2>$trwithdrawgems</td><td>");
			if($gemwith>0 && $cofferwiths>0){
					rawoutput("<a href='runmodule.php?module=dwellings&op=coffers&subop=with&g=gems&dwid=$dwid&amount=$gemwith'>$trwithmax $dwgems ($gemwith $dwgems)</a></td></tr>");
					addnav("","runmodule.php?module=dwellings&op=coffers&subop=with&g=gems&dwid=$dwid&amount=$gemwith");
					rawoutput("<tr height=30px class='trlight'><td><form action='runmodule.php?module=dwellings&op=coffers&subop=with&g=gems&dwid=$dwid' method='POST'>");
					rawoutput("<input id='input' name='amount' width=5> <input type='submit' class='button' value='".translate_inline("Withdraw only this amount")."'>");
					rawoutput("</form></td></tr>");
					addnav("","runmodule.php?module=dwellings&op=coffers&subop=with&g=gems&dwid=$dwid");
			}elseif($gemwith == 0 && $cofferwiths > 0){
					$cofempty = translate_inline("Your coffers are empty.");
					$nowith = translate_inline("You can't withdraw any more");
					rawoutput("$cofempty</td></tr><tr height=30px class='trlight'><td>");
					output("You can't withdraw any more");
					rawoutput("</td></tr>");
			}else{
					$nowithtoday = translate_inline("You can't withdraw anything else today.");
					rawoutput("$nowithtoday</td></tr><tr height=30px class='trdark'><td>&nbsp;</td></tr>");
			}
		}	
		rawoutput("<tr height=30px class='trlight'><td colspan=2>");
		output("You are allowed to make %s more withdrawls today.",$cofferwiths);
		rawoutput("</td></tr>");
		
		rawoutput("</table><br><hr><br>"); 

		require_once ("lib/commentary.php");
		addcommentary();
		viewcommentary("coffers-".$dwid,"A log of deposits is kept here", 10, "says"); 
	
		addnav("The Coffers","runmodule.php?module=dwellings&op=coffers&dwid=$dwid");
		modulehook("dwellings-coffers", array("type"=>$type, "dwid"=>$dwid));
		addnav("Leave");
		addnav("Back to Dwelling","runmodule.php?module=dwellings&op=enter&dwid=$dwid");
	
?>