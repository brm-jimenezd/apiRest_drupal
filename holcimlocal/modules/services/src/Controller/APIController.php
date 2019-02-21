<?php


// /**
//  * @file
//  * Contains \Drupal\test_api\Controller\TestAPIController.
//  */

namespace Drupal\api\Controller;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
// use PhpOffice\PhpSpreadsheet\IOFactory;
// use PhpOffice\PhpSpreadsheet\Spreadsheet;
// use \Datetime;
// use \Google_Client;
// use \Google_Service_Sheets;
// use GuzzleHttp\Exception\GuzzleException;
// use GuzzleHttp\Client;
use Drupal\node\Entity\Node;
// //require 'vendor/autoload.php';

// //use Drupal\Core\Entity\Query\QueryFactory;
// /**
//  * Controller routines for test_api routes.
//  */


class APIController extends ControllerBase {

	protected $connection;
	protected $path;
	protected $host;

	public function __construct() {
		$this->connection = \Drupal::database();
		$this->path = base_path();
		$this->host = "http://localhost";
	}

	function prettyPrint($arrayToPrint){
        echo "<pre style='background-color:yellow;'>".print_r($arrayToPrint,true)."</pre>";
    }


	public function test(){

		$pass = "pass";

		$encPass = $this->encrypt($pass);
		
		$this->prettyPrint($encPass);
		
		$res = $this->decrypt("pass",$encPass);

		$this->prettyPrint($res);
		die;

		
	
}


	//-------------------------------------*****************************************************************//
					// UTILS FUNCTIONS
	//-------------------------------------*****************************************************************//
	public function paginationLinks(array $input, $endpoint){

		if(isset($_GET["page"]) && isset($_GET["limit"])){
			$page = $_GET["page"];
			$limit = $_GET["limit"];
			
			$next = $page+1;
			$prev = $page-1;
			$result["data"] = $this->getPageItems($input,$page,$limit);

			$dataLength = count($input);
			$position = $limit * $page;

			if($page == $dataLength || $position >= $dataLength ){
				
			}else{
				$result["links"]["next"] = $this->host.$this->path.$endpoint."?page=".$next."&limit=".$limit;
			}
				
			$result["links"]["self"] = $this->host.$this->path.$endpoint."?page=".$page."&limit=".$limit;
			
			if($prev != 0){
			$result["links"]["prev"] = $this->host.$this->path.$endpoint."?page=".$prev."&limit=".$limit;
			}
		}
		 return $result;
	}

	public function getPageItems(array $input, $pageNum, $perPage) {
		$start = ($pageNum-1) * $perPage;
		$end = $start + $perPage;
		$count = count($input);
	
		// Conditionally return results
		if ($start < 0 || $count <= $start) {
			// Page is out of range
			return array(); 
		} else if ($count <= $end) {
			// Partially-filled page
			return array_slice($input, $start);
		} else {
			// Full page 
			return array_slice($input, $start, $end - $start);
		}
	}

	public function contentFilter($contentArray, $filterName, $filterValue){

		$filteredArray = [];
		foreach($contentArray as $contentKey => $contentData){
				if(strtolower($filterValue) == strtolower($contentData[$filterName])){
					$filteredArray[$contentKey] = $contentData;
				}
		}

		return $filteredArray;
	}

	public function encrypt($password){
		$hash = password_hash($password, PASSWORD_BCRYPT, ["cost" => 13]);
		return $hash;
	}

