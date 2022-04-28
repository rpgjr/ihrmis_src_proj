<?php
namespace App\Services\Jvscrw;

use App\Models\Tbljvs;
use App\Models\TbljvsCompetencies;
use App\Models\TbljvsCompetencyRatings;
use App\Models\TbljvsDutiesRspnsblts;
use App\Models\TblplantillaItems;
use Carbon\Carbon;
use DateTime;
use Exception;
use Mpdf\Mpdf as MPDF;
use stdClass;

class JvscrwService {
  /// CALLED IN THE JVS CONTROLLER
  public function updateOrCreateCmpntncyRtng($request, $type){
    if(!empty($request->min_com_desc)){
      $jvsQry = Tbljvs::where('jvs_id', $request->jvs_id)->first();
      $jvsQry->jvs_min_com_desc = $request->min_com_desc;
      $jvsQry->save();  
    }

    if($type == "save"){
      foreach ($request->competencies as $value) {
        if(!empty($value)){
          $this->addCompetencies($value, $request->jvs_id);
        }
      }
      $this->dutiesResponsibilities($request, $request->jvs_id);
      $this->onSubmitSign($request, $request->jvs_id);
      return "Successfully updated";
    }
  }

  public function createNewJvsVersion ($item) {
    $latestVersion = Tbljvs::where('jvs_itm_id', $item)->orderBy('jvs_version', 'DESC')->get();
    $newVersionQry = new Tbljvs();
    $newVersionQry->jvs_itm_id = $item;
    $newVersionQry->jvs_version = $latestVersion[0]->jvs_version + 1;
    $newVersionQry->jvs_min_com_desc = "";
    $newVersionQry->jvs_prepared = "";
    $newVersionQry->jvs_approved = "";
    $newVersionQry->jvs_signed_file = "";
    $newVersionQry->save(); 
    return 'Created new version';
  }

  public function uploadImage($request, $id, $signType){
    $request->validate([
      'signature' => "required"
    ]);

    date_default_timezone_set('Asia/Manila'); //define local time
    
    $jvsQry = Tbljvs::find($id);
    $uploadedDate = Carbon::now(); 

    if($signType == "prepared"){
      $arrCurrentData = explode("|", $jvsQry->jvs_prepared); 
      if(!empty($arrCurrentData[2])){  // Delete Current image 
        $img = $arrCurrentData[2];
        if($img != ""){
          $public = public_path('storage/jvscrw/prepared/'.$img);
          unlink($public);
        }
      }

      $imageObj = $request->file('signature'); // Get image from request
      $extentionStr = $imageObj->getClientOriginalExtension(); // Get image name amd extension 
      $filenameStr = 'prepared-img-' . $uploadedDate . "." . $extentionStr; // Assign database name
      $imageObj->storeAs('public/jvscrw/prepared', $filenameStr); // Store image to storage folder

      $preparedName = $arrCurrentData[0] ?? "";

      $data =  $preparedName . "|" . $uploadedDate . "|" . $filenameStr;
      $jvsQry->jvs_prepared = $data;
      $jvsQry->save(); 

      return "Image Prepared saved";
    }

    if($signType == "approved"){
      $arrCurrentData = explode("|", $jvsQry->jvs_approved);
      if(!empty($arrCurrentData[2])){
        $img = $arrCurrentData[2];
        if($img != ""){
          $public = public_path('storage/jvscrw/approved/'.$img);
          unlink($public);
        }
      }

      $imageObj = $request->file('signature'); // Get image from request
      $extentionStr = $imageObj->getClientOriginalExtension(); // Get image name amd extension 
      $filenameStr = 'approved-img-' .$uploadedDate . "." . $extentionStr; // Assign database name
      $imageObj->storeAs('public/jvscrw/approved', $filenameStr); // Store image to storage folder

      $approvedName = $arrCurrentData[0] ?? "";

      $data = $approvedName . "|" . $uploadedDate . "|" . $filenameStr;
      $jvsQry->jvs_approved = $data;
      $jvsQry->save();

      return "Image Approved saved";
    }   
  }

