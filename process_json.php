<?php
/***********************
	Functions to work the page

***********************/

//get dates given month and year
function week_date($week, $year)
{
    $date = new DateTime();
	$dias = array();
	for ($t = 1; $t < 6 ; $t++)
	{
		//$tmp = $date->setISODate($year, $week, $t);
		$tmp = date("Y-m-d", strtotime("{$year}-W{$week}-$t")); //Returns the date of monday in week
		$dias[] = $tmp;
	}   
	return $dias;
}

//converting day name given day of week
function convertDay($dia)
{
	$label = array();
	$label[0] = 'Lunes';
	$label[1] = 'Martes';
	$label[2] = 'Miércoles';
	$label[3] = 'Jueves';
	$label[4] = 'Viernes';
	$label[5] = 'Sábado';
	$label[6] = 'Domingo';
	return $label[$dia];	
}

function setTitle($opt)
{
	$label = array();
	$label[0] = 'Para Comenzar';
	$label[1] = 'Guarniciones';
	$label[2] = 'Plato Fuerte';
	$label[3] = 'Postre';
	$label[4] = 'Adicionales';

	return $label[$opt];	
}

function convertFileType($num)
{
	$tipo = '';
	switch($num)
	{
		case 1:
			$tipo = 'M';
		break;
		case 2:
			$tipo = 'H';
		break;
		case 3:
			$tipo = 'C';
		break;
	}
	return $tipo;
}

//$salida = saveToFile($array, $type_file, $ts);
function saveToFile($file, $ts_file)
{
	$out = '';
	//if ($file["files"]["error"][0]== UPLOAD_ERR_OK)
	if ($file["error"][0]== UPLOAD_ERR_OK)
	{
		$ext = substr($file["name"][0], strpos($file["name"][0], ".") + 1);  
		$name = 'appli_cert.'.$ext;
		//$name = 'tempFile.txt';
		if(file_exists('repo/'.$name))
		{
			unlink('repo/'.$name);
		}
		move_uploaded_file($file["tmp_name"][0], "repo/" . $name);
		$out .= 'ok';
		//move_uploaded_file($file["tmp_name"][0], "temp/" . $name);
		
	}
	else
	{
		//echo json_encode(array('arreglo_archivo'=>FALSE,'data'=>'Error uploading '.$name));
		//exit(1);
		//$out .= '<span class="badge badge-danger">Error occur during file upload!</span><br><br>'."\n";
		$out .= 'nok';
	}
	return $out;
}

// to clean up LDAP results
function rCountRemover($arr) 
{
	foreach($arr as $key=>$val) 
	{
		# (int)0 == "count", so we need to use ===
		if($key === "count")
			unset($arr[$key]);
		elseif(is_array($val))
			$arr[$key] = rCountRemover($arr[$key]);
	}
	return $arr;
}

//to search on LDAP given mail of user
function searchOnLDAP($correo, $member_name, $member_pwd)
{
	$resultado = '';
	$ldap = ldap_connect("euedcadsls01.ls.ege.ds");
	ldap_set_option ($ldap, LDAP_OPT_REFERRALS, 0);
	ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
	if (@$bind = ldap_bind($ldap, "ls\\".$member_name, $member_pwd)) 	
	{
		//Credentials work now let's do LDAP query for data.....LDAP parameters
		//$ldap_dn = 'OU=Users,OU=PBL,OU=MX,DC=ls,DC=ege,DC=ds';
		$ldap_dn = 'DC=ls,DC=ege,DC=ds';
		//$attributes = array("displayname", "mail", "samaccountname");
		$attributes = array("displayname"); 
		//$filter = "(&(objectCategory=person)(sAMAccountName=$member_name))";
		$filter = "(&(objectCategory=person)(mail=$correo))";
		$result = ldap_search($ldap, $ldap_dn, $filter, $attributes);
		$entries = ldap_get_entries($ldap, $result);
		$entries_clean = rCountRemover($entries);
		if(isset($entries_clean[0]['displayname']))
		{
			$resultado= str_replace(' (external)', '', $entries_clean[0]['displayname'][0]);
		}		 
		//$resultado[1] = $entries_clean[0]['mail'][0]; 
		//$resultado[2] = $entries_clean[0]['samaccountname'][0]; 
		return $resultado;
	}
}