	public function decrypt($password, $dbHash){
		if(password_verify($password, $dbHash)){
		 return "true";
		}else{
			return "false";
		}
	}
	//-------------------------------------*****************************************************************//
	//-------------------------------------*****************************************************************//
				//ENDPOINTS FUNCTIONS
	//-------------------------------------*****************************************************************//
	// function to get articles
	public function getArticles(){

		$query = \Drupal::entityQuery('node')
  				->condition('type', 'articles');

		$entity_ids = $query->execute();
		$data = entity_load_multiple('node', $entity_ids);
			  
		$articlesData = [];
		
		
		foreach($data as $articleKey => $articleData){
		
			$taxonomyTermCategoryId =  $articleData->get('field_category')->target_id;
			$term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($taxonomyTermCategoryId);
			$taxonomyTermCategoryValue = $term->name->value;
			
			
			$articlesData[$articleKey]["title"] = $articleData->title->value;  
			$articlesData[$articleKey]["category"] = $taxonomyTermCategoryValue;
			$articlesData[$articleKey]["date"] = $articleData->field_date->value;
			$articlesData[$articleKey]["description"] = $articleData->field_description->value;
			
			$articleSliderImageLinks =[];
			foreach($articleData->get("field_slider") as $articleSliderImage){
				$imageData =  \Drupal\file\Entity\File::load($articleSliderImage->target_id);
				$imageLink = file_create_url($imageData->get("uri")->value);
				array_push($articleSliderImageLinks,$imageLink);
			}
			$articlesData[$articleKey]["slider"] = $articleSliderImageLinks;

			$articleDataArray = $articleData->toArray();
			
			$youtubeIdsArray = [];
			foreach($articleDataArray["field_youtube"] as $youtubeKey => $youtubeData){
				array_push($youtubeIdsArray,$youtubeData);
			}
			
			$articlesData[$articleKey]["youtube"] = $youtubeIdsArray;			
	}	

	if(isset($_GET["category"]))
		$articlesData = $this->contentFilter($articlesData,"category",$_GET["category"]);

	if(isset($_GET["page"]) && isset($_GET["limit"])){
		$result = $this->paginationLinks($articlesData,"api/v1/getArticles");
	}else{
		$result = $articlesData;
	}

	 return new JsonResponse( $result );

	}

	public function getProducts(){

		$query = \Drupal::entityQuery('node')
  				->condition('type', 'products');

		$entity_ids = $query->execute();
		$data = entity_load_multiple('node', $entity_ids);
		//$this->prettyPrint($data);
		
		$productsData = [];

		foreach($data as $productKey => $productData){
			$productsData[$productKey]["title"] = $productData->title->value;
			$productsData[$productKey]["pdfLink"] = $productData->field_pdf_link->uri;
			$productsData[$productKey]["subtitle"] = $productData->field_subtitle->value;
			$imageData =  \Drupal\file\Entity\File::load($productData->field_image->target_id);
			$imageLink= file_create_url($imageData->get("uri")->value);
			$productsData[$productKey]["image"] = $imageLink;

			$taxonomyTermTypeId =  $productData->field_product_type->target_id;
			$term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($taxonomyTermTypeId);
			$taxonomyTermTypeValue = $term->name->value;

			$productsData[$productKey]["type"] = $taxonomyTermTypeValue;
				
		}
		
		if(isset($_GET["type"]))
		$productsData = $this->contentFilter($productsData,"type",$_GET["type"]);

		if(isset($_GET["page"]) && isset($_GET["limit"])){
			$result = $this->paginationLinks($productsData,"api/v1/getProducts");
		}else{
			$result = $productsData;
		}
	
		return new JsonResponse( $result );
	

	}


	public function getPqr(){

		$query = \Drupal::entityQuery('node')
  				->condition('type', 'pqr');

		$entity_ids = $query->execute();
		$data = entity_load_multiple('node', $entity_ids);
		
		
		$pqrsData = [];

		foreach($data as $pqrKey => $pqrData){
			$pqrsData[$pqrKey]["question"] = $pqrData->field_question->value;
			$pqrsData[$pqrKey]["answer"] = $pqrData->field_answer->value;
		}
		
		if(isset($_GET["page"]) && isset($_GET["limit"])){
			$result = $this->paginationLinks($pqrsData, "api/v1/getPqr");
		}else{
			$result = $pqrsData;
		}
	
		 return new JsonResponse( $result );

	}
		
		
	
	public function getLocations(){

		$query = \Drupal::entityQuery('node')
  				->condition('type', 'locations');

		$entity_ids = $query->execute();
		$data = entity_load_multiple('node', $entity_ids);
		// $this->prettyPrint($data);
		$locationsData = [];

		foreach($data as $locationKey => $locationData){
			$locationsData[$locationKey]["name"] = $locationData->field_name->value;
			$locationsData[$locationKey]["latitude"] = $locationData->field_latitude->value;
			$locationsData[$locationKey]["longitude"] = $locationData->field_longitude->value;
			$locationsData[$locationKey]["address"] = $locationData->field_address->value;
			$locationsData[$locationKey]["phone"] = $locationData->field_phone->value;


			$taxonomyTermTypeId =  $locationData->field_type->target_id;
			$term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($taxonomyTermTypeId);
			$taxonomyTermTypeValue = $term->name->value;

			$locationsData[$locationKey]["type"] = $taxonomyTermTypeValue;
		}
		
		if(isset($_GET["type"]))
			$locationsData = $this->contentFilter($locationsData,"type",$_GET["type"]);

		if(isset($_GET["page"]) && isset($_GET["limit"])){
			$result = $this->paginationLinks($locationsData, "api/v1/getLocations");
		}else{
			$result = $locationsData;
		}
	
		 return new JsonResponse( $result );
		

	}	