  public function getImageSaved($id){
    $applicantQry = Tbljvs::find($id);
    $preparedArr = empty($applicantQry->jvs_prepared) ? null : explode("|", $applicantQry->jvs_prepared);
    $aprrovedArr =  empty($applicantQry->jvs_prepared) ? null : explode("|", $applicantQry->jvs_approved);
    $preparedImgName = $preparedArr[2] ?? null;
    $approvedImgName = $aprrovedArr[2] ?? null;
    $preparedName = empty($preparedArr[0]) ?  "" : $preparedArr[0];
    $approvedName = empty($aprrovedArr[0]) ? "" : $aprrovedArr[0] ;
    $preparedPath = empty($preparedArr[2]) ? "" :  asset("storage/jvscrw/prepared/".$preparedImgName);
    $approvedPath = empty($aprrovedArr[2]) ? "" :  asset("storage/jvscrw/approved/".$approvedImgName);

    return response()->json([
      'prepared_name' => $preparedName,
      'approved_name' => $approvedName,
      'prepared' =>  $preparedPath,
      'approved' => $approvedPath,
      'requirement' =>  $applicantQry->jvs_min_com_desc
    ], 200);
  }

  public function submitGenerate($request, $id){
    $dataJvs = Tbljvs::find($id);
    $prepared = explode("|", $dataJvs->jvs_prepared);
    $approved = explode("|", $dataJvs->jvs_approved);

    $objectSign = new stdClass();
    $objectSign->app_name = $request->app_name ?? null;
    $objectSign->app_sign = $request->file('app_sign') ?? null;
    $objectSign->pre_name = $request->pre_name ?? null;
    $objectSign->pre_sign = $request->file('pre_sign') ?? null;

    try {
      // Case 1
      if(!empty($prepared[0]) && !empty($prepared[1]) && !empty($prepared[2]) && !empty($approved[0]) && !empty($approved[1]) && !empty($approved[2])){
        $this->generateFile($id);
        return $this->generateFile($id);
      }

      // Case 2
      if(!empty($prepared[0]) && !empty($prepared[1]) && !empty($prepared[2]) && !isset($request->app_name) && !isset($request->app_sign)){
        return $this->generateFile($id);
      }

      // case 3
      if(!isset($request->pre_name) && !isset($request->pre_sign) && !isset($request->app_name) && !isset($request->app_sign)){
        return $this->generateFile($id);
      }


      throw new Exception("Please Sign the approved");

    } catch (\Throwable $th) {
      throw $th;
    }

    
    
  }

  public function generateFile($id, $objectSign = null){
    
    date_default_timezone_set('Asia/Manila'); //define local time
    
    $jvsQry = Tbljvs::with('tbljvsDutiesRspnsblts')->find($id);
    
    $comp = TbljvsCompetencies::where('com_jvs_id', $id)->with(['tblComType' => function ($query) use ($id){
      $query->where('rtg_id', $id);
    }])->get(); 

    $preparedArr = empty($jvsQry->jvs_prepared) ? null : explode("|", $jvsQry->jvs_prepared);
    $aprrovedArr = empty($jvsQry->jvs_prepared) ? null : explode("|", $jvsQry->jvs_approved);
    
    $plantilla = TblplantillaItems::with('tbloffices', 'tblpositions.tblpositionCscStandards', 'tbldtyresponsibility')->find($jvsQry->jvs_itm_id);
    
    $approvedDate = DateTime::createFromFormat('Y-m-d H:i:s', $aprrovedArr[1] ?? Carbon::now())->format('m/d/Y');
    $preparedDate = DateTime::createFromFormat('Y-m-d H:i:s', $preparedArr[1] ?? Carbon::now())->format('m/d/Y');
    
    $preSign =  file_get_contents(asset('storage/jvscrw/prepared/'. $preparedArr[2]));
    
    // return $this->reqCompetency($comp);
    $jvs = [
      'jvs' => $jvsQry,
      'plantilla' => $plantilla,
      'standard' => $this->positionCscDataConverter($plantilla->tblpositions),
      'responsibility' => $jvsQry->tbljvsDutiesRspnsblts,
      'required_comp' => $this->reqCompetency($comp, false),
      'approved' => [ 
        'date' => $approvedDate ?? "",
        'name' => $aprrovedArr[0] ?? "",
        'sign' => $aprrovedArr[2] ?? ""
      ]
    ];
    
    $crw = [
      'jvs' => $jvsQry,
      'plantilla' => $plantilla,
      'standard' => $this->positionCscDataConverter($plantilla->tblpositions),
      'required_comp' => $this->reqCompetency($comp),
      'approved' => [ 
        'date' => $approvedDate,
        'name' => $aprrovedArr[0] ?? "",
        'sign' => $aprrovedArr[2] ?? ""
      ],
      'prepared' => [ 
        'date' => $preparedDate,
        'name' => $preparedArr[0] ?? "",
        'sign' => 'data:image/' . 'png' . ';base64,' . base64_encode($preSign)
      ],
    ];

    $report = new MPDF();
    $report->writeHTML(view('reports/jvsReportPdf', $jvs));
    $report->AddPage('L');
   
    $report->writeHTML(view('reports/crwReportPdf', $crw));
    // $report->Image($preSign , 0, 0, 210, 297, 'png', '', true, false);

    return $report->output();
   
  }