function countBreakLines($file)
{
	$current = @file_get_contents($file);	
	$conteo = substr_count($current, "\r\n");
	return $conteo;
}

/***********************
	Functions to manage Ajax calls

***********************/

//To create weekly control buttons given day 
if(isset($_POST["current_fecha"]))
{
	$today = $_POST["current_fecha"];
	$date = new DateTime($today);
	$week = $date->format("W");
	$ano_c = $date->format("Y");
	$diaW = $date->format('N');
	$out = '';
	$days = week_date($week,$ano_c);
	for ($d = 0; $d < count($days); $d++)
	{
		if(($d+1) == $diaW )
		{
			$out .= '<button type="button" class="btn btn-outline-secondary" data-id1="'.$days[$d].'"><b>'.convertDay($d).'</b></button>'."\n";
		}
		else
		{
			$out .= '<button type="button" class="btn btn-secondary" data-id1="'.$days[$d].'">'.convertDay($d).'</button>'."\n";
		}
		
	}
	$out .= '<button type="button" class="btn btn-outline-primary" data-id1="'.$week.'_'.$ano_c.'"><b>Horarios</b></button>'."\n";
	echo $out;
}

//To print page title with week number
if(isset($_POST["titulo"]))
{
	$today = $_POST["titulo"];
	$date = new DateTime($today);
	$week = $date->format("W");
	$out = '<h1>Menu Comedor Sonata</h1><h2>Semana: '.$week.'</h2>';
	echo $out;
}

//To load file with menu and display it giving week number and year	
if(isset($_POST["given_fecha"]))
{
	//$today = '2020-01-12';
	$today = $_POST["given_fecha"];
	$date = new DateTime($today);
	$week = $date->format("W");
	$ano_c = $date->format("Y");
	$dia = $date->format('l');
	$diaW = $date->format('N');
	
	$out = "";
	$print = array();
	$filaname = "data/M".$week."_".$ano_c.".json";
	$strJsonFileContents = @file_get_contents($filaname );
	if(!$strJsonFileContents) //file don't exist
	{
		$out .= '<h2>File not loaded yet for week '.$week.'!</h2>'."\n";
	}
	else
	{
		if($diaW >= 6)
		{
			$out .= '<div class="alert alert-primary my-5" role="alert"><b>El comedor solo tiene servicio de lunes a viernes!<br></b></div>';
		}
		else
		{
			// Convert to array 
			$array = json_decode($strJsonFileContents, true);
			if($array == NULL)
			{
				$out .= '<h2>File is corrupted for week '.$week.'!</h2>'."\n";;
			}
			else
			{
				$llaves = array_keys($array[$dia]);	
				$out = '<h3 class="my-4">Menu del: <b>'.$today .'</b><br></h3>'."\n".'<div class="row mx-3 mb-5">'."\n";
				$largo = count($llaves);
				//echo 'Numero de arreglos: '.$largo.'<br>';
				for($i = 0; $i < $largo; $i++)
				{
					//$out .= '<b>'.setTitle($i).'</b><br>'."\n";
					$llaves_par = array_keys($array[$dia][$llaves[$i]]);
					$largo_par = count($llaves_par);
					$out .= '
					<div class="card border-secondary text-white mb-3 ml-3 mr-3" style="max-width: 18rem;">
						<div class="card-header bg-primary"><b>'.setTitle($i).'</b></div>
						<div class="card-body bg-light text-dark">
							<p class="card-text">';
							for ($r = 0; $r < $largo_par; $r++)
							{
								$out .= '&bull;'.$array[$dia][$llaves[$i]][($r+1)].'.<br>'."\n";
							}
							
					$out .= '</p>
						</div>
					</div>';
				}
				$out .= '</div>'."\n";				
			}
		}
	}
	$print[0] = $out;
	$print[1] = $diaW;
	echo json_encode($print);
}	