	public function getUsers($requestOrigin = null){

		$query = \Drupal::entityQuery('node')
  				->condition('type', 'user');

		$entity_ids = $query->execute();
		$data = entity_load_multiple('node', $entity_ids);
		
	
		$usersData = [];

		foreach($data as $userKey => $userData){
			$usersData[$userKey]["name"] = $userData->field_user_n->value;
			$usersData[$userKey]["lastname"] = $userData->field_lastname_->value;
			$usersData[$userKey]["email"] = $userData->field_email->value;
			$usersData[$userKey]["phone"] = $userData->field_cel_phone->value;
			$usersData[$userKey]["password"] = $userData->field_password->value;


			$imageData =  \Drupal\file\Entity\File::load($userData->field_photo->target_id);
			$imageLink= file_create_url($imageData->get("uri")->value);
			$usersData[$userKey]["image"] = $imageLink;

			$taxonomyTermEnterpriseId =  $userData->field_enterprise->target_id;
			$term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($taxonomyTermEnterpriseId);
			$taxonomyTermEnterpriseValue = $term->name->value;

			$taxonomyTermCityId =  $userData->field_city->target_id;
			$termCity = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($taxonomyTermCityId);
			$taxonomyTermCityValue = $termCity->name->value;

			$usersData[$userKey]["enterprise"]  = $taxonomyTermEnterpriseValue;
			$usersData[$userKey]["city"]  =  $taxonomyTermCityValue;
		}
		
		if(isset($_GET["enterprise"]) && isset($_GET["city"])){
			return new JsonResponse ("please set either city or enterprise filter");

		}else if(isset($_GET["enterprise"])){
			$usersData = $this->contentFilter($usersData,"enterprise",$_GET["enterprise"]);

		}else if(isset($_GET["city"])){
			$usersData = $this->contentFilter($usersData,"city",$_GET["city"]);
		}
		
		

		if(isset($_GET["page"]) && isset($_GET["limit"])){
			$result = $this->paginationLinks($usersData,"api/v1/getUsers");
		}else{
			$result = $usersData;
		}
	

		//si viene de php, no retorna json 
	 	//si viene de front, retorna json
		if($requestOrigin == "1"){
			return ($result);
			
		}else{ 
		return new JsonResponse( $result );
				


	}

	}

	
	public function getSocialNetworks(){
		
		$query = \Drupal::entityQuery('node')
		->condition('type', 'social_networks');

		$entity_ids = $query->execute();
		$data = entity_load_multiple('node', $entity_ids);
		
		$firstNode = array_values($data)[0]->toArray();

		$result["facebook"] = $firstNode["field_facebook"][0]["uri"];
		$result["instagram"] = $firstNode["field_instagram"][0]["uri"];
		$result["twitter"] = $firstNode["field_twitter"][0]["uri"];
		$result["youtube"] = $firstNode["field_youtube_link"][0]["uri"];
		// $this->prettyPrint(array_values($data)[0]->toArray());
		return new JsonResponse ($result);
	}


	public function validateUser(){
	
		$query = \Drupal::entityQuery('node')
  				->condition('type', 'user');

		$entity_ids = $query->execute();
		$data = entity_load_multiple('node', $entity_ids);

		$getEmail = [];
		$getPsw = [];

		// $inpMail = "asdf";// aqui lo que trae la db correo
		$inpPsw = "asdfsa";// aqui lo que trae la db contraseña $this->getUsers->password->value;
		$prueba = "texto";



		foreach($data as $userKey => $userData){
			$getEmail[$userKey]["email"] = $userData->field_email->value;
			$getEmail[$userKey]["password"] = $userData->field_password->value;

			// $getPsw[$userKey]["password"] = $userData->field_password->value;
		}


		
		

		// return new JsonResponse ($getPsw); 

		// exit;
		// $inpMail = $getUsers["email"];
		
		$Datos = json_decode(file_get_contents('php://input'), true);//datos que le envía el cliente
		if($Datos["correo"] == $getEmail && $Datos["psw"] == $getPsw){
			return new JsonResponse ("correcto");
		}
		else{
			return new JsonResponse ("incorrecto");
		}
		

	}
}