  /// CALLED IN updateOrCreateCmpntncyRtng FUCNTION
  private function addCompetencies($competencies, $id){
    if(isset($competencies)){
      $competencyQry = TbljvsCompetencies::where('com_type', $competencies['com_type'])->where('com_jvs_id', $id)->first();
      if(isset($competencyQry)){
        TbljvsCompetencies::where('com_type', $competencies['com_type'])->where('com_jvs_id', $id)->update([
            'com_jvs_id' => $id,
            'com_type' => $competencies['com_type'],
            'com_specific' => $competencies['com_specific'],
        ]);
        $this->arrayRating($competencies['tbl_com_type'], $id, $competencies['com_type']);
         
      } else {
        $create = new TbljvsCompetencies();
        $create->com_jvs_id = $competencies['com_jvs_id'];
        $create->com_type = $competencies['com_type'];
        $create->com_specific = $competencies['com_specific'];
        $create->save();
        $this->arrayRating($competencies['tbl_com_type'], $id, $competencies['com_type']);
       
      }
    }
  }
  /// CALLED IN updateOrCreateCmpntncyRtng FUCNTION
  private function arrayRating($ratings, $id ,$type){
    $count = 0;
    $checker = TbljvsCompetencyRatings::where('rtg_id', $id)->get();
   

    if(!empty($checker)){
      TbljvsCompetencyRatings::where('rtg_id', $id)->where('rtg_com_type', $type)->delete();
    }
    
    if(!empty($ratings)){
      foreach ($ratings as $value) {
        $count = $count + 1;
        $createQry = new TbljvsCompetencyRatings();
        $createQry->rtg_id = $id;
        $createQry->rtg_com_type = $value['rtg_com_type'];
        $createQry->rtg_seq_order = $count;
        $createQry->rtg_factor = $value['rtg_factor'];
        $createQry->rtg_percent = $value['rtg_percent'];
        $createQry->save();
      }
    }

  }
  /// CALLED IN updateOrCreateCmpntncyRtng FUCNTION
  private function dutiesResponsibilities($request){
    $count = 0;
    $read = TbljvsDutiesRspnsblts::where('dty_jvs_id', $request->jvs_id)->get();

    if (!empty($read)) {
      TbljvsDutiesRspnsblts::where('dty_jvs_id', $request->jvs_id)->delete();
    }
   
    foreach ($request->dty_res_item as $value) {
      $count = $count + 1;
      $add = new TbljvsDutiesRspnsblts();
      $add->dty_jvs_id = $request->jvs_id;
      $add->dty_jvs_order = $count;
      $add->dty_jvs_desc = $value['description'];
      $add->save();
    }
  }
  /// CALLED IN updateOrCreateCmpntncyRtng FUCNTION
  private function onSubmitSign($request, $id){
    date_default_timezone_set('Asia/Manila'); //define local time
    $applicantQry = Tbljvs::find($id);
    $imageLocPre = "";
    $imageLocApp = "";
    $preparedArr = [];
    $aprrovedArr  = [];

    if(!empty($applicantQry->jvs_prepared)){
      $preparedArr = empty($applicantQry->jvs_prepared) ? null : explode("|", $applicantQry->jvs_prepared);
      $imageLocPre = $preparedArr[2] ?? "";
    }

    if(!empty($applicantQry->jvs_approved)){
      $aprrovedArr = empty($applicantQry->jvs_prepared) ? null : explode("|", $applicantQry->jvs_approved);
      $imageLocApp = $aprrovedArr[2] ?? "";
    }

    if(isset($request->pre_name) && empty($preparedArr[0])){
      $pre_data = $request->pre_name . "|" . Carbon::now() . "|" . $imageLocPre;
      $applicantQry->jvs_prepared = $pre_data;
    }

    if(isset($request->app_name) && empty($aprrovedArr[0])){
      $app_data = $request->app_name . "|" . Carbon::now() . "|" . $imageLocApp;
      $applicantQry->jvs_approved = $app_data;
    }

    // if(isset($request->pre_name) &&  isset($request->pre_sign) && isset($request->app_name) &&  isset($request->app_sign)){
    //   $this->generateFile($request->jvs_id); 
    // }

    $applicantQry->save();
    return "Save worksheet";
    
  }

