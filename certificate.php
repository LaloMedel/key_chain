<!DOCTYPE html>
<html lang="en">
<head>
<meta name="viewport" content="width=device-width"/>

<!--<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>-->
<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
<script src="https://unpkg.com/jquery"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/css/bootstrap-select.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/js/bootstrap-select.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.min.css" />
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.1/css/all.css" integrity="sha384-vp86vTRFVJgpjF9jiIGPEEqYqlDwgyBgEF109VFjmqGmIY/Y4HV4d3Gp2irVfcrp" crossorigin="anonymous">
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">	
<link rel="icon" href="Images/favicon.png">
<link rel="stylesheet" type="text/css" href="Style/main.css">
<title>Certificate Upload</title>
</head>
<body>
<div id="myNav">
	<nav class="navbar navbar-expand-lg sticky-top navbar-dark bg-dark">
		<div class="container-fluid">
			<a class="navbar-brand" href="#"><img src="Image/white_logo.png" alt="" width="80" class="d-inline-block align-top mr-1">|<b class="ml-2">App Name</b></a>
			<p class="text-white ml-auto my-2 mx-1">Logged as: <b>medel</b></p>
			<button class="btn btn-outline-light my-2 mx-2" id="logoutBTN"><i class="fas fa-angle-left mr-2"></i>Back</button>
		</div>		
	</nav>
</div>

<div id="alert_pop"></div>
<center>
<div class="my-4 mx-3">
	<h2>Upload your certificate here...</h2>
	<label for="exampleFormControlFile1"><b>Select your file:</b></label>
	<input type="file" class="form-control-file" id="input_files" name="files[]">
	<div id="loading_spinner" class="mt-3"><h4><i class="fa fa-spinner fa-pulse"></i>  <b>Uploading</b></h4></div>
	<div id="file_result" class="mt-3"></div>

</div>
</center>

</body>
<script>
$(document).ready(function() 
{
	function hasExtension(fileName, exts) 
	{
		var check = (new RegExp('(' + exts.join('|').replace(/\./g, '\\.') + ')$')).test(fileName.toLowerCase());
		console.log("File: "+ fileName + " match extension: "+ check);
		return check;
	}
	
	function TransferCompleteCallback(content)
	{
		// we might want to use the transferred content directly
		// for example to render an uploaded image
	}
	

	var input = document.getElementById("input_files");
	var formdata = false;
	
	if (window.FormData) 
	{
		formdata = new FormData();
		//$("#btn_submit").hide();
		$("#loading_spinner").hide();
	}
	
	$('#input_files').on('change',function(event)
	{	
		file = this.files[0]; 
		var stamp_today = '2021-01-25';
		extension_OK = hasExtension(file.name,  ['.txt', '.json']);
		if(extension_OK == true)
		{
			$("#loading_spinner").fadeIn();
			file = this.files[0]; 
			if ( window.FileReader ) 
			{
				reader = new FileReader();
				reader.onloadend = function (e) 
				{ 
					TransferCompleteCallback(e.target.result);
				};
				reader.readAsDataURL(file);
			}
			if (formdata) 
			{
				formdata.append("files[]", file);
				formdata.append('exp_start', stamp_today);				
			}
			$.ajax(
			{
				url: "process_json.php",
				type: "POST",
				data: formdata,
				processData: false,
				contentType: false, // this is important!!!
				beforeSend: function() 
				{ 
					//console.log("Sending file!");
					console.log('File name is: '+ file.name + ' fecha es: '+ stamp_today);
				},
				success: function (res) 
				{
					console.log(res);
					//$('html, body').animate({scrollTop: '0px'}, 1000);
					$("#loading_spinner").hide();
					$("#file_result").html(res);
					// reset formdata
					formdata = false;
					formdata = new FormData();
				}
			});
		}	
	});
	
	
	
});
</script>
<!--
<footer class="footer">
<div>
	<span class="text-white"><strong><br>Faurecia México HQ</strong></span><br><span>Powered by <strong><a class="link" href="#">Faurecia GIS MX</a></strong><br></span>
</div>
</footer>-->
</html>