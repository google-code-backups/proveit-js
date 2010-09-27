<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en"> 
<head>
	<link href="proveit_styles.css" rel="stylesheet" type="text/css" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>ProveIt - <?php echo $page; ?></title>
	<script type="text/javascript" src="http://www.google.com/jsapi"></script> 
	<script type="text/javascript"> 
		google.load("jquery", "1");
		google.setOnLoadCallback(function(){});
	</script> 
	<script src="scripts.js" type="text/javascript"></script>
</head>
<body>
<div id="superContainer">
<!-- Navigation Table -->
				<table width="960" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 15px; ">
					<tr>
						<td style="width: 250px; ">
							<div id="logoContainer">
								<a href="index.php" class="img"><img src="img/proveit_logo.png" alt="ProveIt logo" /></a>
							</div><!-- end #logoContainer -->
						</td>
						<td style="width: 460px;">

<?php

// use the page name to figure out which primary-nav category we're in

$primary = '';

switch($page)
{
	case 'Features':
	case 'Screenshots':
	case 'Tutorials':
	case 'Report a Bug':
	case 'Demo':
		$primary = 'Users';
		break;
	
	case 'Documentation':
		$primary = 'Developers';
		break;
	
	case 'Research':
	case 'The Team':
		$primary = 'About';
}

?>
						
						<ul id="primary-nav">				
							<li>
								<a href="#" class="primary-nav-link<?= $primary == 'Users' ? ' selected' : '' ?>">Users</a>
								<ul<?= $primary == 'Users' ? ' style="display: block;"' : '' ?>>
									<li><a href="features.php"<?= $page == 'Features' ? ' class="selected"' : '' ?>>Features</a></li>
									<li><a href="screenshots.php"<?= $page == 'Screenshots' ? ' class="selected"' : '' ?>>Screenshots</a></li>
									<li><a href="tutorials.php"<?= $page == 'Tutorials' ? ' class="selected"' : '' ?>>Tutorials</a></li>
									<li><a href="#"<?= $page == 'Report a Bug' ? ' class="selected"' : '' ?>>Report a Bug</a></li>
									<li><a href="demo.php"<?= $page == 'Demo' ? ' class="selected"' : '' ?>>Demo</a></li>
								</ul>
							</li>
							<li>
								<a href="#" class="primary-nav-link<?= $primary == 'Developers' ? ' selected' : '' ?>">Developers</a>
								<ul<?= $primary == 'Developers' ? ' style="display: block;"' : '' ?>>
									<li><a href="documentation.php"<?= $page == 'Documentation' ? ' class="selected"' : '' ?>>Documentation</a></li>
									<li><a href="http://code.google.com/p/proveit-js/">Google Code Project</a></li>
									<li><a href="http://code.google.com/p/proveit-js/w/list">Wiki</a></li>
								</ul>
							</li>
							<li>
								<a href="#" class="primary-nav-link<?= $primary == 'About' ? ' selected' : '' ?>">About</a>
								<ul<?= $primary == 'About' ? ' style="display: block;"' : '' ?>>
									<li><a href="research.php"<?= $page == 'Research' ? ' class="selected"' : '' ?>>Research</a></li>
									<li><a href="theteam.php"<?= $page == 'The Team' ? ' class="selected"' : '' ?>>The Team</a></li>
								</ul>
							</li>
						</ul><!-- end #primary-nav -->
							
						</td>
						<td style="width: 250px; ">
							<div id="getProveIt">
								<a href="" class="getProveit">&nbsp;</a>
							</div><!-- end #getProveIt -->
						 </td>
					</tr>
				</table>
			<!-- End of Navigation Table -->