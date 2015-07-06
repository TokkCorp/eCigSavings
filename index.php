<?php
require_once 'settings.php';

header('Content-type: text/html; charset=UTF-8');
header("Cache-Control: no-cache, must-revalidate"); 
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
setlocale(LC_MONETARY, 'de_DE');

try {
	$db = new PDO('mysql:host='.$config['mysql_host'].';dbname='.$config['mysql_db'].';charset=utf8', $config['mysql_user'], $config['mysql_pwd']);
} catch (PDOException $e) {
	print "Error!: " . $e->getMessage() . "<br/>";
	die();
}

	$stmt = $db->prepare("SELECT count(*) AS cnt FROM information_schema.TABLES WHERE (TABLE_SCHEMA = '".$config["mysql_db"]."') AND (TABLE_NAME = '".$config["mysql_pre"]."spendings')");
	$stmt->execute();
	$res = $stmt->fetch(PDO::FETCH_OBJ);
	if($res->cnt < 1)
	{
		$db->query("CREATE TABLE `".$config["mysql_pre"]."spendings` (`id` int(10) unsigned NOT NULL AUTO_INCREMENT, `date` int(10) unsigned NOT NULL, `price` decimal(5,2) NOT NULL,  `name` varchar(255) NOT NULL,   `url` text,  PRIMARY KEY (`id`),  UNIQUE KEY `id_UNIQUE` (`id`) ) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
");
	}

if(isset($_POST["SpendingName"]) && isset($_POST["SpendingPrice"]) && isset($_POST["SpendingDate"]) && ((isset($_POST["SpendingPassword"]) && $_POST["SpendingPassword"] == $config["addSpendingPassword"]) || (isset($_COOKIE["isAddAuthoirized"]) && password_verify($config["addSpendingPassword"], $_COOKIE["isAddAuthoirized"]))))
{
	$name = $_POST["SpendingName"];    
	$price = $_POST["SpendingPrice"];
	$date = DateTime::createFromFormat("Y-m-d", $_POST["SpendingDate"]);
	$url = isset($_POST["SpendingUrl"]) ? $_POST["SpendingUrl"] : "";
	
	$query = $db->prepare("INSERT INTO ".$config["mysql_pre"]."spendings (date, price, name, url) VALUES (:date,:price,:name,:url)");
	$query->bindValue(":date", $date->getTimestamp(), PDO::PARAM_INT);
	$query->bindParam(":price", $price, PDO::PARAM_STR);
	$query->bindParam(":name", $name, PDO::PARAM_STR);
	$query->bindParam(":url", $url, PDO::PARAM_STR);
	$query->execute();
	
	if(isset($_POST["SpendingRemember"]) && ($_POST["SpendingRemember"] == "on" || $_POST["SpendingRemember"] == 1))
	{
		if(isset($_COOKIE["isAddAuthoirized"]))
		{
			setcookie("isAddAuthoirized", $_COOKIE["isAddAuthoirized"], time() + 30*24*60*60);
		}
		else
		{
			$cookieHash = password_hash($config["addSpendingPassword"], PASSWORD_BCRYPT);
			setcookie("isAddAuthoirized", $cookieHash, time() + 30*24*60*60); 
		} 
	}
	else
	{
		setcookie("isAddAuthoirized", "no", 1);
	}
	header("Location: index.php");
}

$cigSettings["cigsPerCartoon"] = $cigSettings["cigsInBox"] * $cigSettings["BoxesPerCarton"];
$cigSettings["startedDateTime"] = DateTime::createFromFormat("d.m.Y H:i", $cigSettings["startedHuman"]);
$cigSettings["currentDateTime"] = new DateTime();
$cigSettings["eCigDuration"] = $cigSettings["currentDateTime"]->diff($cigSettings["startedDateTime"]);
$cigSettings["eCigDurationSecs"] = $cigSettings["currentDateTime"]->getTimestamp() - $cigSettings["startedDateTime"]->getTimestamp();
$cigSettings["CigsPerSecond"] = $cigSettings["cigsPerDay"] / 86400;

$cigSettings["avoided"] = array(
	"cigsTotal" => round($cigSettings["CigsPerSecond"] * $cigSettings["eCigDurationSecs"], 0),
	"BoxesTotal" => ceil(round($cigSettings["CigsPerSecond"] * $cigSettings["eCigDurationSecs"], 0) / $cigSettings["cigsInBox"])
);

$cigSettings["avoided"]["cartons"] = floor($cigSettings["avoided"]["cigsTotal"] / $cigSettings["cigsPerCartoon"]);
$cigSettings["avoided"]["boxes"] = floor(($cigSettings["avoided"]["cigsTotal"] - ($cigSettings["avoided"]["cartons"] * $cigSettings["cigsPerCartoon"]))/$cigSettings["cigsInBox"]);
$cigSettings["avoided"]["cigs"] = $cigSettings["avoided"]["cigsTotal"] - ($cigSettings["avoided"]["cartons"] * $cigSettings["cigsPerCartoon"] + $cigSettings["avoided"]["boxes"] * $cigSettings["cigsInBox"]);
$cigSettings["savings"] = ($cigSettings["avoided"]["cigsTotal"] * $cigSettings["pricePerBox"])/$cigSettings["cigsInBox"];


$stmt = $db->prepare("SELECT SUM(price) AS total FROM ".$config["mysql_pre"]."spendings");
$stmt->execute();
$totalSpendings = $stmt->fetch(PDO::FETCH_OBJ);

$stmt = $db->prepare("SELECT date, price, name, url FROM ".$config["mysql_pre"]."spendings ORDER BY date DESC LIMIT 5");
$stmt->execute();
$lastFiveSpendings = $stmt->fetchAll(PDO::FETCH_OBJ);

?><!DOCTYPE html>
<html lang="en">
  <head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
	<meta name="description" content="Savings from smoking electronic Cigs">
	<meta name="author" content="Tokk">

	<title>eCig Savings</title>

	<!-- Bootstrap core CSS -->
	<link rel="stylesheet" type="text/css" href="/css/bootstrap.min.css">
	<!-- Custom styles for this template -->
	<link rel="stylesheet" type="text/css" href="/css/jumbotron-narrow.css">

	<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
	<!--[if lt IE 9]>
	  <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
	  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
	<![endif]-->
  </head>

  <body>

	<div class="container">
	  <div class="header clearfix">
		<nav>
		<ul class="nav nav-pills pull-right">
		<li role="presentation"><button type="button" class="btn btn-success btn-sm"  data-toggle="modal" data-target="#addSpendingModal"><span class="glyphicon glyphicon-plus"></span> Add Spending</button></li>
		</ul>
		</nav>
		<h3 class="text-muted">eCig Savings</h3>
	  </div>
	<?php if($config["addSpendingPassword"] != "") { ?> 
	  
	  <!-- Modal -->
	<div class="modal fade" id="addSpendingModal" tabindex="-1" role="dialog" aria-labelledby="addSpendingModalLabel">
	  <div class="modal-dialog" role="document">
		<div class="modal-content">
		  <div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" id="myModalLabel">Add spending</h4>
		  </div>
		  <form action="./index.php" method="POST" class="form-horizontal">
		  <div class="modal-body">
			<div class="form-group">
				<label for="inputSpendingName" class="col-sm-2 control-label">Name</label>
				<div class="col-sm-10">
				  <input type="text" class="form-control" id="inputSpendingName" name="SpendingName" placeholder="Liquid 50ml" required="required">
				</div>
			</div>
			<div class="form-group">
				<label for="inputSpendingPrice" class="col-sm-2 control-label">Price</label>
				<div class="col-sm-10">
				  <input type="number" class="form-control" id="inputSpendingPrice" name="SpendingPrice" placeholder="10,00" step="any" required="required">
				</div>
			</div>
			<div class="form-group">
				<label for="inputSpendingDate" class="col-sm-2 control-label">Date</label>
				<div class="col-sm-10">
				  <input type="date" class="form-control" id="inputSpendingDate" name="SpendingDate" value="<?php echo date("Y-m-d"); ?>" required="required">
				</div>
			</div>
			<div class="form-group">
				<label for="inputSpendingUrl" class="col-sm-2 control-label">Url*</label>
				<div class="col-sm-10">
				  <input type="url" class="form-control" id="inputSpendingUrl" name="SpendingUrl" placeholder="http://www.shop.com" >
				</div>
			</div>
			<?php if(!isset($_COOKIE["isAddAuthoirized"])) { ?>
			<div class="form-group">
				<label for="inputSpendingPassword" class="col-sm-2 control-label">Password</label>
				<div class="col-sm-10">
				  <input type="password" class="form-control" id="inputSpendingPassword" name="SpendingPassword" placeholder="******" >
				</div>
			</div>
			<?php } ?>
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-10">
				  <div class="checkbox">
					<label>
					  <input type="checkbox" <?php if(isset($_COOKIE["isAddAuthoirized"])) { echo "checked=\"checked\""; } ?> name="SpendingRemember"> Remember this device
					</label>
				  </div>
				</div>
			</div>
			<p>Fields marked with * are optional</p>
		  </div>
		  <div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
			<input type="submit" class="btn btn-primary" value="Save changes"></input>
		  </div>
		  </form>
		</div>
	  </div>
	</div>
	<!-- end modal -->
	<?php } else { ?>
	<div class="alert alert-danger" role="alert"><strong>Password not set</strong> Adding a spending is not possible until you set a password in the configuration</div>
	<?php } ?>

	  <div class="jumbotron">
		<p><span class="label label-success"><?php echo number_format($cigSettings["savings"],2)?> <?php echo $cigSettings["currency"]; ?></span> - <span class="label label-danger"><?php echo number_format($totalSpendings->total ,2); ?> <?php echo $cigSettings["currency"]; ?></span> = </p>
		<h1><span class="label label-<?php echo ($cigSettings["savings"] - $totalSpendings->total) < 0 ? "danger" : "success"; ?>"><?php echo  number_format ($cigSettings["savings"] - $totalSpendings->total,2);  ?> <?php echo $cigSettings["currency"]; ?></span></h1>
		<?php
			if(($cigSettings["savings"] - $totalSpendings->total) < 0)
			{
				$savingsPerDay = ($cigSettings["pricePerBox"] / $cigSettings["cigsInBox"])*$cigSettings["cigsPerDay"];
				$savingsPerMinute = (($savingsPerDay / 24) / 60);
				
				$currentValue = $cigSettings["savings"] - $totalSpendings->total;
				
				$startSavingInMinutes = abs($currentValue / $savingsPerMinute);
				
				$dayPart = floor($startSavingInMinutes / (60*24));
				$hourPartInMinutes = $startSavingInMinutes - ($dayPart*60*24);
				$hours = ceil($hourPartInMinutes/60);
				$savingsStartString = "";
				
				if($dayPart > 0)
				{
					$savingsStartString = $dayPart." days";
				}
				if($hours > 0)
				{
					if($savingsStartString != "")
					{
						$savingsStartString .= " and ";
					}
					$savingsStartString .= $hours." hours"; 
				}
				
				echo "<p><br>You are saving <span class=\"label label-info\">".number_format($savingsPerDay, 2)." ".$cigSettings["currency"]."</span> per day therefore you will start to save money in approximately <span class=\"label label-info\">".$savingsStartString."</span> without further spendings</p>";
			}
		?>
	  </div>

	  <div class="row marketing">
		<div class="col-lg-6">
		  <h4>Spendings</h4>
		  <p>Last 5 Spendings:</p>
		  <table class="table table-condensed">
		  <tr><th>Item</th><th>Date</th><th>Price</th></tr>
		  <?php
		  foreach($lastFiveSpendings as $spending)
		  {
			  $name = $spending->name;
			  if(trim($spending->url) != "")
			  {
				  $name = "<a href=\"".$spending->url."\">".$spending->name."</a>";
			  }
			  
			  echo "<tr><td>".$name."</td><td>".date("d.m.Y", $spending->date)."</td><td>".number_format($spending->price,2)." ".$cigSettings["currency"]."</td></tr>";
		  }     
		  ?>
		  <tr><td>Total Spendings</td><td></td><td><span class="label label-danger"><?php echo number_format($totalSpendings->total,2)?> <?php echo $cigSettings["currency"]; ?></span></td></tr>
		  </table>
		</div>

		<div class="col-lg-6">
		  <h4>Savings</h4>
		  <p>Smoking eCigs since <?php echo $cigSettings["eCigDuration"]->format("%y years, %m months, %d days, %h hours"); ?></p>
		  <p>In this timespan <span class="label label-info"><?php echo $cigSettings["avoided"]["cartons"]; ?></span> cartoons, <span class="label label-info"><?php echo $cigSettings["avoided"]["boxes"]; ?></span> boxes and <span class="label label-info"><?php echo $cigSettings["avoided"]["cigs"]; ?></span> cigs would have been smoked</p>
		  <p>This leads to a total of <span class="label label-primary"><?php echo $cigSettings["avoided"]["cigsTotal"]; ?></span> avoided cigs</p>
		  <p>With a price of <span class="label label-info"><?php echo number_format($cigSettings["pricePerBox"],2); ?> <?php echo $cigSettings["currency"]; ?></span> per Box this means total savings of:</p>
		  <p> <span class="label label-success"><?php echo number_format($cigSettings["savings"],2)?> <?php echo $cigSettings["currency"]; ?></span></p>
		</div>
	  </div>

	  <footer class="footer">
		<p>&copy; Tokk Corp 2015</p>
	  </footer>
		
	</div> <!-- /container -->
	
	<script type="text/javascript" src="/js/jquery.js"></script>
	<script type="text/javascript" src="/js/bootstrap.min.js"></script>
	
  </body>
</html>