//To load file and display table with hours giving week number and year	
if(isset($_POST["given_fecha_horario"]))
{
	//$today = '21_2020';
	$ts = explode("_", $_POST["given_fecha_horario"]);
	$week = $ts[0];
	$ano_c = $ts[1];
		
	$out = "";
	$filaname = "data/H".$week."_".$ano_c.".json";
	$strJsonFileContents = @file_get_contents($filaname );
	if(!$strJsonFileContents) //file don't exist
	{
		$out .= '<h2>File not loaded yet for week '.$week.'!</h2>'."\n";
	}
	else
	{
		// Convert to array 
		$array = json_decode($strJsonFileContents, true);
		if($array == NULL)
		{
			$out .= '<h2>File is corrupted for week '.$week.'!</h2>'."\n";;
		}
		else
		{
			$llaves = array_keys($array["Horarios"]);	
			$largo = count($llaves);
			$texto = '<p><br><b>En caso de que no puedas acudir en tu horario, deberás hacerlo en el horario comodín para evitar aglomeraciones de personas.</b></p>';
			//$out .= 'Hay '.$largo.' grupos<br>';
			$out .= '<table class="table table-sm table-striped mt-4 mb-3">
			<thead class="thead-dark"><tr><th scope="col">Grupos</th><th scope="col">Horarios</th><th scope="col">Áreas</th></tr></thead>
			<tbody>'."\n";
			for($i = 0; $i < $largo; $i++)
			{
				$out .= '		     <tr><td>'.($i+1).'</td><td>'.$array["Horarios"][$llaves[$i]]["horario"].'</td><td>'.$array["Horarios"][$llaves[$i]]["area"].'</td></tr>'."\n";
			}
			$out .= '		</tbody>'."\n".'</table>'."\n".$texto.'<br>'."\n";
		}		
	}
	echo $out;
}

//To fill multiselect picker with available weeks given current date
if(isset($_POST["fill_picker"]))
{
	$max_weeks = 4; //weeks ahead of current week
	$today = $_POST["fill_picker"];
	//$today = '2020-12-02';
	$date = new DateTime($today);
	$week = $date->format("W");
	$ano_c = $date->format("Y");
	if(($week + $max_weeks) > 53)
	{
		$max_weeks = (53 - $week)+1;
	}
	$fill_opt = '<option value="blank"> </option>'."\n";
	for($w = 0; $w < $max_weeks ; $w++)
	{
		$fill_opt .= '<option value="'.str_pad(($week+$w), 2, "0", STR_PAD_LEFT).'_'.$ano_c .'">'.($week+$w).'</option>'."\n";
	}
	echo $fill_opt;		
}

//to upload file (either can be schedule or menu or contacts)
if(isset($_FILES['files']) &&  isset($_POST['tipo_archivo']) &&  isset($_POST['fecha_archivo']))
{
	$array = $_FILES['files'];
	$ts = explode("_", $_POST["fecha_archivo"]);
	$type_file = $_POST['tipo_archivo'];
	$name_file = convertFileType($type_file);
	//$largo = count($_FILES["files"]["name"]);
	//echo 'File uploaded is '.$_FILES["files"]["name"][0].' Tipo archivo es: '.convertFileType($_POST['tipo_archivo']).' data: '.json_encode($_FILES['files']);
	$salida = saveToFile($array, $type_file, $name_file, $ts);
	
	echo $salida;	
	
}

//filling modal with next friday given some date
if(isset($_POST['check_friday']) )
{
	$fecha = $_POST['check_friday'];
	//$fecha = '2020-11-04 18:59:00';
	$date = new DateTime($fecha);
	$date->modify('next friday');
	$date = $date->format('Y-m-d');
	
	$date2 = new DateTime($fecha);
	$date2 ->modify('wednesday this week');
	$wedOfWeek = $date2->format('Y-m-d');
	$wedOfWeek .= '18:00:00';
	
	$today_time = new DateTime($fecha);
	$limit_request = new DateTime($wedOfWeek);
	$salida = array();
	if ($today_time >= $limit_request) 
	{
		//Date is greater than, you can't request service
		$salida[0] = '<label>El registro para consumo el próximo viernes <b>'.$date.'</b> ya está cerrado!</label>';
		$salida[1] = 0;
	}
	else
	{
		//Date is less than, record your service
		$salida[0] = '<label>Fecha: <b>'.$date.'</b></label><br><label>Tu correo:</label><input class="form-control" type="text" placeholder="someone@faurecia.com" id="mail_friday">';
		$salida[1] = 1;
	}	
	echo json_encode($salida);	
}