  // For getting RATING CALLED IN generateFile
  private function positionCscDataConverter($data){
    $arrContainer = [];
    $EducType = ['Bachelor\'s', 'Master\'s', 'PhD'];
    foreach ($data->tblpositionCscStandards as $value) {
      if($value["std_type"] == "ED"){
        $degree = explode("|",$value['std_keyword']);
        $printArr = [];
        foreach($degree as $educValue){
          $holder = explode(":",$educValue);
          array_push($printArr, $EducType[$holder[0] - 1] . " Degree in " . $holder[1] . " is relevant to the job,\n");
          // return $holder[0];
        }
        if(!empty($value["std_specifics"])){
          $arrContainer["ed"] = implode(" ", $printArr) . " and " . $value["std_specifics"];
        } else {
          $arrContainer["ed"] = implode(" ", $printArr) . ".";
        }
      } else if ($value["std_type"] == "EX") {
        $arrContainer["ex"] =  $value["std_quantity"] . " years of " . $value["std_keyword"] . " Experience";
      } else if ($value["std_type"] == "TR") {
        $arrContainer["tr"] = $value["std_quantity"] . " hours of " . $value["std_keyword"];
      } else if ($value["std_type"] == "CS") {
        $reArange = explode("|", $value["std_keyword"]);
        $arrContainer["cs"] = implode(" / ",$reArange);
      }
    }
    return [
      "pos_title" => $data->pos_title,
      "pos_short_name" => $data->pos_short_name,
      "pos_salary_grade" => $data->pos_salary_grade,
      "education" => nl2br($arrContainer["ed"]) ?? "",
      "experience" => $arrContainer["ex"] ?? "",
      "training" => $arrContainer["tr"]  ?? "",
      "eligibility" => $arrContainer["cs"] ?? ""
    ];
  }
  // For getting RATING CALLED IN generateFile
  private function reqCompetency($data = [], $requiredComp = true){
    $object = new stdClass();
    // $containerObj = new stdClass();
    $jobCompArr = [];
    foreach ($data as $value) {
      if ($value["com_type"] == "ED") {
        $object->ed = $value["tblComType"] ?? [];
        $object->edMaxMin = $this->ratingMinMax($value["tblComType"]);
      } else if ($value["com_type"] == "TR") {
        $object->tr = $value["tblComType"];
        $object->trMaxMin = $this->ratingMinMax($value["tblComType"]);
      } else if ($value["com_type"] == "EX") {
        $object->ex = $value["tblComType"] ?? [];
        $object->exMaxMin = $this->ratingMinMax($value["tblComType"]);
      } else if ($value["com_type"] == "WE") {
        $jobCompArr[0] = $this->factorWeightMaker($value);
        $object->we = $value["tblComType"] ?? [];
        $object->weMaxMin = $this->ratingMinMax($value["tblComType"]);
      } else if ($value["com_type"] == "OE") {
        $jobCompArr[1] =  $this->factorWeightMaker($value);
        $object->oe = $value["tblComType"] ?? [];
        $object->oeMaxMin = $this->ratingMinMax($value["tblComType"]);
      } else if ($value["com_type"] == "CW") {
        $jobCompArr[2] =  $this->factorWeightMaker($value);
        $object->cw = $value["tblComType"] ?? [];
        $object->cwMaxMin = $this->ratingMinMax($value["tblComType"]);
      } else if ($value["com_type"] == "AS") {
        $jobCompArr[3] =  $this->factorWeightMaker($value);
        $object->as = $value["tblComType"] ?? [];
        $object->asMaxMin = $this->ratingMinMax($value["tblComType"]);
      } else if ($value["com_type"] == "CS") {
        $jobCompArr[4] =  $this->factorWeightMaker($value);
        $object->cs = $value["tblComType"] ?? [];
        $object->exMaxMin = $this->ratingMinMax($value["tblComType"]);
      } else if ($value["com_type"] == "OT") {
        $jobCompArr[5] =  $this->factorWeightMaker($value);
        $object->ot = $value["tblComType"] ?? [];
        $object->otMaxMin = $this->ratingMinMax($value["tblComType"]);
      }
    }
    $object->jobComp = $jobCompArr;
    $object->jcMaxMin = $this->ratingMinMax($jobCompArr);
    // Getting counter
    $counter = 0;
    $counterArr = $requiredComp 
      ? [count($object->ex ?? []), count($object->tr ?? []), count($object->ed ?? []), count($object->jobComp ?? [])] 
      : [count($object->we ?? []), count($object->oe ?? []), count($object->cw ?? []), count($object->as ?? []), count($object->cs?? []), count($object->ot ?? [])]; 
    foreach ($counterArr as $value) {
      if($counter < $value){
        $counter = $value;
      }
    }
    $object->counter = $counter;

    return $object;
  }