//search on AD the given mail and if found it record the lunch request on file_exists
if(isset($_POST['requesting_mail']) && isset($_POST['stamp']))
{
	include 'params/global.php';
	$today = $_POST["stamp"];
	$date = new DateTime($today);
	$week = $date->format("W");
	$ano_c = $date->format("Y");
	$correo_validar = $_POST['requesting_mail'];
	$datos = searchOnLDAP($correo_validar, $service_ad, $service_pwd);
	if($datos == '')
	{
		$salida = '<h2>El usuario <b>'.$correo_validar.'</b> no se encontro!</h2>';
	}
	else
	{
		$insert = $datos.','. $today.','."\r\n";
		$file = 'data/C'.$week.'_'.$ano_c.'.csv';
		$current = @file_get_contents($file);
		$current .= $insert;
		file_put_contents($file, $current);
		$salida = '<h2>Consumo registrado para <b>'.$datos.'</b></h2>';
	}	
	echo $salida;
}


//to print the download button of current week and send mail button of current week
if( isset($_POST['week_file']))
{
	$today = $_POST["week_file"];
	$date = new DateTime($today);
	$week = $date->format("W");
	$ano_c = $date->format("Y");
	$file = 'data/C'.$week.'_'.$ano_c.'.csv';
	$registros = countBreakLines($file);	
	$out = '<center>
		<h2>Revisa los consumos en Viernes aquí...</h2>
		<h4 class="text-dark my-3">Consumos para está semana: <b>'.$registros.'</b></h4>
		<a class="btn btn-dark mb-5 mt-2" href="download.php?file=C'.$week.'_'.$ano_c.'.csv" role="button" download>Descarga la semana actual</a>'."\n";
	$filaname = "data/H".$week."_".$ano_c.".json";
	$strJsonFileContents = @file_get_contents($filaname );
	if(!$strJsonFileContents) //file don't exist
	{
		$out .= '<div class="my-3 mx-3">
					<h2>Envía los horarios de comedor por correo aquí...</h2>
					<button type="button" class="btn btn-dark mb-5 mt-2" id="mailBTN" disabled>Enviar correo</button>
				</div>'."\n".'</center>'."\n";
	}
	else
	{
		// Convert to array 
		$array = json_decode($strJsonFileContents, true);
		if($array == NULL)
		{
			$out .= '<div class="my-3 mx-3">
						<h2>Envía los horarios de comedor por correo aquí...</h2>
						<button type="button" class="btn btn-dark mb-5 mt-2" id="mailBTN" disabled>Enviar correo</button>
					</div>'."\n".'</center>'."\n";
		}
		else
		{
			$out .= '<div class="my-3 mx-3">
						<h2>Envía los horarios de comedor por correo aquí...</h2>
						<button type="button" class="btn btn-dark mb-5 mt-2" id="mailBTN">Enviar correo</button>
					</div>'."\n".'</center>'."\n";
		}
	}
	echo $out;
}