  private function factorWeightMaker($item = []){
    $jobCompetency = $item["com_specific"];
    $jobCompArr = [];
    $valuableAsset = new stdClass(); 
    if(!empty($item["tblComType"])){
      foreach ($item["tblComType"] as $value) {
        $var = implode(": ", [$value['rtg_factor'], $value['rtg_percent']]);
        array_push($jobCompArr, $var);
      }
      $handler = implode(", ", $jobCompArr);
      $minMax = $this->ratingMinMax($item["tblComType"]);
      $valuableAsset->rtg_factor = $jobCompetency . " " . $handler;
      $valuableAsset->rtg_percent = $minMax[0];
      
    } else {
      $valuableAsset = null;
    }
    return $valuableAsset;
  }

  // For getting MINIMUM AND MAXIMUM RATING CALLED IN generateFile
  private function ratingMinMax($valueArr = []){
    $max = 0;
    $min = 0;
    foreach ($valueArr as $key => $item) {
      if($key == 0){
        $max = $item->rtg_percent;
        $min = $item->rtg_percent;
      }
      if($item->rtg_percent > $max) $max = $item->rtg_percent;
      if($item->rtg_percent < $min) $min = $item->rtg_percent;
    }

    return [$max, $min];
  }

  public function removeImage($id, $type){
    $jvsQry = Tbljvs::find($id);

    if($type == "prepared"){
      $arrCurrentData = explode("|", $jvsQry->jvs_prepared); 
      if(empty($jvsQry->jvs_prepared)){  // Delete Current image 
        $img = $arrCurrentData[2];
        if($img != ""){
          $public = public_path('storage/jvscrw/prepared/'.$img);
          unlink($public);
        }
      }
      $arr = [$arrCurrentData[0], $arrCurrentData[1], ""]; 
      $jvsQry->jvs_prepared = implode("|", $arr);
    } 
    
    if ($type == "approved"){
      $arrCurrentData = explode("|", $jvsQry->jvs_approved); 
      if(empty($jvsQry->jvs_approved)){  // Delete Current image 
        $img = $arrCurrentData[2];
        if($img != ""){
          $public = public_path('storage/jvscrw/prepared/'.$img);
          unlink($public);
        }
      }
      $arr = [$arrCurrentData[0], $arrCurrentData[1], ""]; 
      $jvsQry->jvs_approved = implode("|", $arr);
    }
  }
}



//
// else {
//   $latestVersion = Tbljvs::where('jvs_id', $request->jvs_id)->orderBy('jvs_version', 'DESC')->first();
//   $newJvs = new Tbljvs();
//   $newJvs->jvs_version = $latestVersion->jvs_version + 1;
//   $newJvs->jvs_itm_id = $latestVersion->jvs_itm_id;
//   $newJvs->jvs_min_com_desc = $request->min_com_desc;
//   $newJvs->jvs_prepared = "";
//   $newJvs->jvs_approved = "";
//   $newJvs->jvs_signed_file = "";
//   $newJvs->save();

//   return $newJvs->jvs_id;

//   foreach ($request->competencies as $value) {
//     if(!empty($value)){
//       $this->addCompetencies($value, $newJvs->jvs_id);
//     }
//   }

//   $this->dutiesResponsibilities($request, $newJvs->jvs_id);
//   $this->onSubmitSign($request, $newJvs->jvs_id);
// }