//to send table with hours by mail 
if(isset($_POST["send_mail"]))
{
	$today = $_POST["send_mail"];
	$date = new DateTime($today);
	$week = $date->format("W");
	$ano_c = $date->format("Y");
	$filaname = "data/H".$week."_".$ano_c.".json";
	$filecontacs = "data/contacts.json";
	$strJsonFileContents = @file_get_contents($filaname );
	$strJsonFileContacts = @file_get_contents($filecontacs );
	$out = '';
	if(!$strJsonFileContacts) //file don't exist for contacts
	{
		$out .= 'missing_c';
		echo $out;
	}
	else
	{
		$array2 = json_decode($strJsonFileContacts, true);
		if($array2 == NULL)
		{
			$out .= 'missing_f';
			echo $out;
		}
		else
		{
			if(!$strJsonFileContents) //file don't exist for hours
			{
				$out .= 'missing_h';
				echo $out;
			}
			else
			{
				$to = $array2["Contactos"]["copy"]["dl"];
				$bcc =  $array2["Contactos"]["blank_copy"]["dl"];
				//$to = 'eduardo.medel@faurecia.com, isi.duarte@faurecia.com';
				$array = json_decode($strJsonFileContents, true); // Convert to array 
				if($array == NULL)
				{
					//$out .= '<h2>File is corrupted for week '.$week.'!</h2>'."\n";
					$out .= 'corrupted';
					echo $out;
				}
				else
				{
					include 'template.php';
					$llaves = array_keys($array["Horarios"]);	
					$largo = count($llaves);
					$semana_label = '<p style="line-height: 24px; font-size: 16px; width: 100%; margin: 0;" align="left"><b>Hola a todos,<br>El horario del comedor para la semana '.$week.' será:</b></p>';
					$out .= $first_part.$semana_label.$second_part;
					for($i = 0; $i < $largo; $i++)
					{
						if ($i % 2 == 0) 
						{
							//número par
							$out .= '<tr bgcolor="#f2f2f2">
										<td style="border-spacing: 0px; border-collapse: collapse; line-height: 24px; font-size: 16px; border-top-width: 1px; border-top-color: #dee2e6; border-top-style: solid; font-family: &quot;Century Gothic&quot;, Arial, Helvetica, sans-serif; margin: 0; padding: 4.8px;" align="center" valign="top">'.($i+1).'</td>
										<td style="border-spacing: 0px; border-collapse: collapse; line-height: 24px; font-size: 16px; border-top-width: 1px; border-top-color: #dee2e6; border-top-style: solid; font-family: &quot;Century Gothic&quot;, Arial, Helvetica, sans-serif; margin: 0; padding: 4.8px;" align="center" valign="top">'.$array["Horarios"][$llaves[$i]]["horario"].'</td>
										<td style="border-spacing: 0px; border-collapse: collapse; line-height: 24px; font-size: 16px; border-top-width: 1px; border-top-color: #dee2e6; border-top-style: solid; font-family: &quot;Century Gothic&quot;, Arial, Helvetica, sans-serif; margin: 0; padding: 4.8px;" align="center" valign="top">'.$array["Horarios"][$llaves[$i]]["area"].'</td>
									</tr>';
						}
						else
						{
							//número impar
							$out .= '<tr>
										<td style="border-spacing: 0px; border-collapse: collapse; line-height: 24px; font-size: 16px; border-top-width: 1px; border-top-color: #dee2e6; border-top-style: solid; font-family: &quot;Century Gothic&quot;, Arial, Helvetica, sans-serif; margin: 0; padding: 4.8px;" align="center" valign="top">'.($i+1).'</td>
										<td style="border-spacing: 0px; border-collapse: collapse; line-height: 24px; font-size: 16px; border-top-width: 1px; border-top-color: #dee2e6; border-top-style: solid; font-family: &quot;Century Gothic&quot;, Arial, Helvetica, sans-serif; margin: 0; padding: 4.8px;" align="center" valign="top">'.$array["Horarios"][$llaves[$i]]["horario"].'</td>
										<td style="border-spacing: 0px; border-collapse: collapse; line-height: 24px; font-size: 16px; border-top-width: 1px; border-top-color: #dee2e6; border-top-style: solid; font-family: &quot;Century Gothic&quot;, Arial, Helvetica, sans-serif; margin: 0; padding: 4.8px;" align="center" valign="top">'.$array["Horarios"][$llaves[$i]]["area"].'</td>
									</tr>';
						}	
					}
					$out .= $last_part;
					
					$subject = 'Menu Comedor Sonata Semana '.$week;
					$headers = 'From: comedor.sonata@faurecia.com' . "\r\n" .'MIME-Version: 1.0'."\r\n".'Content-Type: text/html; charset=UTF-8'."\r\n";
					$headers .= "Bcc: $bcc\r\n";
					if(@!mail($to, $subject, $out, $headers))
					{
						$validation = 'error';
					}
					else
					{
						$validation = 'ok';
					}
					echo $validation;	
				}		
			}			
		}
	}	
	
}


//to upload certificate file
if(isset($_FILES['files']) &&  isset($_POST['exp_start']) )
{
	$array = $_FILES['files'];
	$ts = $_POST['exp_start'];
	//$largo = count($_FILES["files"]["name"]);
	//echo 'File uploaded is '.$_FILES["files"]["name"][0].' Tipo archivo es: '.convertFileType($_POST['tipo_archivo']).' data: '.json_encode($_FILES['files']);
	$salida = saveToFile($array, $ts);
	//$salida = 'fecha: '.$ts.'<br><pre>'.print_r($array).'</pre>';
	//$salida = 'fecha: '.$ts;
	echo $salida;	
	
}

?>