<?php
declare(strict_types=1);

/* Skin Wound Clinical Viewer — dependency-free, file-backed demo. */
const APP_NAME = 'Skin Wound Clinical Viewer';
const DEFAULT_PATIENT_ID = 'sample-patient';
const MAX_UPLOAD = 15728640;
$root = getenv('POSTOP_STORAGE_DIR') ?: __DIR__ . '/storage';
$patientId=(string)($_GET['patient']??$_POST['patient']??DEFAULT_PATIENT_ID);
if(!safe_id($patientId))fail('Patient record not found.',404);
$patientDir = $root . '/patients/' . $patientId;
$dataFile = $patientDir . '/record.json';
$accountFile = $root . '/account.json';

function fail(string $message, int $status = 400): void { http_response_code($status); header('Content-Type: text/plain; charset=utf-8'); exit($message); }
function safe_id(string $value): bool { return (bool)preg_match('/^[a-z0-9][a-z0-9-]{0,63}$/', $value); }
function new_id(string $prefix): string { return $prefix . '-' . strtolower(bin2hex(random_bytes(6))); }
function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function active_patient_id(): string { global $patientId; return $patientId; }
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}
function now(): string { return gmdate('c'); }
function atomic_write(string $path, array $data): void {
    $dir = dirname($path);
    if (!is_dir($dir) && !mkdir($dir, 0770, true) && !is_dir($dir)) fail('Storage directory could not be created.', 500);
    if (!is_writable($dir)) fail('Storage directory is not writable. Check POSTOP_STORAGE_DIR permissions.', 500);
    $tmp = tempnam($dir, '.write-');
    if ($tmp === false) fail('Could not prepare an atomic record write.', 500);
    $fp = fopen($tmp, 'wb');
    if (!$fp) fail('Could not open the temporary record.', 500);
    try {
        if (!flock($fp, LOCK_EX) || fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n") === false || !fflush($fp)) fail('Could not safely write the record.', 500);
        flock($fp, LOCK_UN); fclose($fp); $fp = null;
        if (!rename($tmp, $path)) fail('Could not commit the record.', 500);
        chmod($path, 0660);
    } finally { if (is_resource($fp)) fclose($fp); if (is_file($tmp)) unlink($tmp); }
}
function placeholder(string $serial): string { return '<svg xmlns="http://www.w3.org/2000/svg" width="960" height="720" viewBox="0 0 960 720"><rect width="960" height="720" fill="#07090b"/><text x="480" y="365" text-anchor="middle" fill="white" font-family="system-ui,sans-serif" font-size="64">'.htmlspecialchars($serial, ENT_XML1).'</text></svg>'; }
function seed(string $dataFile, string $patientDir): void {
    if (is_file($dataFile)) return;
    $today = new DateTimeImmutable('today'); $start = $today->modify('-12 days'); $partial = $today->modify('-2 days')->format('Y-m-d'); $complete = $today->modify('-1 day')->format('Y-m-d');
    $serials = ['IMG-7F3C92','IMG-A91D40','IMG-2B81EF']; $photos=[];
    foreach ($serials as $i=>$serial) { $id='photo-'.($i+1); $name=sprintf('%02d-shot-%s.svg',$i+1,$id); $dir="$patientDir/libraries/left-heel/wounds/lateral-incision/$complete"; if (!is_dir($dir)) mkdir($dir,0770,true); file_put_contents("$dir/$name",placeholder($serial)); $photos[]=['id'=>$id,'angle'=>'','caption'=>'Seeded reference image','created_at'=>now(),'sort_order'=>$i+1,'filename'=>$name,'mime'=>'image/svg+xml','bytes'=>filesize("$dir/$name")]; }
    $priorId='photo-seeded-prior-date';$priorName='01-shot-'.$priorId.'.svg';$priorDir="$patientDir/libraries/left-heel/wounds/lateral-incision/$partial";
    if (!is_dir($priorDir)) mkdir($priorDir,0770,true); file_put_contents("$priorDir/$priorName",placeholder('IMG-4D2A71'));
    $priorPhotos=[['id'=>$priorId,'angle'=>'Progress check','caption'=>'Seeded prior-day reference image','created_at'=>now(),'sort_order'=>1,'filename'=>$priorName,'mime'=>'image/svg+xml','bytes'=>filesize("$priorDir/$priorName")]];
    $data=['patient'=>['id'=>DEFAULT_PATIENT_ID,'name'=>'Sample Patient','account_number'=>'A-10042','age'=>64,'weight_kg'=>78.2,'gender'=>'Female','diagnosis'=>'Postoperative left heel wound with posterior heel donor site','avatar'=>'IMG-AVATAR-FEMALE.svg'],'seed_version'=>2,'revision'=>1,'libraries'=>[
      ['id'=>'left-heel','name'=>'Left Heel Post-op Recovery','type'=>'Postoperative Wound','custom_type'=>'','start_date'=>$start->format('Y-m-d'),'description'=>'Track recovery milestones and dressing observations.','revision'=>1,'notes'=>[['id'=>'note-lib-1','text'=>'Review progress at each dressing change.','created_at'=>now()]],'day_notes'=>[$partial=>[['id'=>'note-day-1','text'=>'Patient reported improved comfort.','created_at'=>now()]]],'wounds'=>[
        ['id'=>'lateral-incision','name'=>'Lateral incision','location'=>'Left lateral heel','active'=>true,'notes'=>[],'day_notes'=>[$partial=>[['id'=>'note-wound-1','text'=>'Observe incision edge and surrounding skin.','created_at'=>now()]]],'updates'=>[$partial=>['note'=>'Dressing changed; prior progress image available.','photos'=>$priorPhotos],$complete=>['note'=>'Routine progress image set.','photos'=>$photos]]],
        ['id'=>'donor-site','name'=>'Donor site','location'=>'Posterior heel','active'=>true,'notes'=>[],'day_notes'=>[],'updates'=>[$complete=>['note'=>'Clean and dry.','photos'=>[]]]]
      ]],
      ['id'=>'mole-monitor','name'=>'Mole Monitoring','type'=>'Mole Monitoring','custom_type'=>'','start_date'=>$today->modify('-20 days')->format('Y-m-d'),'description'=>'Demo longitudinal comparison library.','revision'=>1,'notes'=>[],'day_notes'=>[],'wounds'=>[['id'=>'medial-site','name'=>'Medial site','location'=>'Left ankle','active'=>true,'notes'=>[],'day_notes'=>[],'updates'=>[]]]]
    ]]; atomic_write($dataFile,$data);
}
function seed_additional_demo_photo_date(string $dataFile,string $patientDir): void {
    $raw=@file_get_contents($dataFile);$data=$raw===false?null:json_decode($raw,true);
    if(!is_array($data)||(int)($data['seed_version']??1)>=2||(string)($data['patient']['id']??'')!==DEFAULT_PATIENT_ID)return;
    foreach($data['libraries'] as &$library){
        if(($library['id']??'')!=='left-heel')continue;
        foreach(($library['wounds']??[]) as &$wound){
            if(($wound['id']??'')!=='lateral-incision')continue;
            $updates=$wound['updates']??[];$photoDates=[];$priorDate=null;
            foreach($updates as $ds=>$update){
                if(!empty($update['photos']))$photoDates[]=$ds;
            }
            if(!$photoDates)break;
            rsort($photoDates);$latestPhotoDate=$photoDates[0];
            foreach($updates as $ds=>$update)if($ds<$latestPhotoDate&&empty($update['photos'])&&($priorDate===null||$ds>$priorDate))$priorDate=$ds;
            if($priorDate===null)break;
            $id='photo-seeded-prior-date';$exists=false;
            foreach(($wound['updates'][$priorDate]['photos']??[]) as $photo)if(($photo['id']??'')===$id)$exists=true;
            if(!$exists){
                $name='01-shot-'.$id.'.svg';$dir="$patientDir/libraries/left-heel/wounds/lateral-incision/$priorDate";
                if(!is_dir($dir)&&!mkdir($dir,0770,true))fail('Could not create seeded photo storage.',500);
                $path="$dir/$name";
                if(!is_file($path)&&file_put_contents($path,placeholder('IMG-4D2A71'))===false)fail('Could not create the seeded prior-day photo.',500);
                $wound['updates'][$priorDate]['photos'][]=['id'=>$id,'angle'=>'Progress check','caption'=>'Seeded prior-day reference image','created_at'=>now(),'sort_order'=>count($wound['updates'][$priorDate]['photos'])+1,'filename'=>$name,'mime'=>'image/svg+xml','bytes'=>filesize($path)];
                $wound['updates'][$priorDate]['note']='Dressing changed; prior progress image available.';
            }
            $library['revision']=($library['revision']??0)+1;
            $data['revision']=($data['revision']??0)+1;$data['seed_version']=2;atomic_write($dataFile,$data);return;
        }
        unset($wound);
    }
    unset($library);
}
function pipeline_in_dir(): string { return __DIR__.'/pipeline-in'; }
function pipeline_list_dirs(string $path): array {
    $dirs=[];
    if(!is_dir($path))return $dirs;
    foreach(scandir($path)?:[] as $entry){
        if($entry==='.'||$entry==='..'||str_starts_with($entry,'.'))continue;
        if(is_dir($path.'/'.$entry))$dirs[]=$entry;
    }
    sort($dirs,SORT_NATURAL|SORT_FLAG_CASE);
    return $dirs;
}
function pipeline_list_photos(string $path): array {
    $files=[];
    if(!is_dir($path))return $files;
    foreach(scandir($path)?:[] as $entry){
        if($entry==='.'||$entry==='..'||str_starts_with($entry,'.'))continue;
        if(!is_file($path.'/'.$entry)||!preg_match('/\.(jpe?g|png|webp)$/i',$entry))continue;
        $files[]=$entry;
    }
    natcasesort($files);
    return array_values($files);
}
function pipeline_photo_labels(string $filename): array {
    $base=(string)pathinfo($filename,PATHINFO_FILENAME);
    if(preg_match('/^IMG[_-]?\d+$/i',$base))return ['angle'=>'','caption'=>''];
    if(preg_match('/^(.*)-([a-z])$/i',$base,$m))return ['angle'=>strtoupper($m[2]),'caption'=>ucwords(strtolower(str_replace(['-','_'],' ',$base)))];
    return ['angle'=>'','caption'=>ucwords(strtolower(str_replace(['-','_'],' ',$base)))];
}
function pipeline_capture_date(string $path,string $fallback): string {
    if(function_exists('exif_read_data')){
        $exif=@exif_read_data($path);
        if(is_array($exif)){
            $dt=(string)($exif['DateTimeOriginal']??$exif['DateTime']??'');
            if(preg_match('/^(\d{4}):(\d{2}):(\d{2})/',$dt,$m))return $m[1].'-'.$m[2].'-'.$m[3];
        }
    }
    return $fallback;
}
function pipeline_slug_id(string $slug,string $prefix): string {
    $slug=str_replace('postup-','postop-',$slug);
    $id=trim((string)preg_replace('/[^a-z0-9]+/','-',strtolower($slug)),'-');
    if($id===''||!safe_id($id))$id=$prefix.'-'.substr(sha1($slug),0,8);
    return $id;
}
function pipeline_guess_type(string $id): string {
    $hay=str_replace('-',' ',$id);
    if(preg_match('/wound|postop|incision/',$hay))return 'Postoperative Wound';
    if(preg_match('/mole|shave|bx|lesion/',$hay))return 'Mole Monitoring';
    if(preg_match('/pressure/',$hay))return 'Pressure Injury';
    return 'Custom';
}
function pipeline_library_meta(string $id,array $spec): array {
    $name=trim((string)($spec['name']??''))?:ucwords(str_replace('-',' ',$id));
    $allowed=['Pressure Injury','Mole Monitoring','Postoperative Wound','Custom'];
    $type=(string)($spec['type']??'');
    if(!in_array($type,$allowed,true))$type=pipeline_guess_type($id);
    $custom=trim((string)($spec['custom_type']??''));
    if($type==='Custom'&&$custom==='')$custom=$name;
    return ['id'=>$id,'name'=>$name,'type'=>$type,'custom_type'=>$type==='Custom'?$custom:'','start_date'=>'','description'=>trim((string)($spec['description']??'')),'revision'=>1,'notes'=>[],'day_notes'=>[],'wounds'=>[]];
}
function pipeline_library_spec(string $id,array $manifest): array {
    $all=is_array($manifest['libraries']??null)?$manifest['libraries']:[];
    $spec=is_array($all[$id]??null)?$all[$id]:[];
    return pipeline_library_meta($id,$spec);
}
function pipeline_wound_meta(string $slug,array $manifest): array {
    $slug=str_replace('postup-','postop-',$slug);
    $known=is_array($manifest['wounds']??null)?$manifest['wounds']:[];
    $spec=is_array($known[$slug]??null)?$known[$slug]:[];
    $id=trim((string)($spec['id']??''));
    if($id===''||!safe_id($id))$id=pipeline_slug_id($slug,'wound');
    $name=trim((string)($spec['name']??''))?:ucwords(str_replace('-',' ',$id));
    $location=trim((string)($spec['location']??''))?:$name;
    return ['id'=>$id,'name'=>$name,'location'=>$location];
}
function pipeline_wound_index(array &$library,array $meta): int {
    foreach($library['wounds'] as $i=>$wound)if(($wound['id']??'')===$meta['id'])return $i;
    $library['wounds'][]=['id'=>$meta['id'],'name'=>$meta['name'],'location'=>$meta['location'],'active'=>true,'notes'=>[],'day_notes'=>[],'updates'=>[]];
    return count($library['wounds'])-1;
}
function pipeline_photo_id(string $wound,string $date,string $filename): string {
    $file=trim((string)preg_replace('/[^a-z0-9]+/','-',strtolower((string)pathinfo($filename,PATHINFO_FILENAME))),'-');
    $id=trim((string)preg_replace('/-+/','-','photo-'.$wound.'-'.str_replace('-','',$date).'-'.$file),'-');
    if(strlen($id)>64)$id=rtrim(substr($id,0,64),'-');
    return $id!==''&&safe_id($id)?$id:'photo-'.substr(sha1($wound.$date.$filename),0,12);
}
function import_pipeline_photo(string $source,string $destDir,string $photoId,int $order,string $angle,string $caption): array {
    if(!is_dir($destDir)&&!mkdir($destDir,0770,true)&&!is_dir($destDir))fail('Could not create imported photo storage.',500);
    $info=@getimagesize($source);
    $types=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if(!$info||!isset($types[$info['mime']]))fail('Pipeline photo must be a valid JPEG, PNG, or WebP image.');
    $ext=$types[$info['mime']];
    $slug=trim(preg_replace('/[^a-z0-9]+/','-',strtolower($angle)),'-')?:'shot';
    $filename=sprintf('%02d-%s-%s.%s',$order,$slug,$photoId,$ext);
    $path="$destDir/$filename";
    if(!copy($source,$path))fail('Could not create imported photo.',500);
    return ['id'=>$photoId,'angle'=>$angle,'caption'=>$caption,'created_at'=>now(),'sort_order'=>$order,'filename'=>$filename,'mime'=>$info['mime'],'bytes'=>filesize($path)];
}
function pipeline_import_wound_photos(array &$library,string $woundDir,string $woundSlug,string $updateDate,string $patientDir,array $manifest): void {
    $files=pipeline_list_photos($woundDir);
    if(!$files)return;
    $meta=pipeline_wound_meta($woundSlug,$manifest);
    $wi=pipeline_wound_index($library,$meta);
    $library['wounds'][$wi]['updates'][$updateDate]??=['photos'=>[]];
    $order=count($library['wounds'][$wi]['updates'][$updateDate]['photos']);
    $dest="$patientDir/libraries/{$library['id']}/wounds/{$meta['id']}/$updateDate";
    foreach($files as $file){
        $order++;
        $labels=pipeline_photo_labels($file);
        $photoId=pipeline_photo_id($meta['id'],$updateDate,$file);
        $library['wounds'][$wi]['updates'][$updateDate]['photos'][]=import_pipeline_photo($woundDir.'/'.$file,$dest,$photoId,$order,$labels['angle'],$labels['caption']);
    }
}
function pipeline_finalize_library(array $library): array {
    $dates=[];
    foreach($library['wounds'] as &$wound){
        if(is_array($wound['updates']??null))ksort($wound['updates']);
        foreach($wound['updates']??[] as $date=>$_)$dates[]=$date;
    }
    unset($wound);
    if($dates){sort($dates);$library['start_date']=$dates[0];}
    else $library['start_date']=gmdate('Y-m-d');
    return $library;
}
function pipeline_read_manifest(string $dir): ?array {
    $file="$dir/patient.json";
    if(!is_file($file))return null;
    $raw=@file_get_contents($file);
    $data=$raw===false?null:json_decode($raw,true);
    if(!is_array($data)||trim((string)($data['name']??''))==='')return null;
    return $data;
}
function pipeline_available_patients(): array {
    $out=[];
    foreach(pipeline_list_dirs(pipeline_in_dir()) as $id){
        if(!safe_id($id))continue;
        $manifest=pipeline_read_manifest(pipeline_in_dir().'/'.$id);
        if($manifest===null)continue;
        $out[]=['id'=>$id,'name'=>trim((string)$manifest['name'])];
    }
    return $out;
}
function pipeline_patient_from_manifest(string $id,array $manifest): array {
    $gender=trim((string)($manifest['gender']??'Female'))?:'Female';
    $avatar=trim((string)($manifest['avatar']??''));
    if($avatar==='')$avatar=strcasecmp($gender,'male')===0?'IMG-AVATAR-MALE.svg':'IMG-AVATAR-FEMALE.svg';
    return ['id'=>$id,'name'=>trim((string)$manifest['name']),'account_number'=>trim((string)($manifest['account_number']??'')),'age'=>(int)($manifest['age']??0),'height'=>trim((string)($manifest['height']??'')),'weight_kg'=>(float)($manifest['weight_kg']??0),'gender'=>$gender,'diagnosis'=>trim((string)($manifest['diagnosis']??'')),'avatar'=>$avatar];
}
function pipeline_note_list($value): array {
    if(is_string($value))return ($t=trim($value))===''?[]:[$t];
    if(!is_array($value))return [];
    $notes=[];
    foreach($value as $item){
        $text=is_string($item)?$item:(is_array($item)?(string)($item['text']??''):'');
        $text=trim($text);
        if($text!=='')$notes[]=$text;
    }
    return $notes;
}
function pipeline_apply_manifest_notes(array &$libraries,array $manifest): void {
    $dayNotes=is_array($manifest['day_notes']??null)?$manifest['day_notes']:[];
    $woundNotes=is_array($manifest['wound_notes']??null)?$manifest['wound_notes']:[];
    foreach($libraries as &$library){
        $libId=(string)($library['id']??'');
        if(!is_array($library['day_notes']??null))$library['day_notes']=[];
        foreach(($dayNotes[$libId]??[]) as $date=>$texts){
            if(!is_string($date)||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date))continue;
            $library['day_notes'][$date]??=[];
            foreach(pipeline_note_list($texts) as $i=>$text)$library['day_notes'][$date][]=['id'=>'note-day-'.$libId.'-'.str_replace('-','',$date).'-'.$i,'text'=>$text,'created_at'=>now()];
        }
        foreach($library['wounds'] as &$wound){
            $woundId=(string)($wound['id']??'');
            $forWound=is_array($woundNotes[$libId][$woundId]??null)?$woundNotes[$libId][$woundId]:[];
            if(!$forWound)continue;
            if(!is_array($wound['day_notes']??null))$wound['day_notes']=[];
            foreach($forWound as $date=>$texts){
                if(!is_string($date)||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date))continue;
                $wound['day_notes'][$date]??=[];
                foreach(pipeline_note_list($texts) as $i=>$text)$wound['day_notes'][$date][]=['id'=>'note-wound-'.$woundId.'-'.str_replace('-','',$date).'-'.$i,'text'=>$text,'created_at'=>now()];
            }
        }
        unset($wound);
    }
    unset($library);
}
function pipeline_capture_fallback(string $slug,array $manifest,string $photo): string {
    $dates=is_array($manifest['capture_dates']??null)?$manifest['capture_dates']:[];
    $slug=str_replace('postup-','postop-',$slug);
    $given=trim((string)($dates[$slug]??''));
    if(preg_match('/^\d{4}-\d{2}-\d{2}$/',$given))return $given;
    $fromFile=pipeline_capture_date($photo,'');
    if(preg_match('/^\d{4}-\d{2}-\d{2}$/',$fromFile))return $fromFile;
    $mtime=@filemtime($photo);
    return $mtime?gmdate('Y-m-d',$mtime):gmdate('Y-m-d');
}
function import_pipeline_patient(string $root,string $id): string {
    if(!safe_id($id))fail('Patient record not found.',404);
    $source=pipeline_in_dir().'/'.$id;
    $manifest=pipeline_read_manifest($source);
    if($manifest===null)fail('That photo pipeline has no patient file.',404);
    $patientDir="$root/patients/$id";
    $dataFile="$patientDir/record.json";
    if(is_file($dataFile))fail('That patient record already exists.',409);
    if(!is_dir($patientDir)&&!mkdir($patientDir,0770,true)&&!is_dir($patientDir))fail('Could not create patient storage.',500);
    $lock=fopen($patientDir.'/.import.lock','c');
    if($lock===false||!flock($lock,LOCK_EX))fail('Could not lock patient import.',500);
    try {
        if(is_file($dataFile))fail('That patient record already exists.',409);
        $patient=pipeline_patient_from_manifest($id,$manifest);
        $dated=trim((string)($manifest['dated_library']??'progress'));
        if(!safe_id($dated))$dated='progress';
        $libraries=[];
        foreach(pipeline_list_dirs($source) as $entry){
            $path="$source/$entry";
            if(preg_match('/^\d{4}-\d{2}-\d{2}$/',$entry)){
                $libraries[$dated]??=pipeline_library_spec($dated,$manifest);
                foreach(pipeline_list_dirs($path) as $woundSlug)pipeline_import_wound_photos($libraries[$dated],$path.'/'.$woundSlug,$woundSlug,$entry,$patientDir,$manifest);
                continue;
            }
            $libId=pipeline_slug_id($entry,'library');
            $libraries[$libId]??=pipeline_library_spec($libId,$manifest);
            foreach(pipeline_list_dirs($path) as $woundSlug){
                $woundDir="$path/$woundSlug";
                $files=pipeline_list_photos($woundDir);
                if(!$files)continue;
                $date=pipeline_capture_fallback($woundSlug,$manifest,$woundDir.'/'.$files[0]);
                pipeline_import_wound_photos($libraries[$libId],$woundDir,$woundSlug,$date,$patientDir,$manifest);
            }
        }
        $ordered=[];
        $order=is_array($manifest['library_order']??null)?$manifest['library_order']:[];
        foreach($order as $libId){
            if(!is_string($libId)||empty($libraries[$libId]['wounds']))continue;
            $ordered[]=pipeline_finalize_library($libraries[$libId]);
            unset($libraries[$libId]);
        }
        foreach($libraries as $lib){if(!empty($lib['wounds']))$ordered[]=pipeline_finalize_library($lib);}
        if(!$ordered)fail('That photo pipeline did not contain any libraries.',500);
        pipeline_apply_manifest_notes($ordered,$manifest);
        atomic_write($dataFile,['patient'=>$patient,'seed_version'=>1,'revision'=>1,'libraries'=>$ordered]);
        ensure_avatar_assets($patientDir);
        return $patient['name'];
    } finally {
        flock($lock,LOCK_UN);
        fclose($lock);
    }
}
function seed_account(string $accountFile): void {
    if (is_file($accountFile)) return;
    $hash=password_hash('password',PASSWORD_DEFAULT);
    atomic_write($accountFile,[
        'version'=>3,
        'accounts'=>[
            ['display_name'=>'Weng','username'=>'admin','password_hash'=>$hash,'patient_ids'=>['sample-patient']],
            ['display_name'=>'Weng','username'=>'wf','password_hash'=>$hash,'patient_ids'=>['sample-patient','xiaoping-hong']],
        ],
    ]);
}
function read_account_file(): array {
    global $accountFile;
    $raw=@file_get_contents($accountFile);
    $decoded=$raw===false?null:json_decode($raw,true);
    if(!is_array($decoded))fail('The account configuration is unavailable or invalid.',500);
    return $decoded;
}
function account_entry($account): ?array {
    if(!is_array($account))return null;
    $hash=$account['password_hash']??null;
    if(!is_string($hash)||$hash==='')return null;
    $display=trim((string)($account['display_name']??''));
    $username=trim((string)($account['username']??''));
    if($display===''||$username==='')return null;
    $patientIds=[];
    $scoped=isset($account['patient_ids'])&&is_array($account['patient_ids']);
    if($scoped)foreach($account['patient_ids'] as $id)if(is_string($id)&&safe_id($id))$patientIds[]=$id;
    return ['display_name'=>$display,'username'=>$username,'password_hash'=>$hash,'patient_ids'=>$patientIds,'patient_scope'=>$scoped];
}
function load_accounts(): array {
    $decoded=read_account_file();
    $candidates=isset($decoded['accounts'])&&is_array($decoded['accounts'])?$decoded['accounts']:[$decoded];
    $accounts=[];
    foreach($candidates as $candidate){
        $entry=account_entry($candidate);
        if($entry)$accounts[]=$entry;
    }
    if(!$accounts)fail('The account configuration is unavailable or invalid.',500);
    return $accounts;
}
function account_can_access(array $account,string $patientId): bool {
    if(empty($account['patient_scope']))return true;
    return in_array($patientId,$account['patient_ids'],true);
}
function save_account(array $account): void {
    global $accountFile;
    $decoded=read_account_file();
    if(isset($decoded['accounts'])&&is_array($decoded['accounts'])){
        foreach($decoded['accounts'] as &$existing){
            if(!is_array($existing)||($existing['username']??'')!==$account['username'])continue;
            $existing['display_name']=$account['display_name'];
            $existing['password_hash']=$account['password_hash'];
            if(array_key_exists('patient_ids',$account))$existing['patient_ids']=$account['patient_ids'];
            unset($existing);
            atomic_write($accountFile,$decoded);
            return;
        }
        fail('The account configuration is unavailable or invalid.',500);
    }
    atomic_write($accountFile,$account);
}
function current_page_url(): string {
    $query=$_GET;
    unset($query['action']);
    return 'index.php'.($query?'?'.http_build_query($query,'','&',PHP_QUERY_RFC3986):'');
}
function placeholders_dir(): string { return __DIR__ . '/placeholders'; }
function avatar_svg(string $serial, string $gender): string {
    $female=strtolower($gender)==='female';
    $serialXml=htmlspecialchars($serial, ENT_XML1);
    $hair=$female
      ? '<path fill="#4a5f63" d="M196 248c18-86 78-148 124-148s106 62 124 148c8 38 4 70-10 92-22 14-70 24-114 24s-92-10-114-24c-14-22-18-54-10-92z"/><circle cx="320" cy="268" r="78" fill="#8aa0a4"/><path fill="#4a5f63" d="M232 248c8 42 40 72 88 72s80-30 88-72c-16 28-48 48-88 48s-72-20-88-48z"/><path fill="#6d8589" d="M176 560c18-92 70-150 144-150s126 58 144 150v160H176z"/>'
      : '<path fill="#3f5357" d="M230 214c10-54 44-96 90-96s80 42 90 96c6 28 2 48-8 62-20 8-52 14-82 14s-62-6-82-14c-10-14-14-34-8-62z"/><circle cx="320" cy="262" r="80" fill="#8aa0a4"/><path fill="#6d8589" d="M150 560c22-100 78-156 170-156s148 56 170 156v160H150z"/>';
    $size=$female?'26':'28';
    return '<svg xmlns="http://www.w3.org/2000/svg" width="640" height="800" viewBox="0 0 640 800"><rect width="640" height="800" fill="#07090b"/>'.$hair.'<text x="320" y="756" text-anchor="middle" fill="#fff" font-family="system-ui,sans-serif" font-size="'.$size.'" font-weight="700">'.$serialXml.'</text></svg>';
}
function ensure_avatar_assets(string $patientDir): void {
    $dir=placeholders_dir();
    if(!is_dir($dir)&&!mkdir($dir,0770,true)&&!is_dir($dir)) return;
    $female="$dir/female.svg"; $male="$dir/male.svg";
    if(!is_file($female)) file_put_contents($female,avatar_svg('AVATAR-FEMALE','female'));
    if(!is_file($male)) file_put_contents($male,avatar_svg('AVATAR-MALE','male'));
    $dest="$patientDir/IMG-AVATAR-FEMALE.svg";
    if(!is_file($dest)){
        $src=is_file($female)?file_get_contents($female):avatar_svg('IMG-AVATAR-FEMALE','female');
        if(is_string($src)) file_put_contents($dest,str_replace('>AVATAR-FEMALE<','>IMG-AVATAR-FEMALE<',$src));
    }
}
function patient_profile(array $d): array {
    $p=is_array($d['patient']??null)?$d['patient']:[];
    $gender=(string)($p['gender']??'Female');
    $avatar=(string)($p['avatar']??(strcasecmp($gender,'male')===0?'IMG-AVATAR-MALE.svg':'IMG-AVATAR-FEMALE.svg'));
    return [
        'id'=>(string)($p['id']??DEFAULT_PATIENT_ID),
        'name'=>(string)($p['name']??'Sample Patient'),
        'account_number'=>(string)($p['account_number']??'A-10042'),
        'age'=>(int)($p['age']??64),
        'height'=>trim((string)($p['height']??'')),
        'weight_kg'=>(float)($p['weight_kg']??78.2),
        'gender'=>$gender,
        'diagnosis'=>(string)($p['diagnosis']??'Postoperative left heel wound with posterior heel donor site'),
        'avatar'=>$avatar,
    ];
}
function wound_notes_for(array $w,string $date): array {
    $notes=$w['day_notes'][$date]??[];
    return is_array($notes)?array_values($notes):[];
}
function update_note_id(string $woundId,string $date): string { return 'update-note-'.$woundId.'-'.$date; }
function update_note_for(array $w,string $date): string {
    $id=update_note_id((string)($w['id']??''),$date);
    foreach(wound_notes_for($w,$date) as $note)if(($note['id']??'')===$id)return trim((string)($note['text']??''));
    return '';
}
function migrate_legacy_wound_notes(array &$d): bool {
    $changed=false;
    foreach($d['libraries'] as &$lib){
        foreach($lib['wounds'] as &$w){
            if(!isset($w['day_notes'])||!is_array($w['day_notes'])){$w['day_notes']=[];$changed=true;}
            $legacy=$w['notes']??[];
            if(!is_array($legacy)||!$legacy){
                if(!is_array($w['notes']??null)){$w['notes']=[];$changed=true;}
                continue;
            }
            $dates=array_keys($w['updates']??[]);
            sort($dates);
            $target=(string)($dates[0]??($lib['start_date']??''));
            if($target==='')continue;
            $w['day_notes'][$target]=array_values(array_merge($w['day_notes'][$target]??[],$legacy));
            $w['notes']=[];
            $changed=true;
        }
        unset($w);
    }
    unset($lib);
    return $changed;
}
function migrate_update_notes(array &$d): bool {
    $changed=false;
    foreach($d['libraries'] as &$lib){
        foreach($lib['wounds'] as &$w){
            if(!isset($w['day_notes'])||!is_array($w['day_notes'])){$w['day_notes']=[];$changed=true;}
            if(!is_array($w['updates']??null))continue;
            foreach($w['updates'] as $date=>&$update){
                if(!is_array($update)||!array_key_exists('note',$update))continue;
                $text=trim((string)$update['note']);
                if($text!==''){
                    $isDayNote=false;
                    foreach(($lib['day_notes'][$date]??[]) as $dayNote)if(trim((string)($dayNote['text']??''))===$text){$isDayNote=true;break;}
                    $w['day_notes'][$date]??=[];$id=update_note_id((string)($w['id']??''),(string)$date);$found=false;
                    foreach($w['day_notes'][$date] as $note)if(($note['id']??'')===$id||trim((string)($note['text']??''))===$text){$found=true;break;}
                    if(!$isDayNote&&!$found){
                        $created=now();
                        foreach(($update['photos']??[]) as $photo)if(!empty($photo['created_at'])){$created=(string)$photo['created_at'];break;}
                        $w['day_notes'][$date][]=['id'=>$id,'text'=>$text,'created_at'=>$created];
                    }
                }
                unset($update['note']);$changed=true;
            }
            unset($update);
        }
        unset($w);
    }
    unset($lib);
    return $changed;
}
function remove_duplicate_day_notes(array &$d): bool {
    $changed=false;
    foreach($d['libraries'] as &$lib){
        foreach($lib['wounds'] as &$w){
            foreach(($w['day_notes']??[]) as $date=>$notes){
                $dayTexts=[];
                foreach(($lib['day_notes'][$date]??[]) as $note)$dayTexts[]=trim((string)($note['text']??''));
                if(!$dayTexts||!is_array($notes))continue;
                $filtered=array_values(array_filter($notes,static fn($note)=>!in_array(trim((string)($note['text']??'')),$dayTexts,true)));
                if(count($filtered)===count($notes))continue;
                $w['day_notes'][$date]=$filtered;$changed=true;
            }
        }
        unset($w);
    }
    unset($lib);
    return $changed;
}
function load_data(): array { global $dataFile; $raw=@file_get_contents($dataFile); $d=$raw===false?null:json_decode($raw,true); if (!is_array($d)) fail('The patient record is unavailable or invalid.',500); $d['patient']=patient_profile($d); $changed=migrate_legacy_wound_notes($d); if(migrate_update_notes($d))$changed=true; if(remove_duplicate_day_notes($d))$changed=true; if($changed)save_data($d); return $d; }
function save_data(array $d): void { global $dataFile; $d['revision']=($d['revision']??0)+1; atomic_write($dataFile,$d); }
function &library(array &$d,string $id): array { foreach($d['libraries'] as &$x) if($x['id']===$id)return $x; fail('Library not found.',404); }
function &wound(array &$lib,string $id): array { foreach($lib['wounds'] as &$x) if($x['id']===$id)return $x; fail('Wound not found.',404); }
function remove_tree(string $path,string $scope): void { $realBase=realpath($scope); $real=realpath($path); if(!$real||!$realBase||!str_starts_with($real.DIRECTORY_SEPARATOR,$realBase.DIRECTORY_SEPARATOR)) return; if(is_link($real)) fail('Refusing to delete linked storage.',400); $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($real,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST); foreach($it as $f){if($f->isLink())fail('Refusing to delete linked storage.',400); $f->isDir()?rmdir($f->getPathname()):unlink($f->getPathname());} rmdir($real); }

session_name('skin_wound_viewer'); session_start(['cookie_httponly'=>true,'cookie_samesite'=>'Strict','use_strict_mode'=>true]);
$action=$_GET['action']??'';
if($action==='manifest'){header('Content-Type: application/manifest+json');echo json_encode(['name'=>APP_NAME,'short_name'=>'Wound Viewer','start_url'=>'./index.php','scope'=>'./','display'=>'standalone','background_color'=>'#f4f7f6','theme_color'=>'#164e63','icons'=>[['src'=>'index.php?action=icon&size=192','sizes'=>'192x192','type'=>'image/svg+xml'],['src'=>'index.php?action=icon&size=512','sizes'=>'512x512','type'=>'image/svg+xml']]]);exit;}
if($action==='icon'){header('Content-Type: image/svg+xml');$s=($_GET['size']??'192')==='512'?512:192;echo '<svg xmlns="http://www.w3.org/2000/svg" width="'.$s.'" height="'.$s.'"><rect width="100%" height="100%" rx="36" fill="#164e63"/><path d="M'.($s*.28).' '.($s*.5).'h'.($s*.44).'M'.($s*.5).' '.($s*.28).'v'.($s*.44).'" stroke="white" stroke-width="'.($s*.1).'" stroke-linecap="round"/></svg>';exit;}
if($action==='service-worker'){header('Content-Type: application/javascript');header('Service-Worker-Allowed: ./');echo <<<'JS'
const SHELL='swcv-shell-v1'; self.addEventListener('install',e=>e.waitUntil(caches.open(SHELL).then(c=>c.add('./index.php')).then(()=>self.skipWaiting()))); self.addEventListener('activate',e=>e.waitUntil(self.clients.claim())); self.addEventListener('fetch',e=>{const u=new URL(e.request.url);if(u.origin!==location.origin)return;if(e.request.method!=='GET')return;if(u.searchParams.get('action')==='media'){e.respondWith(caches.match(e.request).then(x=>x||fetch(e.request)));return}if(e.request.mode==='navigate'){e.respondWith(fetch(e.request).catch(()=>caches.match(e.request).then(x=>x||caches.match('./index.php'))))}});
JS;exit;}

$samplePatientDir=$root.'/patients/'.DEFAULT_PATIENT_ID;$sampleDataFile=$samplePatientDir.'/record.json';
if(!is_file($sampleDataFile)||!is_file($accountFile)){
    $_SESSION['setup_csrf']??=bin2hex(random_bytes(24));
    if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'&&($_POST['op']??'')==='create_sample_pack'){
        if(!hash_equals((string)$_SESSION['setup_csrf'],(string)($_POST['setup_csrf']??'')))fail('Invalid setup request.',403);
        seed($sampleDataFile,$samplePatientDir);
        seed_additional_demo_photo_date($sampleDataFile,$samplePatientDir);
        seed_account($accountFile);
        ensure_avatar_assets($samplePatientDir);
        header('Location: index.php');exit;
    }
    first_run_page((string)$_SESSION['setup_csrf']);
}
if(!is_file($dataFile))fail('Patient record not found.',404);
ensure_avatar_assets($patientDir);

$accounts=load_accounts();
$authed=($_SESSION['auth']??false)===true;
if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['op']??'')==='login'){
    $username=trim((string)($_POST['username']??''));$password=(string)($_POST['password']??'');$matched=null;
    foreach($accounts as $candidate)if(hash_equals($candidate['username'],$username)&&password_verify($password,$candidate['password_hash'])){$matched=$candidate;break;}
    if($matched){session_regenerate_id(true);$_SESSION=['auth'=>true,'username'=>$matched['username'],'csrf'=>bin2hex(random_bytes(24))];header('Location: index.php',true,303);exit;}
    $_SESSION['login_error']='Incorrect username or password.';header('Location: index.php',true,303);exit;
}
if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['op']??'')==='logout'){
    $_SESSION=[];
    if(ini_get('session.use_cookies')){
        $params=session_get_cookie_params();
        setcookie(session_name(),'',['expires'=>time()-42000,'path'=>$params['path'],'domain'=>$params['domain'],'secure'=>$params['secure'],'httponly'=>$params['httponly'],'samesite'=>$params['samesite']??'Strict']);
    }
    session_destroy();
    header('Clear-Site-Data: "cache", "storage"');
    header('Location: index.php',true,303);
    exit;
}
$account=null;
if($authed){
    foreach($accounts as $candidate)if(hash_equals($candidate['username'],(string)($_SESSION['username']??''))){$account=$candidate;break;}
    if(!$account){$_SESSION=[];session_destroy();header('Location: index.php',true,303);exit;}
    if((isset($_GET['patient'])||isset($_POST['patient']))&&!account_can_access($account,$patientId))fail('Patient record not found.',404);
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    $op=$_POST['op']??'';
    if(!$authed) fail('Authentication required.',401);
    if(!hash_equals((string)($_SESSION['csrf']??''),(string)($_POST['csrf']??'')))fail('Invalid CSRF token.',403);
    if($op==='account_save'){
        $display=trim((string)($_POST['display_name']??''));
        if($display===''||strlen($display)>80)fail('Enter a name of 80 characters or fewer.');
        $current=(string)($_POST['current_password']??'');$new=(string)($_POST['new_password']??'');$confirm=(string)($_POST['confirm_password']??'');
        $changingPassword=$current!==''||$new!==''||$confirm!=='';
        if($changingPassword){
            if($current===''||$new===''||$confirm==='')fail('Complete every password field to change your password.');
            if(!password_verify($current,$account['password_hash']))fail('Your current password is incorrect.');
            if(strlen($new)<8)fail('Your new password must contain at least 8 characters.');
            if(!hash_equals($new,$confirm))fail('Your new password and confirmation do not match.');
            $account['password_hash']=password_hash($new,PASSWORD_DEFAULT);
        }
        $account['display_name']=$display;
        save_account($account);
        $_SESSION['flash']='Account information saved.';
        $return=(string)($_POST['return_to']??'index.php');
        if(!preg_match('/^index\.php(?:\?[^\r\n]*)?$/',$return))$return='index.php';
        header('Location: '.$return);exit;
    }
    if($op==='patient_import'){
        $pipelineId=(string)($_POST['pipeline_id']??'');
        if(!safe_id($pipelineId))fail('Patient record not found.',404);
        $imported=import_pipeline_patient($root,$pipelineId);
        if(!account_can_access($account,$pipelineId)){$account['patient_ids'][]=$pipelineId;$account['patient_scope']=true;save_account($account);}
        $_SESSION['flash']=$imported.' record imported.';
        header('Location: index.php');exit;
    }
    $d=load_data(); if(isset($_POST['expected_revision'])&&(int)$_POST['expected_revision']!==(int)$d['revision'])fail('Conflict: the server record changed. Reload the server version or review and retry the pending change.',409); $libId=(string)($_POST['library_id']??''); if($libId!==''&&!safe_id($libId))fail('Invalid library ID.');
    $redirect='index.php?patient='.active_patient_id();
    try {
      if($op==='library_save'){$name=trim((string)($_POST['name']??''));$type=(string)($_POST['type']??'');$date=(string)($_POST['start_date']??'');$allowed=['Pressure Injury','Mole Monitoring','Postoperative Wound','Custom'];if($name===''||!in_array($type,$allowed,true)||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)||$date>gmdate('Y-m-d')||($type==='Custom'&&trim((string)($_POST['custom_type']??''))===''))fail('Name, valid type, and a non-future starting date are required.');if($libId===''){$libId=new_id('library');$d['libraries'][]=['id'=>$libId,'name'=>$name,'type'=>$type,'custom_type'=>trim((string)($_POST['custom_type']??'')),'start_date'=>$date,'description'=>trim((string)($_POST['description']??'')),'revision'=>1,'notes'=>[],'day_notes'=>[],'wounds'=>[]];}else{$l=&library($d,$libId);foreach(['name'=>$name,'type'=>$type,'custom_type'=>trim((string)($_POST['custom_type']??'')),'start_date'=>$date,'description'=>trim((string)($_POST['description']??''))] as $k=>$v)$l[$k]=$v;$l['revision']++;} $redirect.='&library='.$libId;}
      elseif($op==='library_delete'){$idx=null;foreach($d['libraries'] as $i=>$l)if($l['id']===$libId)$idx=$i;if($idx===null)fail('Library not found.',404);remove_tree("$patientDir/libraries/$libId","$patientDir/libraries");array_splice($d['libraries'],$idx,1);}
      elseif($op==='wound_save'){$l=&library($d,$libId);$wid=(string)($_POST['wound_id']??'');$name=trim((string)($_POST['name']??''));if($name==='')fail('Wound name is required.');$payload=['name'=>$name,'location'=>trim((string)($_POST['location']??'')),'active'=>isset($_POST['active'])];if($wid===''){$wid=new_id('wound');$l['wounds'][]=['id'=>$wid]+$payload+['notes'=>[],'day_notes'=>[],'updates'=>[]];}else{$w=&wound($l,$wid);foreach($payload as $k=>$v)$w[$k]=$v;}$l['revision']++;$redirect.='&library='.$libId;}
      elseif($op==='wound_delete'){$l=&library($d,$libId);$wid=(string)$_POST['wound_id'];$idx=null;foreach($l['wounds'] as $i=>$w)if($w['id']===$wid)$idx=$i;if($idx===null)fail('Wound not found.',404);remove_tree("$patientDir/libraries/$libId/wounds/$wid","$patientDir/libraries/$libId/wounds");array_splice($l['wounds'],$idx,1);$l['revision']++;$redirect.='&library='.$libId;}
      elseif($op==='update_save'){$l=&library($d,$libId);$w=&wound($l,(string)$_POST['wound_id']);$date=(string)$_POST['date'];if($date<$l['start_date']||$date>gmdate('Y-m-d'))fail('Date is outside this library timeline.');$old=$w['updates'][$date]??[];$payload=['photos'=>$old['photos']??[]];$assess=assessment_from_post();if(assessment_filled($assess))$payload['assessment']=$assess;$w['updates'][$date]=$payload;$text=trim((string)($_POST['note']??''));if(!isset($w['day_notes'])||!is_array($w['day_notes']))$w['day_notes']=[];$w['day_notes'][$date]??=[];$id=update_note_id($w['id'],$date);$found=null;foreach($w['day_notes'][$date] as $i=>$note)if(($note['id']??'')===$id)$found=$i;if($text===''){if($found!==null)array_splice($w['day_notes'][$date],$found,1);}elseif($found===null)$w['day_notes'][$date][]=['id'=>$id,'text'=>$text,'created_at'=>now()];else{$w['day_notes'][$date][$found]['text']=$text;$w['day_notes'][$date][$found]['updated_at']=now();}$l['revision']++;$redirect.='&library='.$libId.'&date='.$date;}
      elseif($op==='update_delete'){$l=&library($d,$libId);$w=&wound($l,(string)$_POST['wound_id']);$date=(string)$_POST['date'];unset($w['updates'][$date]);remove_tree("$patientDir/libraries/$libId/wounds/{$w['id']}/$date","$patientDir/libraries/$libId/wounds/{$w['id']}");$l['revision']++;$redirect.='&library='.$libId.'&date='.$date;}
      elseif(in_array($op,['note_add','note_edit','note_delete'],true)){$l=&library($d,$libId);$scope=(string)$_POST['scope'];if($scope==='library')$notes=&$l['notes'];elseif($scope==='day'){$date=(string)$_POST['date'];if($date<$l['start_date']||$date>gmdate('Y-m-d'))fail('Invalid note date.');$l['day_notes'][$date]??=[];$notes=&$l['day_notes'][$date];}elseif($scope==='wound'){$w=&wound($l,(string)$_POST['wound_id']);$date=(string)$_POST['date'];if($date<$l['start_date']||$date>gmdate('Y-m-d'))fail('Invalid note date.');$w['day_notes'][$date]??=[];$notes=&$w['day_notes'][$date];}else fail('Invalid note scope.');$nid=(string)($_POST['note_id']??'');if($op==='note_add'){$text=trim((string)$_POST['text']);if($text==='')fail('Note cannot be empty.');$notes[]=['id'=>new_id('note'),'text'=>$text,'created_at'=>now()];}else{$found=null;foreach($notes as $i=>$n)if($n['id']===$nid)$found=$i;if($found===null)fail('Note not found.',404);if($op==='note_delete')array_splice($notes,$found,1);else{$text=trim((string)$_POST['text']);if($text==='')fail('Note cannot be empty.');$notes[$found]['text']=$text;$notes[$found]['updated_at']=now();}}$l['revision']++;$redirect.='&library='.$libId.(isset($date)?'&date='.$date:'');}
      elseif(in_array($op,['placeholder','upload'],true)){$l=&library($d,$libId);$w=&wound($l,(string)$_POST['wound_id']);$date=(string)$_POST['date'];if(!isset($w['updates'][$date]))fail('Create the wound update first.');$id=new_id('photo');$order=count($w['updates'][$date]['photos'])+1;$angle=trim((string)($_POST['angle']??''));$ext='svg';$mime='image/svg+xml';$content='';if($op==='placeholder'){$serial='IMG-'.strtoupper(bin2hex(random_bytes(3)));$content=placeholder($serial);}else{if(!isset($_FILES['photo'])||$_FILES['photo']['error']!==UPLOAD_ERR_OK)fail('Choose an image to upload.');if($_FILES['photo']['size']>MAX_UPLOAD)fail('Image exceeds the 15 MB limit.',413);$info=@getimagesize($_FILES['photo']['tmp_name']);$types=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];if(!$info||!isset($types[$info['mime']]))fail('Upload must be a valid JPEG, PNG, or WebP image.');$mime=$info['mime'];$ext=$types[$mime];}$slug=preg_replace('/[^a-z0-9]+/','-',strtolower($angle));$name=sprintf('%02d-%s-%s.%s',$order,trim($slug,'-')?:'shot',$id,$ext);$dir="$patientDir/libraries/$libId/wounds/{$w['id']}/$date";if(!is_dir($dir)&&!mkdir($dir,0770,true))fail('Could not create photo storage.',500);$path="$dir/$name";$ok=$op==='placeholder'?file_put_contents($path,$content)!==false:move_uploaded_file($_FILES['photo']['tmp_name'],$path);if(!$ok)fail('Could not store the image.',500);$w['updates'][$date]['photos'][]=['id'=>$id,'angle'=>$angle,'caption'=>trim((string)($_POST['caption']??'')),'created_at'=>now(),'sort_order'=>$order,'filename'=>$name,'mime'=>$mime,'bytes'=>filesize($path)];$l['revision']++;$redirect.='&library='.$libId.'&date='.$date;}
      elseif(in_array($op,['photo_edit','photo_delete','photo_move'],true)){$l=&library($d,$libId);$w=&wound($l,(string)$_POST['wound_id']);$date=(string)$_POST['date'];$photos=&$w['updates'][$date]['photos'];$pi=null;foreach($photos as $i=>$p)if($p['id']===(string)$_POST['photo_id'])$pi=$i;if($pi===null)fail('Photo not found.',404);$dir="$patientDir/libraries/$libId/wounds/{$w['id']}/$date";if($op==='photo_delete'){if(is_file("$dir/{$photos[$pi]['filename']}"))unlink("$dir/{$photos[$pi]['filename']}");array_splice($photos,$pi,1);}elseif($op==='photo_edit'){$photos[$pi]['angle']=trim((string)$_POST['angle']);$photos[$pi]['caption']=trim((string)($_POST['caption']??''));}else{$to=$pi+((string)$_POST['direction']==='left'?-1:1);if(isset($photos[$to]))[$photos[$pi],$photos[$to]]=[$photos[$to],$photos[$pi]];}foreach($photos as $i=>&$p){$ext=pathinfo($p['filename'],PATHINFO_EXTENSION);$slug=trim(preg_replace('/[^a-z0-9]+/','-',strtolower($p['angle'])),'-')?:'shot';$new=sprintf('%02d-%s-%s.%s',$i+1,$slug,$p['id'],$ext);if($new!==$p['filename']&&is_file("$dir/{$p['filename']}")){rename("$dir/{$p['filename']}","$dir/.tmp-{$p['id']}.$ext");$p['_new']=$new;}$p['sort_order']=$i+1;}unset($p);foreach($photos as &$p)if(isset($p['_new'])){$tmp="$dir/.tmp-{$p['id']}.".pathinfo($p['filename'],PATHINFO_EXTENSION);rename($tmp,"$dir/{$p['_new']}");$p['filename']=$p['_new'];unset($p['_new']);}unset($p);$l['revision']++;$redirect.='&library='.$libId.'&date='.$date;}
      else fail('Unknown action.');
      save_data($d);$_SESSION['flash']='Changes saved.';
      if(!str_contains($redirect,'view='))$redirect.=(str_contains($redirect,'?')?'&':'?').'view='.((($_GET['view']??'')==='gallery')?'gallery':'day');
      header('Location: '.$redirect);exit;
    } catch(Throwable $e){fail('Unable to complete request: '.$e->getMessage(),500);}
}

if(in_array($action,['media','avatar','avatar-generic','sync-manifest','snapshot','replay'],true)&&!$authed)fail('Authentication required.',401);
if($action==='avatar'){
    $d=load_data();$p=patient_profile($d);$file=basename((string)$p['avatar']);
    if(!preg_match('/^[A-Za-z0-9._-]+\.(svg|png|jpe?g|webp)$/',$file))fail('Avatar unavailable.',404);
    $path="$patientDir/$file";$real=realpath($path);$base=realpath($patientDir);
    if(!$real||!$base||!str_starts_with($real,$base.DIRECTORY_SEPARATOR)||!is_file($real))fail('Avatar unavailable.',404);
    $ext=strtolower(pathinfo($file,PATHINFO_EXTENSION));
    $mimes=['svg'=>'image/svg+xml','png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','webp'=>'image/webp'];
    header('Content-Type: '.($mimes[$ext]??'application/octet-stream'));header('Content-Length: '.filesize($real));header('Cache-Control: private, max-age=86400');header('X-Content-Type-Options: nosniff');readfile($real);exit;
}
if($action==='avatar-generic'){
    $g=strtolower((string)($_GET['gender']??''))==='male'?'male':'female';
    $path=placeholders_dir().'/'.$g.'.svg';
    if(!is_file($path))fail('Placeholder unavailable.',404);
    header('Content-Type: image/svg+xml');header('Content-Length: '.filesize($path));header('Cache-Control: private, max-age=86400');header('X-Content-Type-Options: nosniff');readfile($path);exit;
}
if($action==='media'){$d=load_data();$pid=(string)($_GET['id']??'');foreach($d['libraries'] as $l)foreach($l['wounds'] as $w)foreach($w['updates'] as $date=>$u)foreach($u['photos'] as $p)if($p['id']===$pid){$path="$patientDir/libraries/{$l['id']}/wounds/{$w['id']}/$date/{$p['filename']}";if(!is_file($path))fail('Media unavailable.',404);header('Content-Type: '.$p['mime']);header('Content-Length: '.filesize($path));header('Cache-Control: private, max-age=31536000, immutable');header('X-Content-Type-Options: nosniff');readfile($path);exit;}fail('Media not found.',404);}
if($action==='snapshot'){header('Content-Type: application/json');echo json_encode(load_data(),JSON_UNESCAPED_SLASHES);exit;}
if($action==='sync-manifest'){$d=load_data();$media=[];$bytes=0;foreach($d['libraries'] as $l)foreach($l['wounds'] as $w)foreach($w['updates'] as $u)foreach($u['photos'] as $p){$url='index.php?action=media&patient='.rawurlencode(active_patient_id()).'&id='.$p['id'].'&v='.$l['revision'];$media[]=['id'=>$p['id'],'url'=>$url,'bytes'=>$p['bytes']??0];$bytes+=$p['bytes']??0;}header('Content-Type: application/json');echo json_encode(['patient_id'=>active_patient_id(),'revision'=>$d['revision'],'generated_at'=>now(),'snapshot_url'=>'index.php?action=snapshot&patient='.rawurlencode(active_patient_id()).'&v='.$d['revision'],'media'=>$media,'photo_count'=>count($media),'total_bytes'=>$bytes]);exit;}
if($action==='replay'){fail('Use the online forms to review and apply pending changes.',409);}

$csrf=$_SESSION['csrf']??'';$loginError=$_SESSION['login_error']??'';unset($_SESSION['login_error']);$flash=$_SESSION['flash']??'';unset($_SESSION['flash']);
if(!$authed): ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><title>Sign in · <?=APP_NAME?></title><style><?=css()?></style></head><body><a class="skip-link" href="#main">Skip to main content</a><main id="main" class="login" tabindex="-1"><section class="login-card"><div class="brandmark" aria-hidden="true">+</div><p class="eyebrow">Clinical recordkeeping demo</p><h1><?=APP_NAME?></h1><p class="muted">Sign in to review longitudinal wound records.</p><?php if($loginError):?><div class="alert" role="alert" id="login-error"><?=h($loginError)?></div><?php endif?><form method="post"<?=$loginError?' aria-describedby="login-error"':''?>><input type="hidden" name="op" value="login"><label>Username<input name="username" autocomplete="username" required></label><label>Password<input name="password" type="password" autocomplete="current-password" required></label><button type="submit">Sign in</button></form><div class="demo"><strong>Demo-only credentials</strong><button type="button" class="demo-row" data-user="admin" data-pass="password"><span class="demo-cred"><code>admin</code> / <code>password</code></span><small>Sample Patient</small></button><button type="button" class="demo-row" data-user="wf" data-pass="password"><span class="demo-cred"><code>wf</code> / <code>password</code></span><small>Sample Patient, Xiaoping Hong</small></button></div></section></main><?=app_footer()?><script>document.querySelectorAll('.demo-row').forEach(function(btn){btn.addEventListener('click',function(){var f=document.querySelector('form');var u=f.querySelector('[name=username]');var p=f.querySelector('[name=password]');u.value=btn.dataset.user;p.value=btn.dataset.pass;u.focus()});});</script></body></html><?php exit;endif;
$d=load_data();$profile=patient_profile($d);$patient=isset($_GET['patient']);$view=(($_GET['view']??'')==='gallery')?'gallery':'day';$galleryOrder=gallery_order();$selectedId=(string)($_GET['library']??($d['libraries'][0]['id']??''));$selected=null;foreach($d['libraries'] as $l)if($l['id']===$selectedId)$selected=$l;if($selected){$date=(string)($_GET['date']??'');if($date===''||$date<$selected['start_date']||$date>gmdate('Y-m-d'))$date=latest_relevant_date($selected);}else{$date=gmdate('Y-m-d');}
?><!doctype html><html lang="en" data-revision="<?=h($d['revision'])?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#164e63"><link rel="manifest" href="index.php?action=manifest"><title><?=APP_NAME?></title><style><?=css()?></style></head><body><a class="skip-link" href="#main">Skip to main content</a><header class="topbar"><a class="wordmark" href="index.php"><span aria-hidden="true">+</span><?=APP_NAME?></a><div class="top-actions"><button type="button" id="reviewPending" class="ghost small" hidden>Review and sync 0 changes</button><button type="button" id="editMode" class="edit-mode" aria-pressed="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm17.71-10.21a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg><span>Edit Mode</span><small>Off</small></button><span id="network" class="status" role="status" aria-live="polite">Online</span><form method="post"><input type="hidden" name="op" value="logout"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><button class="ghost" type="submit">Log out</button></form></div></header><?php if($flash):?><div class="toast" role="status"><?=h($flash)?></div><?php endif?><main id="main" class="app" tabindex="-1">
<?=account_modal($account,$csrf,current_page_url())?><?=edit_mode_bar()?><?php if(!$patient):?><section class="pagehead"><div><p class="eyebrow">Workspace</p><h1>Patients</h1></div></section><?=patient_picker(patient_records(),$csrf)?>
<?php else:?><nav class="crumb" aria-label="Breadcrumb"><a href="index.php">Patients</a><span aria-hidden="true">/</span><strong><?=h($profile['name'])?></strong></nav><?=patient_facts_compact($profile)?><div class="layout"><aside class="sidebar"><div class="side-title"><div><p class="eyebrow">Progress libraries</p><h2><?=h($profile['name'])?></h2></div><div class="side-title-actions"><button type="button" class="ghost sidebar-toggle" id="sidebar-toggle" aria-expanded="true" aria-controls="sidebar-body">Hide libraries</button><button type="button" class="iconbtn" onclick="showModal('library-new')" aria-label="Add progress library">+</button></div></div><div class="sidebar-body" id="sidebar-body"><div class="library-list"><?php foreach($d['libraries'] as $l):?><a class="library-item <?=$l['id']===$selectedId?'selected':''?>" <?=$l['id']===$selectedId?'aria-current="page"':''?> href="?patient=<?=h($patientId)?>&library=<?=h($l['id'])?>&view=<?=h($view)?>"><span><strong><?=h($l['name'])?></strong><small><?=h($l['type']==='Custom'?$l['custom_type']:$l['type'])?> · <?=h($l['start_date'])?></small></span><?php if(count($l['notes'])):?><span class="note-badge library-note-badge"><span aria-hidden="true">Notes</span><b aria-hidden="true"><?=count($l['notes'])?></b><span class="sr-only"><?=count($l['notes'])?> notes</span></span><?php endif?></a><?php endforeach?></div><button type="button" id="syncButton" class="sync-card" data-count="<?=sync_count($d)?>"><strong>Sync all to this device</strong><span>Private offline copy · <b><?=sync_count($d)?> photos</b></span></button><div id="syncInfo" class="muted tiny" role="status" aria-live="polite"></div></div></aside>
<section class="content"><?php if(!$selected):?><div class="empty"><h2>No progress library</h2><p>Add a library to begin.</p></div><?php else:$notes=$selected['notes'];?><?=selected_day_notes_banner($selected,$date)?><article class="library-head <?=count($notes)?'noted':''?>"><div><span class="type"><?=h($selected['type']==='Custom'?$selected['custom_type']:$selected['type'])?></span><h1><?=h($selected['name'])?></h1><p><?=h($selected['description'])?></p><small>Tracking since <?=h(date('M j, Y',strtotime($selected['start_date'])))?> · Revision <?=h($selected['revision'])?></small></div><div class="head-buttons"><?=notes_trigger('notes-library',count($notes))?><button type="button" class="ghost" onclick="showModal('library-edit')">Edit library</button><form method="post" onsubmit="return confirm('Delete this library and all its records?')"><input type="hidden" name="op" value="library_delete"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="library_id" value="<?=h($selectedId)?>"><button class="danger ghost" type="submit">Delete library</button></form></div></article>
<?=view_switch($selected,$date,$view),timeline($selected,$date,$view)?>
<?=$view==='gallery'?gallery_view($selected,$date,$csrf,$galleryOrder):day_view($selected,$date,$csrf)?>
<div class="section-title"><h2>Wound list</h2><button type="button" class="ghost" onclick="showModal('wound-new')">Add wound</button></div><div class="manage-list"><?php foreach($selected['wounds'] as $w):?><div><span><strong><?=h($w['name'])?></strong><small><?=h($w['location'])?> · <?=$w['active']?'Active':'Inactive — history retained'?></small></span><span class="row"><button type="button" class="ghost small" onclick="showModal('wound-<?=h($w['id'])?>')">Edit <?=h($w['name'])?></button><form method="post" onsubmit="return confirm('Delete this wound, its history, and photos?')"><?=hidden($csrf,$selectedId,$w['id'])?><input type="hidden" name="op" value="wound_delete"><button class="danger ghost small" type="submit">Delete <?=h($w['name'])?></button></form></span></div><?php endforeach?></div>
<?=modals($selected,$csrf,$date)?><?=photo_lightbox()?><?php endif?></section></div><?php endif?></main><?=app_footer()?><script><?=js()?></script></body></html>
<?php
function hidden(string $csrf,string $lib,string $w='',string $date=''):string{return '<input type="hidden" name="csrf" value="'.h($csrf).'"><input type="hidden" name="library_id" value="'.h($lib).'">'.($w?'<input type="hidden" name="wound_id" value="'.h($w).'">':'').($date?'<input type="hidden" name="date" value="'.h($date).'">':'');}
function app_footer():string{return '<footer><span>Clinical viewer for post op wounds, pressure injuries, and moles</span><small>Not HIPAA compliant. We do not take responsibility. Internal testing only.</small></footer>';}
function first_run_page(string $setupCsrf):void{?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><title>Set up · <?=APP_NAME?></title><style><?=css()?></style></head><body><main class="login" tabindex="-1"><section class="login-card"><div class="brandmark" aria-hidden="true">+</div><p class="eyebrow">First run</p><h1>Start with a sample patient pack</h1><p class="muted">No records or browser storage have been created yet. Create a local sample pack to explore the viewer.</p><form method="post"><input type="hidden" name="op" value="create_sample_pack"><input type="hidden" name="setup_csrf" value="<?=h($setupCsrf)?>"><button type="submit">Create sample patient pack</button></form><p class="muted tiny">This creates the local <code>storage/</code> folder and the demo sign-in account on this machine.</p></section></main><?=app_footer()?></body></html><?php exit;}
function sync_count(array $d):int{$n=0;foreach($d['libraries'] as $l)foreach($l['wounds'] as $w)foreach($w['updates'] as $u)$n+=count($u['photos']);return $n;}
function gallery_order():string{return (($_GET['gallery_order']??'')==='asc')?'asc':'desc';}
function app_url(string $library,string $date,string $view='day',?string $galleryOrder=null):string{
    $url='?patient='.rawurlencode(active_patient_id()).'&library='.rawurlencode($library).'&date='.rawurlencode($date).'&view='.rawurlencode($view);
    return $view==='gallery'?$url.'&gallery_order='.rawurlencode($galleryOrder??gallery_order()):$url;
}
function photo_count_on_date(array $l,string $ds):int{$n=0;foreach($l['wounds'] as $w)$n+=count($w['updates'][$ds]['photos']??[]);return $n;}
function notes_count_on_date(array $l,string $ds):int{
    $n=count($l['day_notes'][$ds]??[]);
    foreach($l['wounds'] as $w)$n+=count($w['day_notes'][$ds]??[]);
    return $n;
}
function date_count_badge(string $label,int $count,string $noun):string{
    if($count<=0)return '';
    $words=$count===1?$noun:$noun.'s';
    return '<span class="note-badge date-'.strtolower($label).'"><span aria-hidden="true">'.$label.'</span><b aria-hidden="true">'.$count.'</b><span class="sr-only">'.$count.' '.$words.'</span></span>';
}
function note_mark_icon():string{return '<svg class="note-mark" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 3h18v14H7l-4 4V3zm4 5v2h10V8H7zm0 4v2h7v-2H7z"/></svg>';}
function photo_dates_for_wound(array $w):array{$dates=[];foreach(($w['updates']??[]) as $date=>$update)if(!empty($update['photos']))$dates[]=$date;sort($dates);return $dates;}
function latest_relevant_date(array $l):string{
    $today=gmdate('Y-m-d');$start=$l['start_date'];$latestPhoto=null;$latestUpdate=null;
    foreach($l['wounds'] as $w){foreach(($w['updates']??[]) as $ds=>$u){
        if(!is_string($ds)||$ds<$start||$ds>$today)continue;
        if(!empty($u['photos'])&&($latestPhoto===null||$ds>$latestPhoto))$latestPhoto=$ds;
        if($latestUpdate===null||$ds>$latestUpdate)$latestUpdate=$ds;
    }}
    return $latestPhoto??$latestUpdate??($start>$today?$start:$today);
}
function photo_days(array $l,string $order='desc'):array{
    $days=[];
    foreach($l['wounds'] as $w){foreach(($w['updates']??[]) as $ds=>$u){
        $photos=$u['photos']??[];if(!$photos)continue;
        $days[$ds][]=['wound'=>$w,'notes'=>wound_notes_for($w,$ds),'photos'=>$photos];
    }}
    if($order==='asc')ksort($days);else krsort($days);
    return $days;
}
function photo_word(int $n):string{return $n.' photo'.($n===1?'':'s');}
function shot_word(int $n):string{return $n.' shot'.($n===1?'':'s');}
function pose_is_generic(?string $label):bool{
    $t=strtolower(trim((string)$label));
    return $t===''||in_array($t,['front','side','left','right','back','left side','right side','left-side','right-side','l','r'],true);
}
function photo_heading(array $p,int $i):string{
    $label=trim((string)($p['angle']??''));
    return pose_is_generic($label)?'Shot '.($i+1):$label;
}
function pencil_icon():string{return '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm17.71-10.21a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>';}
function avatar_markup(array $p,string $class='avatar'):string{
    $src='index.php?action=avatar&patient='.rawurlencode((string)$p['id']);
    return '<span class="'.$class.'"><img src="'.$src.'" alt="Portrait placeholder for '.h($p['name']).'"></span>';
}
function notes_trigger(string $id,int $count,string $kind='Notes'):string{
    if($id==='notes-library'&&$kind==='Notes')$kind='Library notes';
    $has=$count>0;
    $label=$has?$kind:'No Notes';
    $aria=$has?($count===1?'1 note':$count.' notes'):'No notes';
    if($kind==='Day notes')$aria=$has?($count===1?'1 day note':$count.' day notes'):'No day notes';
    if($kind==='Wound notes')$aria=$has?($count===1?'1 wound note':$count.' wound notes'):'No wound notes';
    if($kind==='Library notes')$aria=$has?($count===1?'1 library note':$count.' library notes'):'No library notes';
    return '<button type="button" class="notes-btn '.($has?'has-notes':'no-notes').'" onclick="showModal(\''.h($id).'\')" aria-label="'.$aria.'"><span>'.$label.'</span>'.($has?'<b aria-hidden="true">'.$count.'</b>':'').'</button>';
}
function kg_to_lb(float $kg):float{return round($kg*2.2046226218,1);}
function weight_control(float $kg):string{
    $kgLabel=number_format($kg,1,'.','').' kg';
    $lb=kg_to_lb($kg);
    return '<div class="weight-control" data-kg="'.h(number_format($kg,1,'.','')).'" data-lb="'.h(number_format($lb,1,'.','')).'"><span class="weight-value">'.$kgLabel.'</span><span class="unit-toggle" role="group" aria-label="Body weight unit"><button type="button" class="unit-btn" data-unit="kg" aria-pressed="true" aria-label="Show weight in kilograms">kg</button><button type="button" class="unit-btn" data-unit="lb" aria-pressed="false" aria-label="Show weight in pounds">lb</button></span></div>';
}
function patient_facts(array $p):string{
    $age=(int)$p['age'];
    return '<dl class="patient-meta"><div class="patient-diagnosis"><dt>Dx</dt><dd>'.h($p['diagnosis']).'</dd></div><div><dt>Account number</dt><dd>'.h($p['account_number']).'</dd></div><div><dt>Age</dt><dd>'.$age.' year'.($age===1?'':'s').'</dd></div>'.($p['height']!==''?'<div><dt>Height</dt><dd>'.h($p['height']).'</dd></div>':'').'<div><dt>Gender</dt><dd>'.h($p['gender']).'</dd></div><div><dt>Body weight</dt><dd>'.weight_control((float)$p['weight_kg']).'</dd></div></dl>';
}
function patient_facts_compact(array $p):string{
    $age=(int)$p['age'];
    return '<div class="patient-facts">'.avatar_markup($p,'avatar compact-avatar').'<span><strong>Account number</strong> '.h($p['account_number']).'</span><span><strong>Age</strong> '.$age.' year'.($age===1?'':'s').'</span>'.($p['height']!==''?'<span><strong>Height</strong> '.h($p['height']).'</span>':'').'<span><strong>Gender</strong> '.h($p['gender']).'</span><span class="patient-facts-weight"><strong>Body weight</strong> '.weight_control((float)$p['weight_kg']).'</span></div>';
}
function patient_records():array{
    global $root,$account;$records=[];$dir=$root.'/patients';
    if(!is_dir($dir))return $records;
    $ids=[];
    foreach(scandir($dir)?:[] as $id){
        if($id==='.'||$id==='..'||!safe_id($id)||!is_file("$dir/$id/record.json"))continue;
        if(is_array($account)&&!account_can_access($account,$id))continue;
        $ids[]=$id;
    }
    usort($ids,static function(string $a,string $b):int{
        if($a===DEFAULT_PATIENT_ID)return -1;
        if($b===DEFAULT_PATIENT_ID)return 1;
        return strcmp($a,$b);
    });
    foreach($ids as $id){
        $raw=@file_get_contents("$dir/$id/record.json");$record=$raw===false?null:json_decode($raw,true);
        if(!is_array($record))continue;$record['patient']=patient_profile($record);$records[]=$record;
    }
    return $records;
}
function patient_picker(array $records,string $csrf):string{
    $out='<div class="patient-picker">';
    foreach($records as $d){$p=patient_profile($d);$n=count($d['libraries']);$href='?patient='.rawurlencode($p['id']);$libs=$n.' progress librar'.($n===1?'y':'ies');$out.='<article class="patient-card" aria-labelledby="patient-name-'.h($p['id']).'"><a class="patient-open" href="'.$href.'">'.avatar_markup($p).'<span class="patient-name"><strong id="patient-name-'.h($p['id']).'">'.h($p['name']).'</strong><small>'.$libs.'</small></span></a>'.patient_facts($p).'<a class="patient-go" href="'.$href.'">Open record <span aria-hidden="true">→</span></a></article>';}
    $have=array_map(fn(array $record)=>(string)($record['patient']['id']??''),$records);
    foreach(pipeline_available_patients() as $candidate){
        if(in_array($candidate['id'],$have,true))continue;
        $out.='<article class="patient-card patient-add-card"><p class="eyebrow">Available record</p><h2>Add '.h($candidate['name']).'</h2><p class="muted">Import this patient from the local photo pipeline.</p><form method="post"><input type="hidden" name="op" value="patient_import"><input type="hidden" name="pipeline_id" value="'.h($candidate['id']).'"><input type="hidden" name="csrf" value="'.h($csrf).'"><button type="submit">Import patient record</button></form></article>';
    }
    return $out.'</div>';
}
function selected_day_notes_banner(array $l,string $date):string{
    $notes=$l['day_notes'][$date]??[];
    if(!$notes)return '';
    $o='<section class="day-notes-panel" aria-label="Day notes"><p class="eyebrow">Day note · '.h(date('M j, Y',strtotime($date))).'</p>';
    foreach($notes as $note)$o.='<p class="note-entry">'.note_mark_icon().'<span>'.h($note['text']??'').'</span></p>';
    return $o.'</section>';
}
function view_switch(array $l,string $date,string $view):string{
    $day=app_url($l['id'],$date,'day');$gal=app_url($l['id'],$date,'gallery');
    return '<nav class="view-switch" aria-label="Library views"><a href="'.$day.'"'.($view==='day'?' aria-current="page"':'').'>Day record</a><a data-gallery-tab href="'.$gal.'"'.($view==='gallery'?' aria-current="page"':'').'>Photo gallery</a></nav>';
}
function photo_gallery(array $photos,array $w,array $l,string $date,string $csrf):string{
    $n=count($photos);if($n===0)return '';
    $uid='angles-'.h($w['id']).'-'.h($date);
    $photoDates=implode(',',photo_dates_for_wound($w));
    $canBrowseDates=count(photo_dates_for_wound($w))>1;
    $o='<div class="angle-set" data-mode="cycle" data-count="'.$n.'" data-date="'.h($date).'" data-photo-dates="'.h($photoDates).'" data-wound-id="'.h($w['id']).'" data-wound-name="'.h($w['name']).'" data-wound-description="'.h($w['location']).'" data-assessment="'.h(json_encode(assessment_of($w['updates'][$date]??null),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)).'">';
    $o.='<div class="wound-photo-meta">';
    if($canBrowseDates)$o.='<div class="photo-date-navigation card-date-navigation" role="group" aria-label="Photo dates for '.h($w['name']).'"><span>Dates with photos</span><div><button type="button" class="ghost small angle-date-prev">Previous date</button><button type="button" class="ghost small angle-date-next">Next date</button></div></div>';
    else $o.='<span class="wound-photo-meta-spacer" aria-hidden="true"></span>';
    $o.=assessment_panel($w['updates'][$date]??null,$w['id'],$w['name']);
    $o.='</div>';
    $o.='<div class="angle-stage">';
    $o.=$n>1?'<button type="button" class="ghost angle-nav prev" aria-label="Previous shot of '.h($w['name']).'">Previous shot</button>':'<span class="angle-nav-spacer" aria-hidden="true"></span>';
    $o.='<div class="angle-frames" id="'.$uid.'">';
    foreach($photos as $i=>$p){
        $heading=photo_heading($p,$i);
        $caption=trim((string)($p['caption']??''));
        $clinical=strlen($caption)>50;
        $o.='<figure'.($i===0?' class="is-current"':' hidden').' data-index="'.$i.'"><img src="index.php?action=media&patient='.rawurlencode(active_patient_id()).'&id='.h($p['id']).'&v='.$l['revision'].'" alt="'.h($heading).' of '.h($w['name']).'"><figcaption><span class="photo-label"><strong>'.h($heading).'</strong>'.($caption!==''&&!$clinical?'<small>'.h($caption).'</small>':'').'</span><span class="photo-view-actions"><button type="button" class="ghost small photo-label-edit edit-only" onclick="showModal(\'photo-'.h($p['id']).'\')" aria-label="Edit name or description for '.h($heading).'">'.pencil_icon().'<span class="sr-only">Edit '.h($heading).'</span></button><button type="button" class="ghost small photo-expand" data-index="'.$i.'" aria-label="Expand '.h($heading).' of '.h($w['name']).'">Expand</button></span></figcaption>'.($clinical?'<p class="photo-note">'.h($caption).'</p>':'').'</figure>';
    }
    $o.='</div>';
    $o.=$n>1?'<button type="button" class="ghost angle-nav next" aria-label="Next shot of '.h($w['name']).'">Next shot</button>':'<span class="angle-nav-spacer" aria-hidden="true"></span>';
    $o.='</div>';
    $o.='<div class="angle-toolbar"><p class="angle-status" aria-live="polite">'.($n>1?'Shot 1 of '.$n:'Shot 1').'</p>';
    if($n>1){
        $o.='<button type="button" class="ghost small angle-expand">Show all shots</button>';
        $o.='<button type="button" class="ghost small angle-collapse" hidden>Cycle shots</button>';
    }
    $o.='</div>';
    if($n>1){
        $o.='<div class="angle-dots" role="tablist" aria-label="Shots of '.h($w['name']).'">';
        foreach($photos as $i=>$p)$o.='<button type="button" role="tab" data-index="'.$i.'" aria-label="'.h(photo_heading($p,$i)).', '.($i+1).' of '.$n.'"'.($i===0?' aria-selected="true"':'').'>'.($i+1).'</button>';
        $o.='</div>';
    }
    $o.='</div>';
    return $o.photo_manage($photos,$w,$l,$date,$csrf);
}
function photo_manage(array $photos,array $w,array $l,string $date,string $csrf):string{
    $n=count($photos);if($n===0)return '';
    $o='<details class="photo-manage edit-only"><summary>Manage photos</summary><ul>';
    foreach($photos as $i=>$p){
        $heading=photo_heading($p,$i);
        $o.='<li><span><strong>'.h($heading).'</strong>'.($p['caption']!==''?'<small>'.h($p['caption']).'</small>':'').'</span><div class="row photo-actions"><form method="post">'.hidden($csrf,$l['id'],$w['id'],$date).'<input type="hidden" name="op" value="photo_move"><input type="hidden" name="photo_id" value="'.h($p['id']).'"><button type="submit" class="ghost small" name="direction" value="left" aria-label="Move '.h($heading).' earlier" '.($i===0?'disabled':'').'>Earlier</button><button type="submit" class="ghost small" name="direction" value="right" aria-label="Move '.h($heading).' later" '.($i===$n-1?'disabled':'').'>Later</button></form><button type="button" class="ghost small" onclick="showModal(\'photo-'.h($p['id']).'\')">Edit label</button></div></li>';
    }
    return $o.'</ul></details>';
}
function assessment_fields(): array {
    return [
        'width'=>'Width',
        'depth'=>'Depth',
        'length'=>'Length',
        'periskin'=>'Periskin',
        'drainage'=>'Drainage',
        'smell'=>'Odor',
        'dressing'=>'Dressing',
        'treatment'=>'Treatment',
    ];
}
function assessment_of(?array $u): array {
    $src=is_array($u['assessment']??null)?$u['assessment']:[];
    $out=[];
    foreach(array_keys(assessment_fields()) as $k)$out[$k]=trim((string)($src[$k]??''));
    return $out;
}
function assessment_from_post(): array {
    $out=[];
    foreach(array_keys(assessment_fields()) as $k){
        $v=trim((string)($_POST['assess_'.$k]??''));
        if(strlen($v)>80)$v=substr($v,0,80);
        $out[$k]=$v;
    }
    return $out;
}
function assessment_filled(array $a): bool {
    foreach($a as $v)if($v!=='')return true;
    return false;
}
function assessment_clip(array $a,string $key,string $emptyLabel): array {
    $value=trim((string)($a[$key]??''));
    return ['label'=>$emptyLabel,'value'=>$value,'set'=>$value!==''];
}
function assessment_size_clip(array $a): array {
    $w=$a['width']??'';$d=$a['depth']??'';$l=$a['length']??'';
    $set=$w!==''||$d!==''||$l!=='';
    $part=static fn(string $v):string=>$v!==''?$v:'–';
    return ['label'=>'W×D×L','value'=>$set?$part($w).' × '.$part($d).' × '.$part($l):'','set'=>$set];
}
function assessment_panel(?array $u,string $woundId,string $woundName): string {
    $a=assessment_of($u);
    $clips=[
        assessment_size_clip($a),
        assessment_clip($a,'periskin','Periskin'),
        assessment_clip($a,'drainage','Drainage'),
        assessment_clip($a,'smell','Odor'),
        assessment_clip($a,'dressing','Dressing'),
        assessment_clip($a,'treatment','Tx'),
    ];
    $any=assessment_filled($a);
    $sr=[];
    foreach($clips as $c)$sr[]=$c['set']?$c['label'].' '.$c['value']:$c['label'].' not charted';
    $o='<button type="button" class="assess-panel'.($any?' is-set':' is-empty').'" onclick="showModal(\'update-'.h($woundId).'\')" aria-label="'.h('Assessment for '.$woundName.'. '.implode('. ',$sr)).'">';
    foreach($clips as $c){
        $o.='<span class="assess-chip'.($c['set']?' is-set':' is-empty').'"><small>'.h($c['label']).'</small>';
        if($c['set'])$o.='<b>'.h($c['value']).'</b>';
        $o.='</span>';
    }
    return $o.'</button>';
}
function assessment_form(?array $u): string {
    $a=assessment_of($u);
    $o='<fieldset class="assess-fields"><legend>Assessment</legend><div class="assess-dims">';
    foreach(['width'=>'Width','depth'=>'Depth','length'=>'Length'] as $k=>$lab)$o.='<label>'.$lab.' <span class="muted tiny">cm</span><input name="assess_'.$k.'" value="'.h($a[$k]).'" inputmode="decimal" maxlength="80" placeholder="—"></label>';
    $o.='</div>';
    foreach(['periskin'=>'Periskin','drainage'=>'Drainage','smell'=>'Odor','dressing'=>'Dressing','treatment'=>'Treatment'] as $k=>$lab){
        $ph=['periskin'=>'e.g. intact, erythema','drainage'=>'e.g. none, scant serous','smell'=>'e.g. none, faint, foul','dressing'=>'e.g. gauze, foam','treatment'=>'e.g. saline, ointment'][$k];
        $o.='<label>'.$lab.'<input name="assess_'.$k.'" value="'.h($a[$k]).'" maxlength="80" placeholder="'.$ph.'"></label>';
    }
    return $o.'</fieldset>';
}
function wound_card(array $selected,array $w,string $date,string $csrf):string{
    $u=$w['updates'][$date]??null;$photos=$u['photos']??[];$hasPhotos=count($photos)>0;$dayNotes=wound_notes_for($w,$date);$noteCount=count($dayNotes);
    $classes='wound-card '.($hasPhotos?'has-photos':'missing').($noteCount?' noted':'');
    if($u&&$hasPhotos)$state='✓ '.photo_word(count($photos));
    elseif($u)$state='○ No photos added';
    else $state='○ Not updated on this date';
    $o='<article class="'.$classes.'"><div class="wound-title"><div><p class="state">'.$state.'</p><h3>'.h($w['name']).'</h3><small>'.h($w['location']).'</small></div>'.notes_trigger('notes-'.h($w['id']),$noteCount,'Wound notes').'</div>';
    if($noteCount){
        $o.='<section class="wound-notes-panel" aria-label="Wound notes for this date">';
        foreach($dayNotes as $note)$o.='<p class="note-entry">'.note_mark_icon().'<span>'.h($note['text']??'').'</span></p>';
        $o.='</section>';
    }
    if(!$hasPhotos)$o.='<div class="wound-photo-meta">'.assessment_panel($u,$w['id'],$w['name']).'</div>';
    if($u){
        $o.=$hasPhotos?photo_gallery($photos,$w,$selected,$date,$csrf):'<div class="no-photo">No photos added</div>';
        $o.='<div class="row"><button type="button" onclick="showModal(\'photo-add-'.h($w['id']).'-'.h($date).'\')">Add photo</button><button type="button" class="ghost" onclick="showModal(\'update-'.h($w['id']).'\')">Edit update</button><form method="post" onsubmit="return confirm(\'Delete this date update and its photos?\')">'.hidden($csrf,$selected['id'],$w['id'],$date).'<input type="hidden" name="op" value="update_delete"><button class="danger ghost" type="submit">Delete update</button></form></div>';
    }else{
        $o.='<button type="button" onclick="showModal(\'update-'.h($w['id']).'\')">Add update for this date</button>';
    }
    return $o.'</article>';
}
function day_view(array $selected,string $date,string $csrf):string{
    $notesCount=count($selected['day_notes'][$date]??[]);
    $active=array_values(array_filter($selected['wounds'],fn($w)=>!empty($w['active'])));
    $updated=0;$photoTotal=0;
    foreach($active as $w){if(isset($w['updates'][$date]))$updated++;$photoTotal+=count($w['updates'][$date]['photos']??[]);}
    $o='<div class="day-head"><div><p class="eyebrow">Selected day</p><h2>'.h(date('l, F j, Y',strtotime($date))).'</h2></div>'.notes_trigger('notes-day',$notesCount,'Day notes').'</div>';
    if(!$updated)$o.='<div class="empty compact"><strong>No wound updates recorded</strong><span>This date remains intentionally blank until an update is added.</span></div>';
    elseif($photoTotal===0)$o.='<div class="empty compact"><strong>No photos recorded on this date</strong><span>Note-only updates still appear below, grayed out.</span></div>';
    $o.='<div class="wound-grid">';
    foreach($active as $w)$o.=wound_card($selected,$w,$date,$csrf);
    return $o.'</div>';
}
function gallery_view(array $selected,string $date,string $csrf,string $order='desc'):string{
    $days=photo_days($selected,$order);
    $nextOrder=$order==='asc'?'desc':'asc';
    $orderLabel=$order==='asc'?'Oldest first. Show newest first.':'Newest first. Show oldest first.';
    $orderToggle='<a class="gallery-order-toggle" href="'.app_url($selected['id'],$date,'gallery',$nextOrder).'#photo-gallery-card" aria-label="'.h($orderLabel).'" title="'.h($orderLabel).'"><span aria-hidden="true">⇅</span><span class="sr-only">'.h($orderLabel).'</span></a>';
    $o='<div id="photo-gallery-card" class="day-head" tabindex="-1"><div><p class="eyebrow">Photo gallery</p><h2>Days and wounds with pictures</h2><p class="muted">Only dates and wounds that have photos are listed.</p></div>'.$orderToggle.'</div>';
    if(!$days)return $o.'<div class="empty"><h2>No photos recorded</h2><p>Add photos from the day record view and they will appear here.</p></div>';
    if(photo_count_on_date($selected,$date)===0)$o.='<div class="empty compact"><strong>'.h(date('l, F j',strtotime($date))).' has no photos</strong><span>Showing only days that do.</span></div>';
    $openKey=isset($days[$date])?$date:array_key_first($days);
    $o.='<div class="photo-accordion">';
    foreach($days as $ds=>$entries){
        $count=0;foreach($entries as $e)$count+=count($e['photos']);
        $o.='<details class="day-acc"'.($ds===$openKey?' open':'').' id="gallery-day-'.$ds.'"><summary><span><strong>'.h(date('l, F j, Y',strtotime($ds))).'</strong><small>'.photo_word($count).' · '.count($entries).' wound'.(count($entries)===1?'':'s').'</small></span><span class="chev" aria-hidden="true"></span></summary><div class="day-panel">';
        foreach($entries as $e){
            $w=$e['wound'];$pc=count($e['photos']);
            $o.='<details class="wound-acc" open><summary><span><strong>'.h($w['name']).'</strong><small>'.h($w['location']).' · '.photo_word($pc).'</small></span><span class="chev" aria-hidden="true"></span></summary>';
            if($e['notes']){$o.='<section class="wound-notes-panel update-note" aria-label="Wound notes">';foreach($e['notes'] as $note)$o.='<p class="note-entry">'.note_mark_icon().'<span>'.h($note['text']??'').'</span></p>';$o.='</section>';}
            $o.=photo_gallery($e['photos'],$w,$selected,$ds,$csrf);
            $o.='<div class="row acc-actions"><button type="button" onclick="showModal(\'photo-add-'.h($w['id']).'-'.h($ds).'\')">Add photo</button><a class="ghost button-link" href="'.app_url($selected['id'],$ds,'day').'">Open day record</a></div></details>';
        }
        $o.='</div></details>';
    }
    return $o.'</div>';
}
function timeline(array $l,string $date,string $view='day'):string{
    $start=new DateTimeImmutable($l['start_date']);$end=new DateTimeImmutable('today');$cur=new DateTimeImmutable($date);$month=$cur->format('Y-m');
    $first=maxdate($start,new DateTimeImmutable($month.'-01'));$last=mindate($end,new DateTimeImmutable($month.'-01 last day of this month'));
    $out='<nav class="timeline" aria-label="Date timeline"><div class="month-nav"><a href="'.app_url($l['id'],$cur->modify('-1 month')->format('Y-m-d'),$view).'">Previous month</a><strong>'.$cur->format('F Y').'</strong><a href="'.app_url($l['id'],$cur->modify('+1 month')->format('Y-m-d'),$view).'">Next month</a><a href="'.app_url($l['id'],gmdate('Y-m-d'),$view).'">Today</a></div><div class="date-row">';
    for($x=$first;$x<=$last;$x=$x->modify('+1 day')){
        $ds=$x->format('Y-m-d');$photos=photo_count_on_date($l,$ds);$nn=notes_count_on_date($l,$ds);
        $label=$x->format('D j').($photos?', '.photo_word($photos):', no photos').($nn?', '.$nn.' note'.($nn===1?'':'s'):'');
        $current=$ds===$date;
        $badges=$nn?'<span class="date-chip-badges">'.date_count_badge('Notes',$nn,'note').'</span>':'';
        $out.='<a aria-label="'.h($label).'"'.($current?' aria-current="date"':'').' class="date-chip '.($photos?'has-photos':'idle').' '.($current?'current':'').'" href="'.app_url($l['id'],$ds,$view).'"><small>'.$x->format('D').'</small><b>'.$x->format('j').'</b>'.($photos?'<span class="photo-mark" aria-hidden="true">'.$photos.'</span>':'').$badges.'</a>';
    }
    return $out.'</div></nav>';
}
function maxdate(DateTimeImmutable $a,DateTimeImmutable $b):DateTimeImmutable{return $a>$b?$a:$b;}function mindate(DateTimeImmutable $a,DateTimeImmutable $b):DateTimeImmutable{return $a<$b?$a:$b;}
function dialog_start(string $id,string $title):string{return '<dialog id="'.$id.'" aria-labelledby="'.$id.'-title"><button type="button" class="close" onclick="this.closest(\'dialog\').close()" aria-label="Close">×</button><h2 id="'.$id.'-title">'.h($title).'</h2>';}
function account_modal(array $account,string $csrf,string $returnTo):string{
    $name=h($account['display_name']);
    return '<button type="button" id="account-trigger" class="account-trigger" onclick="showModal(\'account-information\')" aria-label="Open account information for '.$name.'">Welcome '.$name.'</button>'
        .dialog_start('account-information','Account information')
        .'<form method="post"><input type="hidden" name="op" value="account_save"><input type="hidden" name="csrf" value="'.h($csrf).'"><input type="hidden" name="return_to" value="'.h($returnTo).'"><label>Your name<input name="display_name" value="'.$name.'" autocomplete="name" maxlength="80" required></label><p class="muted tiny">This name appears in the top-right account control.</p><fieldset><legend>Change password</legend><p class="muted tiny">Leave all password fields blank to keep your current password.</p><label>Current password<input name="current_password" type="password" autocomplete="current-password"></label><label>New password<input name="new_password" type="password" autocomplete="new-password" minlength="8"></label><label>Confirm new password<input name="confirm_password" type="password" autocomplete="new-password" minlength="8"></label></fieldset><button type="submit">Save account</button></form></dialog>';
}
function edit_mode_bar():string{return '<div id="edit-mode-bar" class="edit-mode-bar" hidden><span><strong>Photo editing</strong><small>Show labels and management tools</small></span></div>';}
function photo_lightbox():string{return '<dialog id="photo-lightbox" class="photo-lightbox" aria-labelledby="lightbox-title"><button type="button" class="close lightbox-close" aria-label="Close expanded photo">×</button><header class="lightbox-context"><p id="lightbox-date" class="eyebrow"></p><h2 id="lightbox-title">Photo viewer</h2><p id="lightbox-wound"></p><div class="lightbox-meta"><div class="photo-date-navigation lightbox-date-navigation" hidden><span>Dates with photos</span><div><button type="button" class="ghost small lightbox-date-prev">Previous date</button><button type="button" class="ghost small lightbox-date-next">Next date</button></div></div><button type="button" class="assess-panel lightbox-assess is-empty" aria-label="Wound assessment"></button></div></header><div class="lightbox-stage"><button type="button" class="ghost lightbox-prev">Previous shot</button><figure><img alt=""><figcaption><strong></strong><small></small></figcaption></figure><button type="button" class="ghost lightbox-next">Next shot</button></div><p class="lightbox-status" aria-live="polite"></p></dialog>';}
function format_note_time(string $iso):string{
    try{$dt=new DateTimeImmutable($iso);return $dt->setTimezone(new DateTimeZone('UTC'))->format('M j, Y · H:i').' UTC';}
    catch(Throwable $e){return $iso;}
}
function note_icon(string $kind,string $label):string{
    $svgs=[
      'edit'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm17.71-10.21a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>',
      'delete'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>',
    ];
    return '<span class="note-icon-wrap"><span class="note-tip" role="tooltip">'.$label.'</span><button type="'.($kind==='delete'?'submit':'button').'" class="note-icon note-'.$kind.'" aria-label="'.$label.'"'.($kind==='delete'?' name="op" value="note_delete" onclick="return confirm(\'Delete this note?\')"':'').'>'.$svgs[$kind].'</button></span>';
}
function note_modal(string $id,string $title,array $notes,string $csrf,array $l,string $scope,string $date='',string $wid=''):string{
    $o=dialog_start($id,$title).'<div class="note-list">';
    if(!$notes)$o.='<p class="note-empty">No notes yet.</p>';
    foreach($notes as $n){
        $nid=h($n['id']);
        $when=format_note_time((string)$n['created_at']);
        $iso=h($n['created_at']);
        $fields=hidden($csrf,$l['id'],$wid,$date).'<input type="hidden" name="scope" value="'.$scope.'"><input type="hidden" name="note_id" value="'.$nid.'">';
        $o.='<article class="note-row" data-note-id="'.$nid.'">';
        $o.='<div class="note-read"><div class="note-body"><p class="note-text">'.h($n['text']).'</p><time datetime="'.$iso.'">'.$when.'</time></div>';
        $o.='<div class="note-tools">'.note_icon('edit','Edit');
        $o.='<form method="post" class="note-delete-form">'.$fields.note_icon('delete','Delete').'</form></div></div>';
        $o.='<form method="post" class="note-edit-form"><input type="hidden" name="op" value="note_edit">'.$fields.'<label class="sr-only" for="note-text-'.$nid.'">Note text</label><textarea id="note-text-'.$nid.'" name="text" required>'.h($n['text']).'</textarea><div class="row"><button type="submit">Save note</button><button type="button" class="ghost note-cancel">Cancel</button></div></form>';
        $o.='</article>';
    }
    $o.='</div><form method="post" class="note-add-form"><input type="hidden" name="op" value="note_add">'.hidden($csrf,$l['id'],$wid,$date).'<input type="hidden" name="scope" value="'.$scope.'"><label>New note<textarea name="text" required></textarea></label><button type="submit">Add note</button></form></dialog>';
    return $o;
}
function photo_add_dialog(string $csrf,array $l,array $w,string $date):string{return dialog_start('photo-add-'.h($w['id']).'-'.h($date),'Add photo to '.$w['name'].' · '.date('M j, Y',strtotime($date))).'<form method="post" enctype="multipart/form-data">'.hidden($csrf,$l['id'],$w['id'],$date).'<label>Shot name / angle (optional)<input name="angle" placeholder="For example: Shot 3 or Side"></label><label>Description (optional)<input name="caption" placeholder="For example: Seeded reference images"></label><label>JPEG, PNG, or WebP (max 15 MB)<input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label><div class="row"><button type="submit" name="op" value="upload">Upload image</button><button class="ghost" type="submit" name="op" value="placeholder">Create placeholder photo</button></div></form></dialog>';}
function photo_edit_dialog(string $csrf,array $l,array $w,string $date,array $p):string{return dialog_start('photo-'.h($p['id']),'Edit shot').'<form method="post">'.hidden($csrf,$l['id'],$w['id'],$date).'<input type="hidden" name="photo_id" value="'.h($p['id']).'"><label>Shot name / angle (optional)<input name="angle" value="'.h($p['angle']).'" placeholder="For example: Shot 3 or Side"></label><label>Description (optional)<input name="caption" value="'.h($p['caption']).'" placeholder="For example: Seeded reference images"></label><div class="row"><button type="submit" name="op" value="photo_edit">Save shot</button><button class="danger ghost" type="submit" name="op" value="photo_delete" onclick="return confirm(\'Delete this photo?\')">Delete photo</button></div></form></dialog>';}
function modals(array $l,string $csrf,string $date):string{$o=dialog_start('library-new','Add progress library').library_form($csrf).'</dialog>'.dialog_start('library-edit','Edit progress library').library_form($csrf,$l).'</dialog>';$o.=note_modal('notes-library','Library notes',$l['notes'],$csrf,$l,'library').note_modal('notes-day','Day notes',$l['day_notes'][$date]??[],$csrf,$l,'day',$date);$o.=dialog_start('wound-new','Add wound').wound_form($csrf,$l).'</dialog>';foreach($l['wounds'] as $w){$o.=dialog_start('wound-'.h($w['id']),'Edit '.$w['name']).wound_form($csrf,$l,$w).'</dialog>'.note_modal('notes-'.$w['id'],$w['name'].' notes · '.date('M j, Y',strtotime($date)),wound_notes_for($w,$date),$csrf,$l,'wound',$date,$w['id']);$addDates=[];if(!empty($w['active'])){$u=$w['updates'][$date]??null;$o.=dialog_start('update-'.h($w['id']),($u?'Edit':'Add').' update for '.$w['name']).'<form method="post">'.hidden($csrf,$l['id'],$w['id'],$date).'<input type="hidden" name="op" value="update_save">'.assessment_form($u).'<label>Wound note<textarea name="note">'.h(update_note_for($w,$date)).'</textarea></label><button type="submit">Save update</button></form></dialog>';if($u)$addDates[$date]=true;}foreach(($w['updates']??[]) as $ds=>$upd){foreach(($upd['photos']??[]) as $p)$o.=photo_edit_dialog($csrf,$l,$w,$ds,$p);if(!empty($upd['photos']))$addDates[$ds]=true;}foreach($addDates as $ds=>$_)$o.=photo_add_dialog($csrf,$l,$w,$ds);}return $o;}
function library_form(string $csrf,?array $l=null):string{return '<form method="post"><input type="hidden" name="op" value="library_save"><input type="hidden" name="csrf" value="'.h($csrf).'"><input type="hidden" name="library_id" value="'.h($l['id']??'').'"><label>Name<input name="name" value="'.h($l['name']??'').'" required></label><label>Type<select name="type" required>'.implode('',array_map(fn($x)=>'<option '.(($l['type']??'')===$x?'selected':'').'>'.$x.'</option>',['Pressure Injury','Mole Monitoring','Postoperative Wound','Custom'])).'</select></label><label>Custom type (required when Custom)<input name="custom_type" value="'.h($l['custom_type']??'').'"></label><label>Starting date<input type="date" name="start_date" max="'.gmdate('Y-m-d').'" value="'.h($l['start_date']??gmdate('Y-m-d')).'" required></label><label>Description<textarea name="description">'.h($l['description']??'').'</textarea></label><button type="submit">Save library</button></form>';}
function wound_form(string $csrf,array $l,?array $w=null):string{return '<form method="post">'.hidden($csrf,$l['id']).'<input type="hidden" name="op" value="wound_save"><input type="hidden" name="wound_id" value="'.h($w['id']??'').'"><label>Name<input name="name" value="'.h($w['name']??'').'" required></label><label>Location / description<input name="location" value="'.h($w['location']??'').'"></label><label class="check"><input type="checkbox" name="active" '.(($w['active']??true)?'checked':'').'> Active (inactive history remains retained)</label><button type="submit">Save wound</button></form>';}
function css():string{return <<<'CSS'
:root{--ink:#163238;--muted:#4d6166;--brand:#0f5c69;--brand2:#164e63;--soft:#eef5f3;--line:#d7e3e0;--control:#5e7370;--focus:#0f5c69;--danger:#a12a38;--paper:#fff;--shadow:0 12px 30px rgba(22,50,56,.08)}*{box-sizing:border-box}html{background:#f4f7f6;color:var(--ink);font:15px/1.5 ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;scroll-padding-top:80px}body{margin:0;min-height:100vh}.skip-link{position:absolute;left:12px;top:-48px;z-index:100;background:#fff;color:var(--ink);padding:.55rem .9rem;border-radius:8px;font-weight:800;box-shadow:var(--shadow)}.skip-link:focus,.skip-link:focus-visible{top:12px}a{color:var(--brand);text-decoration:none}button,input,select,textarea{font:inherit}button{border:0;border-radius:9px;background:var(--brand);color:white;font-weight:750;padding:.68rem 1rem;cursor:pointer;min-height:32px}button:hover{filter:brightness(.94)}button:disabled{opacity:.35;cursor:not-allowed}button:focus-visible,a:focus-visible,input:focus-visible,textarea:focus-visible,select:focus-visible{outline:3px solid var(--focus);outline-offset:2px}.ghost{background:white;color:var(--brand);border:1px solid var(--line)}.danger{color:var(--danger)}.small{padding:.38rem .65rem;font-size:.83rem}.topbar{height:68px;padding:0 max(24px,calc((100vw - 1500px)/2));display:flex;align-items:center;justify-content:space-between;background:#fff;border-bottom:1px solid var(--line);position:sticky;top:0;z-index:10}.wordmark{display:flex;gap:11px;align-items:center;color:var(--ink);font-size:1.05rem;font-weight:800}.wordmark span,.brandmark{display:grid;place-items:center;background:var(--brand2);color:#fff;font-size:1.5rem;border-radius:10px;width:36px;height:36px}.top-actions,.row{display:flex;gap:.5rem;align-items:center;flex-wrap:wrap}.top-actions form,.row form,.head-buttons form,.photo-actions form{margin:0}.status{border-radius:99px;background:#e7f6ed;color:#17633a;padding:.35rem .7rem;font-size:.8rem;font-weight:750}.status.offline{background:#fff2dc;color:#8b4a0c}.app{max-width:1500px;margin:auto;padding:28px}.pagehead{display:flex;justify-content:space-between;margin:20px 0 26px}.pagehead h1,.library-head h1{font-size:2rem;margin:.15rem 0}.eyebrow{margin:0;color:var(--brand);font-size:.72rem;font-weight:850;letter-spacing:.11em;text-transform:uppercase}.muted{color:var(--muted)}.tiny{font-size:.78rem}.patient-card{max-width:720px;background:#fff;border:1px solid var(--line);box-shadow:var(--shadow);border-radius:16px;padding:20px 22px;display:grid;gap:14px;color:var(--ink)}.patient-open{display:flex;gap:16px;align-items:center;color:var(--ink)}.patient-name{display:flex;flex-direction:column;flex:1;min-width:0}.patient-name strong{font-size:1.2rem}.patient-go{justify-self:start;font-weight:800}.patient-meta{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px 18px;margin:0;padding:14px 0 4px;border-top:1px solid var(--line)}.patient-meta>div{min-width:0}.patient-meta dt{margin:0;color:var(--muted);font-size:.72rem;font-weight:850;letter-spacing:.08em;text-transform:uppercase}.patient-meta dd{margin:.2rem 0 0;font-weight:750;font-size:1.05rem;color:var(--ink)}.patient-facts{display:flex;flex-wrap:wrap;gap:8px 18px;align-items:center;margin:-8px 0 18px;padding:12px 14px;background:#fff;border:1px solid var(--line);border-radius:12px;color:var(--ink)}.patient-facts>span{display:flex;align-items:baseline;gap:.45rem;flex-wrap:wrap}.patient-facts strong{color:var(--muted);font-size:.72rem;letter-spacing:.08em;text-transform:uppercase}.weight-control{display:flex;align-items:center;gap:10px;flex-wrap:wrap}.weight-value{font-weight:800;font-variant-numeric:tabular-nums}.unit-toggle{display:inline-flex;border:1px solid var(--line);border-radius:999px;overflow:hidden;background:#eef4f2}.unit-btn{min-height:32px;padding:.28rem .7rem;border:0;border-radius:0;background:transparent;color:var(--muted);font-size:.8rem;font-weight:800;box-shadow:none}.unit-btn:hover{filter:none;background:#e0ece8}.unit-btn[aria-pressed="true"]{background:var(--brand);color:#fff}.unit-btn[aria-pressed="true"]:hover{filter:brightness(.94);background:var(--brand)}.library-item small,.manage-list small,.patient-name small{display:block;color:var(--muted);margin-top:3px;overflow-wrap:anywhere}.avatar{width:88px;height:110px;display:block;border-radius:12px;background:#07090b;overflow:hidden;flex:none}.avatar img{display:block;width:100%;height:100%;object-fit:cover}.compact-avatar{width:44px;height:54px;border-radius:9px}.patient-facts{align-items:center}.crumb{display:flex;gap:9px;margin:0 0 22px}.layout{display:grid;grid-template-columns:290px minmax(0,1fr);gap:28px}.sidebar{align-self:start;position:sticky;top:136px}.side-title{display:flex;justify-content:space-between;align-items:start;margin-bottom:14px}.side-title h2{margin:.25rem 0}.iconbtn{border-radius:50%;font-size:1.4rem;padding:0;width:38px;height:38px}.library-list{display:flex;flex-direction:column;gap:8px}.library-item{display:flex;justify-content:space-between;gap:6px;color:var(--ink);padding:13px;border:1px solid transparent;border-radius:11px}.library-item:hover{background:#fff}.library-item.selected{background:#fff;border-color:#b5d1cb;box-shadow:0 5px 18px rgba(22,50,56,.06)}.library-item em{align-self:start;background:#dff2ec;color:#17644f;border-radius:99px;font-size:.66rem;font-style:normal;padding:3px 6px}.sync-card{width:100%;text-align:left;margin-top:18px;padding:14px;background:#e5f0f4;color:var(--brand2);border:1px solid #bad1da}.sync-card span{display:block;font-weight:400;font-size:.78rem;margin-top:4px}.content{min-width:0}.library-head{background:#fff;border:1px solid var(--line);border-radius:16px;padding:22px;display:flex;justify-content:space-between;gap:20px;box-shadow:var(--shadow)}.noted{border-left:5px solid #bb7c20}.library-head p{color:var(--muted);margin:.4rem 0}.type{display:inline-block;background:#e6f1ef;color:#225f55;border-radius:99px;padding:4px 9px;font-size:.7rem;font-weight:800;text-transform:uppercase}.head-buttons{display:flex;gap:7px;align-items:start}.timeline{margin:22px 0;background:#fff;border:1px solid var(--line);border-radius:14px;overflow:hidden}.month-nav{padding:12px 15px;border-bottom:1px solid var(--line);display:grid;grid-template-columns:auto 1fr auto auto;gap:18px;align-items:center}.month-nav strong{text-align:center}.date-row{display:flex;align-items:flex-start;overflow-x:auto;gap:7px;row-gap:42px;padding:13px;scrollbar-width:thin}.date-chip{min-width:52px;min-height:48px;align-self:flex-start;text-align:center;border:1px solid var(--line);border-radius:9px;padding:7px 5px;color:var(--muted);background:#f3f5f4}.date-chip small,.date-chip b{display:block}.date-chip b{font-size:1.05rem}.date-chip.idle{background:#ecefed;color:#7a8b8e;border-color:#d5dddb}.date-chip.has-photos{background:#d8efe8;color:#0f4f44;border-color:#8fc4b8}.date-chip.current{outline:3px solid var(--brand);outline-offset:1px}.date-chip .photo-mark{position:absolute;top:3px;right:3px;min-width:1.15rem;height:1.15rem;padding:0 4px;border-radius:999px;background:#0f5c69;color:#fff;font-size:.58rem;font-weight:800;line-height:1.15rem}.date-chip em{font-size:.6rem;font-style:normal;font-weight:800;display:block}.day-head,.section-title{display:flex;align-items:center;justify-content:space-between;margin:25px 0 12px}.day-head h2,.section-title h2{margin:.15rem 0}.empty{display:grid;place-items:center;text-align:center;min-height:250px;background:#fff;border:1px dashed #aec3bf;border-radius:14px}.empty.compact{min-height:0;display:flex;gap:8px;align-items:center;justify-content:start;padding:14px 17px;margin-bottom:12px;color:var(--muted)}.wound-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px}.wound-card{background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px;min-width:0}.wound-card.missing{background:#e6eaea;color:#5a6b6e;border-style:dashed;border-color:#c5d0ce}.wound-card.has-photos{grid-column:1/-1;border-color:#9ec9be}.wound-grid:has(.has-photos) .wound-card.missing{grid-column:1/-1}.wound-title{display:flex;justify-content:space-between;align-items:start;gap:12px}.wound-title h3{margin:.15rem 0}.state{font-size:.72rem;text-transform:uppercase;font-weight:850;letter-spacing:.06em;color:#20705e;margin:0}.missing .state{color:#59696c}.no-photo{background:#f3f5f4;border:1px dashed #b8c5c3;border-radius:9px;padding:22px;text-align:center;color:var(--muted);margin:12px 0}.angle-set{margin:14px 0}.angle-toolbar{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin:10px 0 0}.angle-status{margin:0;font-weight:800;color:#1a4f4a}.angle-stage{display:grid;grid-template-columns:auto minmax(0,1fr) auto;gap:10px;align-items:center}.angle-frames{min-width:0}.angle-frames figure{margin:0;border:1px solid var(--line);border-radius:12px;overflow:hidden;background:#fff}.angle-set[data-mode="column"] .angle-frames{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px}.angle-set[data-mode="column"] .angle-stage{grid-template-columns:minmax(0,1fr)}.angle-frames img{display:block;width:100%;height:min(38vh,320px);object-fit:contain;background:#080a0c;cursor:zoom-in}.angle-set[data-mode="column"] .angle-frames img{height:160px}.angle-frames figcaption{padding:10px 12px;display:flex;align-items:flex-start;justify-content:space-between;gap:8px}.angle-frames figcaption span{min-width:0}.angle-frames figcaption small{display:block;color:var(--muted)}.photo-expand{flex:none}.angle-nav{min-width:44px;min-height:44px;padding:.55rem .7rem}.angle-dots{display:flex;flex-wrap:wrap;gap:6px;justify-content:center;margin-top:10px}.angle-dots[hidden]{display:none}.angle-dots button{background:#eef3f2;color:#35555a;border:1px solid var(--line);font-weight:750;padding:.4rem .7rem;min-height:36px}.angle-dots button[aria-selected="true"]{background:var(--brand);color:#fff;border-color:var(--brand)}.photo-manage{margin:8px 0 4px;border:1px solid var(--line);border-radius:10px;background:#f8fbfa}.photo-manage summary{cursor:pointer;padding:10px 12px;font-weight:750;color:var(--muted)}.photo-manage summary:focus-visible{outline:3px solid var(--focus);outline-offset:2px}.photo-manage ul{list-style:none;margin:0;padding:0 12px 12px}.photo-manage li{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;padding:8px 0;border-top:1px solid var(--line)}.photo-manage small{display:block;font-weight:500}.photo-actions{display:flex;justify-content:space-between;flex-wrap:wrap;gap:6px}.photo-actions button{padding:.45rem .55rem;font-size:.78rem;min-width:32px;min-height:32px}.manage-list{background:#fff;border:1px solid var(--line);border-radius:13px}.manage-list>div{padding:13px 15px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--line)}.manage-list>div:last-child{border-bottom:0}.toast,.alert{padding:12px 16px;border-radius:9px}.toast{position:fixed;right:25px;top:80px;background:#173f43;color:#fff;z-index:20;box-shadow:var(--shadow)}.alert{background:#fff0f1;color:#8a2430;border:1px solid #e8bbc0;margin:13px 0}.login{min-height:calc(100vh - 56px);display:grid;place-items:center;padding:24px;background:radial-gradient(circle at 20% 10%,#dcedea 0,transparent 35%),radial-gradient(circle at 90% 70%,#dfecef 0,transparent 33%)}.login-card{width:min(440px,100%);padding:36px;background:#fff;border:1px solid var(--line);border-radius:20px;box-shadow:0 24px 70px rgba(25,61,65,.13)}.login-card h1{margin:.4rem 0;font-size:2rem}.login-card form,dialog form{display:grid;gap:13px}.demo{display:flex;flex-direction:column;align-items:stretch;gap:4px;width:100%;text-align:left;background:#eff5f4;color:var(--ink);font-weight:400;padding:12px;border-radius:9px;margin-top:18px;min-height:0}.demo strong{display:block;padding:2px 6px 2px}.demo-row{display:flex;flex-direction:column;align-items:flex-start;gap:1px;width:100%;background:transparent;color:inherit;font-weight:400;text-align:left;padding:7px 8px;border-radius:7px;min-height:0}.demo-row:hover{filter:none;background:#e4eeec}.demo-cred{white-space:nowrap}.demo-row code{font:inherit}.demo-row small{display:block;color:var(--muted);font-size:.8rem;font-weight:500;padding:0}.brandmark{margin-bottom:18px}label{display:grid;gap:5px;font-weight:700;color:#29484e}input,select,textarea{width:100%;border:1px solid var(--control);border-radius:8px;padding:.68rem;background:#fff;color:var(--ink)}textarea{min-height:82px;resize:vertical}.check{display:flex;align-items:center}.check input{width:auto}dialog{width:min(570px,calc(100% - 28px));max-height:88vh;overflow:auto;border:1px solid var(--line);border-radius:16px;padding:27px;color:var(--ink);box-shadow:0 30px 90px rgba(0,0,0,.25)}dialog::backdrop{background:rgba(10,29,31,.55)}dialog h2{margin-top:0}.close{float:right;background:transparent;color:var(--muted);font-size:1.4rem;padding:.2rem}.photo-lightbox{width:100vw;max-width:100vw;height:100vh;max-height:100vh;margin:0;border:0;border-radius:0;padding:16px 20px 20px;background:#11181a;color:#e8f0ee;box-shadow:none;overflow:hidden;flex-direction:column}.photo-lightbox[open]{display:flex}.photo-lightbox::backdrop{background:#0a1012}.photo-lightbox h2{margin:4px 48px 10px 0;font-size:1.15rem;color:#e8f0ee}.photo-lightbox .close{float:none;position:absolute;top:12px;right:16px;z-index:2;color:#c5d4d1}.lightbox-stage{display:flex;align-items:stretch;gap:12px;flex:1;min-height:0;position:relative}.photo-lightbox figure{margin:0;flex:1;min-width:0;min-height:0;height:auto;display:grid;grid-template-rows:minmax(0,1fr) auto}.photo-lightbox img{width:100%;height:100%;max-width:100%;max-height:100%;min-width:0;min-height:0;object-fit:contain;object-position:center;background:transparent;border-radius:10px}.photo-lightbox figcaption{width:100%;padding:10px 4px 0;display:flex;flex-direction:column;gap:2px;text-align:center;align-items:center}.photo-lightbox figcaption small{color:#9eb0ad}.lightbox-status{flex:none;margin:8px 0 0;font-weight:750;color:#b7c9c5;text-align:center}.lightbox-prev,.lightbox-next{position:absolute;top:42%;z-index:1;flex:none;min-width:44px;min-height:44px;background:#1c2a2c;color:#e8f0ee;border-color:#314244}.lightbox-prev{left:0}.lightbox-next{right:0}.note-list{display:flex;flex-direction:column;gap:0;margin-bottom:18px;border:1px solid var(--line);border-radius:12px;overflow:visible}.note-empty{margin:0;padding:14px 12px;color:var(--muted);text-align:center}.note-row{padding:8px 10px;border-bottom:1px solid var(--line);background:#fff}.note-row:last-child{border-bottom:0}.note-read{display:flex;align-items:flex-start;gap:8px}.note-body{flex:1;min-width:0}.note-text{margin:0;font-weight:600;line-height:1.35}.note-body time{display:block;margin-top:2px;color:var(--muted);font-size:.75rem}.note-tools{display:flex;align-items:center;gap:2px;flex:none}.note-delete-form,.dialog .note-delete-form{display:flex;margin:0;padding:0;gap:0}.note-icon-wrap{display:inline-flex;align-items:center;gap:4px}.note-icon{width:36px;height:36px;min-height:36px;padding:0;display:grid;place-items:center;background:transparent;color:var(--brand);border-radius:8px;box-shadow:none}.note-icon:hover{filter:none;background:#eef5f3}.note-icon.note-delete{color:var(--danger)}.note-icon.note-delete:hover{background:#fdecee}.note-icon svg{width:18px;height:18px;display:block}.note-tip{display:none;background:#173f43;color:#fff;font-size:.72rem;font-weight:800;line-height:1;padding:5px 8px;border-radius:6px;white-space:nowrap}.note-icon-wrap.is-open .note-tip{display:inline-block}.note-icon-wrap.is-open .note-tip:has(+ .note-delete){background:#a12a38}.note-edit-form{display:none;gap:8px;margin-top:8px}.note-row.is-editing .note-edit-form{display:grid}.note-row.is-editing .note-read{display:none}.note-edit-form textarea{min-height:72px}.sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}footer{text-align:center;color:var(--muted);font-size:.75rem;padding:20px}
.note-badge{display:inline-flex;align-items:center;gap:.12rem;border:1px solid #bcded4;border-radius:999px;background:#e5f5ef;color:#17644f;font-size:.56rem;font-style:normal;font-weight:800;line-height:1;padding:0 2px 0 5px;white-space:nowrap}.note-badge b{display:grid;place-items:center;min-width:1.35rem;height:1.35rem;border-radius:50%;background:#fff;color:#17644f;font-size:.56rem;line-height:1;transform:scale(.75);}.library-note-badge{align-self:center;flex:none}.notes-btn{display:inline-flex;align-items:center;gap:8px;flex:none;min-height:40px;padding:.48rem .72rem .48rem .95rem;border-radius:999px;font-weight:850;letter-spacing:.04em;line-height:1;box-shadow:0 6px 16px rgba(22,50,56,.08)}.notes-btn span{white-space:nowrap}.notes-btn.has-notes{background:#7a4314;color:#fff6e8;border:2px solid #7a4314}.notes-btn.has-notes:hover{filter:brightness(1.06)}.notes-btn.has-notes b{display:grid;place-items:center;min-width:1.55rem;height:1.55rem;border-radius:50%;background:#fff6e8;color:#7a4314;font-size:.82rem;line-height:1}.notes-btn.no-notes{background:#f4ead6;color:#5c3a0e;border:2px solid #b07a2c;padding-right:.95rem}.notes-btn.no-notes:hover{filter:brightness(.97);background:#efe1c4}.wound-card.missing .notes-btn{opacity:1}.wound-title small{display:block;color:var(--muted);margin-top:3px}.wound-notes-panel{margin:10px 0 12px;padding:12px 14px;background:#fff6e8;border:1px solid #d7b07a;border-left:5px solid #7a4314;border-radius:12px}.wound-notes-panel p{margin:0;font-weight:650;line-height:1.45;color:var(--ink)}.wound-notes-panel p+p{margin-top:8px}.day-notes-panel{margin:0 0 16px;padding:14px 16px;background:#fff6e8;border:1px solid #d7b07a;border-left:5px solid #7a4314;border-radius:12px}.day-notes-panel .eyebrow{margin:0 0 8px}.day-notes-panel p{margin:0;font-weight:650;line-height:1.45;color:var(--ink)}.day-notes-panel p+p{margin-top:10px}.photo-note{margin:10px 12px 12px;padding:10px 12px;background:#fff6e8;border-left:4px solid #7a4314;border-radius:8px;font-weight:650;color:var(--ink);font-size:.95rem;line-height:1.4}.date-row{padding-bottom:48px}.date-chip{position:relative}.date-chip small,.date-chip>b{display:block}.date-chip>b{font-size:1.05rem}.date-chip-badges{position:absolute;z-index:1;left:50%;top:calc(100% + 5px);transform:translateX(-50%);display:flex;flex-direction:column;align-items:center;gap:4px}.date-chip-badges .note-badge{box-shadow:0 1px 2px rgba(22,50,56,.12)}
.view-switch{display:flex;gap:4px;width:fit-content;max-width:100%;margin:18px 0 0;padding:4px;background:#fff;border:1px solid var(--line);border-radius:12px}.view-switch a{padding:.5rem .95rem;border-radius:8px;color:var(--muted);font-weight:750}.view-switch a:hover{color:var(--ink)}.view-switch a[aria-current="page"]{background:var(--brand);color:#fff}.view-switch a[aria-current="page"]:hover{color:#fff}.button-link{display:inline-flex;align-items:center;justify-content:center;border:1px solid var(--line);border-radius:9px;background:#fff;color:var(--brand);font-weight:750;padding:.68rem 1rem;min-height:32px}.photo-accordion{display:grid;gap:10px}.photo-accordion details{background:#fff;border:1px solid var(--line);border-radius:14px}.photo-accordion summary{cursor:pointer;list-style:none;display:flex;justify-content:space-between;align-items:center;gap:12px;padding:16px 18px;scroll-margin-top:140px}.photo-accordion summary:focus-visible{outline:3px solid var(--focus);outline-offset:2px}.photo-accordion summary::-webkit-details-marker{display:none}.photo-accordion summary small{display:block;font-weight:600;color:var(--muted);margin-top:2px}.photo-accordion .chev{flex:none;color:var(--brand);font-size:.85rem;line-height:1;width:auto;height:auto;border:0;transform:none}.photo-accordion .chev::before{content:'▸'}.photo-accordion details[open]>summary .chev{transform:none}.photo-accordion details[open]>summary .chev::before{content:'▾'}.photo-accordion .day-panel{padding:0 14px 16px}.wound-acc{border:1px solid var(--line);border-radius:10px;margin-top:10px;background:#f8fbfa}.wound-acc summary{padding:12px 14px}.wound-acc .update-note{margin:0 14px 8px}.wound-acc .angle-set{margin:8px 14px 12px}.acc-actions{margin:0 14px 14px}
@media(max-width:980px){.app{padding:20px}.layout{grid-template-columns:240px minmax(0,1fr);gap:18px}.wound-grid{grid-template-columns:1fr}.library-head{display:block}.head-buttons{margin-top:16px;flex-wrap:wrap}.sidebar{top:136px}}@media(max-width:700px){.topbar{height:auto;min-height:68px;padding:10px 14px;flex-wrap:wrap;gap:8px}.wordmark{font-size:.9rem;max-width:calc(100% - 8px)}.status{font-size:.72rem;padding:.3rem .55rem}.app{padding:14px}.layout{display:block}.sidebar{position:static}.library-list{flex-direction:row;overflow-x:auto}.library-item{min-width:230px}.sync-card{margin-bottom:17px}.library-head{padding:17px}.month-nav{grid-template-columns:1fr 1fr 1fr}.month-nav strong{order:-1;grid-column:1/-1}.day-head{align-items:end;flex-wrap:wrap;gap:8px}.wound-card{padding:14px}.manage-list>div{align-items:start;flex-wrap:wrap;gap:8px}.toast{left:14px;right:14px}.top-actions{width:100%;justify-content:flex-end}.login-card{padding:25px}.patient-meta{grid-template-columns:1fr}.view-switch{width:100%;position:static;top:auto}.angle-stage{grid-template-columns:1fr 1fr}.angle-frames{grid-column:1/-1;order:-1}.angle-nav{width:100%}.angle-frames img{height:min(40vh,280px)}.photo-lightbox{padding:12px}}@media(min-width:701px) and (max-width:1180px){.app{padding-left:20px;padding-right:20px}.layout{grid-template-columns:minmax(220px,245px) minmax(0,1fr);gap:18px}.library-head{display:block}.library-head h1{font-size:1.65rem}.head-buttons{margin-top:14px;flex-wrap:wrap}.wound-grid{grid-template-columns:1fr}.month-nav{gap:10px}.date-row{flex-wrap:wrap;overflow:visible}.date-chip{min-width:52px}.library-item small{overflow-wrap:anywhere}}
@media(prefers-reduced-motion:reduce){*,*::before,*::after{animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important;scroll-behavior:auto!important}}
.edit-mode{display:inline-flex;align-items:center;gap:.42rem;background:#fff;color:var(--brand);border:1px solid var(--line);padding:.38rem .65rem}.edit-mode svg{width:1rem;height:1rem;fill:currentColor}.edit-mode small{font-size:.7rem;font-weight:850;color:var(--muted)}.edit-mode[aria-pressed="true"]{background:#e5f3ef;border-color:#74aea3;color:#15574f}.edit-mode[aria-pressed="true"] small{color:inherit}.edit-only{display:none!important}body.is-editing .edit-only{display:inline-flex!important}.photo-manage.edit-only{display:none!important}body.is-editing .photo-manage.edit-only{display:block!important}.photo-view-actions{display:flex;gap:6px;align-items:center;flex:none}.photo-label-edit{width:32px;min-width:32px;min-height:32px;padding:.38rem}.photo-label-edit svg{width:15px;height:15px;display:block}
.photo-accordion .chev{display:grid;place-items:center;width:44px;height:44px;border:1px solid var(--line);border-radius:9px;background:#edf5f2;font-size:1.65rem;line-height:1;color:var(--brand)}.photo-accordion .chev::before{content:'›'}.photo-accordion details[open]>summary .chev::before{content:'⌄'}.next-photo-control{display:flex;align-items:center;gap:6px}.angle-advance{min-width:48px;min-height:48px;padding:0;font-size:2rem;line-height:1}.next-options{position:relative}.next-options summary{display:grid;place-items:center;min-width:38px;min-height:38px;cursor:pointer;list-style:none;border:1px solid var(--line);border-radius:8px;background:#fff;color:var(--brand);font-size:0}.next-options summary::-webkit-details-marker{display:none}.next-options summary::before{content:'⌄';font-size:1.3rem;line-height:1}.next-options summary:focus-visible{outline:3px solid var(--focus);outline-offset:2px}.next-options>div{position:absolute;right:0;z-index:4;width:max-content;min-width:190px;margin-top:6px;padding:6px;background:#fff;border:1px solid var(--line);border-radius:10px;box-shadow:var(--shadow)}.next-options button{display:flex;width:100%;justify-content:space-between;gap:12px;background:transparent;color:var(--ink);padding:.55rem .65rem;text-align:left}.next-options button:hover{background:#edf5f2;filter:none}.next-options button[aria-pressed="true"]{color:var(--brand);font-weight:850}.next-options small{color:var(--muted);font-size:.68rem}.next-options button[aria-pressed="true"] small{color:inherit}
.patient-meta .patient-diagnosis{grid-column:1/-1}.patient-diagnosis dd{max-width:54rem}#photo-gallery-card{scroll-margin-top:88px}.gallery-order-toggle{display:grid;place-items:center;flex:none;width:38px;height:38px;min-height:38px;padding:0;border:1px solid var(--line);border-radius:9px;background:#fff;color:var(--brand);font-size:1.35rem;font-weight:850;line-height:1}.gallery-order-toggle:hover{background:#edf5f2;color:var(--brand)}.note-entry{display:flex;align-items:flex-start;gap:8px}.note-mark{flex:none;width:16px;height:16px;margin-top:3px;color:#7a4314}
.patient-picker{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.patient-picker .patient-card{width:100%;max-width:none}
.iconbtn{width:28px;height:28px;min-height:28px;padding:0;font-size:1.05rem;line-height:1}
footer{line-height:1.45}footer span{display:block;color:var(--ink);font-weight:750}footer small{display:block;margin-top:2px;font-size:.72rem}
footer .footer-status{display:inline-flex;align-items:center;margin-top:8px}
footer .footer-status{cursor:pointer;border:0}footer .footer-status:hover{filter:brightness(.96)}.network-info{max-width:420px}.network-info p{color:var(--muted);line-height:1.55}.network-info strong{color:var(--ink)}
.sync-card-frame{position:relative;margin-top:18px}.sync-card-frame .sync-card{margin-top:0}.sync-clear{position:absolute;top:7px;right:7px;z-index:1;width:30px;min-height:30px;height:30px;padding:0;border:0;border-radius:50%;background:transparent;color:var(--brand2);font-size:1.3rem;font-weight:500;line-height:1}.sync-clear:hover{background:#d5e6e9;filter:none}
.account-trigger{min-height:0;padding:.25rem 0;border:0;border-radius:0;background:transparent;color:var(--muted);font-size:.86rem;font-weight:700;box-shadow:none}.account-trigger:hover{background:transparent;color:var(--ink);filter:none;text-decoration:underline;text-decoration-thickness:1px;text-underline-offset:3px}
.top-actions #editMode{display:none}.edit-mode-bar{position:sticky;top:68px;z-index:9;display:flex;align-items:center;justify-content:space-between;gap:14px;margin:0 0 22px;padding:9px 12px;border:1px solid var(--line);border-radius:11px;background:#fff;box-shadow:0 8px 18px rgba(22,50,56,.08)}.edit-mode-bar>span{display:flex;align-items:baseline;gap:8px;min-width:0}.edit-mode-bar strong{font-size:.78rem;letter-spacing:.06em;text-transform:uppercase}.edit-mode-bar small{color:var(--muted);font-size:.75rem}.edit-mode-bar #editMode{display:inline-flex;margin-left:auto}
.angle-nav-spacer{display:block;min-width:44px}.angle-nav{font-weight:850}.wound-photo-meta{display:flex;align-items:flex-start;justify-content:space-between;gap:8px;flex-wrap:wrap;margin:0 0 8px}.wound-photo-meta-spacer{flex:1;min-width:0}.photo-date-navigation{display:flex;align-items:center;gap:8px;padding:5px 6px 5px 10px;border:1px solid var(--line);border-radius:10px;background:#f4f8f7;color:var(--muted)}.card-date-navigation{width:fit-content;margin:0}.assess-panel{display:flex;flex-wrap:wrap;align-items:center;gap:6px 9px;width:fit-content;max-width:min(100%,34rem);margin-left:auto;padding:5px 8px;border:1px solid var(--line);border-radius:10px;background:#f4f8f7;color:var(--muted);min-height:0;font-weight:500;text-align:left;box-shadow:none}.assess-panel:hover{filter:none;background:#eaf2f0}.assess-chip{display:flex;flex-direction:column;gap:0;min-width:0}.assess-chip small{font-size:.58rem;font-weight:850;letter-spacing:.05em;text-transform:uppercase;color:#8a9aa0;line-height:1.1}.assess-panel.is-empty{padding:4px 7px;gap:5px 7px}.assess-panel.is-empty .assess-chip small{font-size:.54rem;color:#93a2a4}.assess-chip.is-set small{color:#5d7370}.assess-chip.is-set b{font-size:.76rem;font-weight:800;color:var(--ink);line-height:1.15;font-variant-numeric:tabular-nums}.missing .assess-panel{background:#eef1f1;border-color:#d2d9d8}.missing .assess-chip.is-set b{color:#3d5053}.assess-fields{display:grid;gap:10px;margin:0;padding:12px 12px 4px;border:1px solid var(--line);border-radius:10px;background:#f7fbfa}.assess-fields legend{padding:0 .3rem;font-size:.72rem;font-weight:850;letter-spacing:.08em;text-transform:uppercase;color:var(--brand)}.assess-dims{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}.assess-dims input{padding:.5rem .55rem}.photo-date-navigation>span{font-size:.68rem;font-weight:850;letter-spacing:.06em;text-transform:uppercase}.photo-date-navigation>div{display:flex;gap:5px}.photo-date-navigation button{min-height:32px;padding:.34rem .58rem;font-size:.75rem}.lightbox-context{flex:none;margin:4px 48px 12px 0}.photo-lightbox .lightbox-context h2{margin:.12rem 0;color:#e8f0ee}.photo-lightbox #lightbox-date{color:#b7c9c5}.photo-lightbox #lightbox-wound{margin:0;color:#b7c9c5;font-size:.86rem}.photo-lightbox .lightbox-meta{display:flex;align-items:flex-start;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-top:10px}.photo-lightbox .lightbox-date-navigation{width:fit-content;margin:0;border-color:#314244;background:#182426;color:#b7c9c5}.photo-lightbox .lightbox-date-navigation button{background:#223336;color:#e8f0ee;border-color:#43585a}.photo-lightbox .lightbox-assess{background:#182426;border-color:#314244;color:#b7c9c5}.photo-lightbox .lightbox-assess:hover{filter:none;background:#223336}.photo-lightbox .assess-chip small{color:#8a9aa0}.photo-lightbox .assess-chip.is-set small{color:#9eb0ad}.photo-lightbox .assess-chip.is-set b{color:#e8f0ee}
@media(max-width:700px){.patient-picker{grid-template-columns:1fr}.edit-mode-bar{top:99px}.angle-nav{font-size:.78rem}.wound-photo-meta,.photo-lightbox .lightbox-meta{flex-direction:column;align-items:stretch}.wound-photo-meta .photo-date-navigation,.wound-photo-meta .assess-panel{width:100%;margin-left:0;justify-content:space-between}.wound-photo-meta .assess-panel{max-width:none}.assess-dims{grid-template-columns:1fr}.lightbox-context{margin-right:40px}.photo-lightbox .lightbox-assess{max-width:100%}}
.sidebar-toggle{display:none}.side-title-actions{display:flex;align-items:center;gap:6px;flex:none}
@media(min-width:701px) and (max-width:1180px){
  .sidebar-toggle{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:.42rem .78rem;font-size:.8rem;font-weight:800}
  .side-title-actions .iconbtn{width:44px;height:44px;min-height:44px}
  .layout:not(.is-sidebar-collapsed) .side-title{flex-direction:column;align-items:stretch;gap:8px}
  .layout:not(.is-sidebar-collapsed) .side-title-actions{justify-content:flex-end}
  .layout:not(.is-sidebar-collapsed) .sidebar-toggle{flex:1}
  .layout.is-sidebar-collapsed{grid-template-columns:minmax(0,1fr)}
  .layout.is-sidebar-collapsed .sidebar{position:static;padding:10px 12px;background:#fff;border:1px solid var(--line);border-radius:12px}
  .layout.is-sidebar-collapsed .side-title{margin-bottom:0;align-items:center}
  .layout.is-sidebar-collapsed .side-title h2{display:none}
  .layout.is-sidebar-collapsed .sidebar-body{display:none}
}
CSS;}
function js():string{return <<<'JS'
const showModal=id=>document.getElementById(id)?.showModal();
(function(){
  const layout=document.querySelector('.layout');
  const toggle=document.getElementById('sidebar-toggle');
  if(!layout||!toggle)return;
  const mq=window.matchMedia('(min-width:701px) and (max-width:1180px)');
  const key='postop-sidebar-collapsed';
  function pref(next){
    try{
      if(next===undefined)return localStorage.getItem(key)==='1';
      localStorage.setItem(key,next?'1':'0');
    }catch(e){return false;}
  }
  function apply(){
    const tablet=mq.matches;
    const collapsed=tablet&&pref();
    layout.classList.toggle('is-sidebar-collapsed',collapsed);
    toggle.setAttribute('aria-expanded',collapsed?'false':'true');
    toggle.textContent=collapsed?'Show libraries':'Hide libraries';
  }
  toggle.addEventListener('click',()=>{pref(!layout.classList.contains('is-sidebar-collapsed'));apply()});
  mq.addEventListener('change',apply);
  apply();
})();
const accountTrigger=document.getElementById('account-trigger');
if(accountTrigger)document.querySelector('.top-actions')?.prepend(accountTrigger);
const editMode=document.getElementById('editMode');
const editModeBar=document.getElementById('edit-mode-bar');
if(editMode&&!document.querySelector('.photo-label-edit.edit-only')){editMode.hidden=true;editModeBar?.remove()}
else if(editMode&&editModeBar){editModeBar.append(editMode);editModeBar.hidden=false}
if(editMode){
  editMode.addEventListener('click',()=>{
    const enabled=editMode.getAttribute('aria-pressed')!=='true';
    editMode.setAttribute('aria-pressed',String(enabled));
    editMode.querySelector('small').textContent=enabled?'On':'Off';
    document.body.classList.toggle('is-editing',enabled);
  });
}
document.querySelector('[data-gallery-tab][aria-current="page"]')?.addEventListener('click',event=>{
  event.preventDefault();
  document.getElementById('photo-gallery-card')?.scrollIntoView({
    block:'start',
    behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth'
  });
});
document.querySelectorAll('.weight-control').forEach(el=>{
  const kg=parseFloat(el.dataset.kg), lb=parseFloat(el.dataset.lb);
  const value=el.querySelector('.weight-value');
  const buttons=[...el.querySelectorAll('[data-unit]')];
  function paint(unit){
    const n=unit==='lb'?lb:kg;
    value.textContent=(Number.isFinite(n)?n.toFixed(1):'—')+' '+unit;
    buttons.forEach(b=>b.setAttribute('aria-pressed',b.dataset.unit===unit?'true':'false'));
  }
  buttons.forEach(b=>b.addEventListener('click',e=>{e.preventDefault();e.stopPropagation();paint(b.dataset.unit)}));
});
function closeNoteTips(except){
  document.querySelectorAll('.note-icon-wrap.is-open').forEach(w=>{if(w!==except)w.classList.remove('is-open','is-pinned')});
}
document.addEventListener('pointerover',e=>{
  const wrap=e.target.closest('.note-icon-wrap');
  if(!wrap)return;
  closeNoteTips(wrap);
  wrap.classList.add('is-open');
});
document.addEventListener('pointerout',e=>{
  const wrap=e.target.closest('.note-icon-wrap');
  if(!wrap)return;
  const to=e.relatedTarget instanceof Node?e.relatedTarget:null;
  if(to&&wrap.contains(to))return;
  if(!wrap.classList.contains('is-pinned'))wrap.classList.remove('is-open');
});
document.addEventListener('click',e=>{
  const wrap=e.target.closest('.note-icon-wrap');
  if(wrap){
    closeNoteTips(wrap);
    wrap.classList.add('is-open','is-pinned');
  }else{
    closeNoteTips();
  }
  const edit=e.target.closest('.note-edit');
  if(edit){
    const row=edit.closest('.note-row');
    document.querySelectorAll('.note-row.is-editing').forEach(r=>{if(r!==row)r.classList.remove('is-editing')});
    row?.classList.add('is-editing');
    row?.querySelector('textarea')?.focus();
  }
  if(e.target.closest('.note-cancel')){
    e.preventDefault();
    e.target.closest('.note-row')?.classList.remove('is-editing');
  }
});
document.querySelector('.date-chip.current')?.scrollIntoView({inline:'nearest',block:'nearest',behavior:'instant'});
document.querySelectorAll('.angle-set').forEach(set=>{
  const figures=[...set.querySelectorAll('.angle-frames figure')];
  const n=figures.length;
  const status=set.querySelector('.angle-status');
  const tabs=[...set.querySelectorAll('.angle-dots [role="tab"]')];
  let i=Math.max(0,figures.findIndex(f=>f.classList.contains('is-current')));
  const mode=()=>set.dataset.mode||'cycle';
  function paint(){
    const column=mode()==='column';
    figures.forEach((fig,idx)=>{
      fig.hidden=!column&&idx!==i;
      fig.classList.toggle('is-current',idx===i);
    });
    tabs.forEach((tab,idx)=>tab.setAttribute('aria-selected',idx===i?'true':'false'));
    if(status&&figures[i]){
      status.textContent=column?(n+' shots'):(n>1?`Shot ${i+1} of ${n}`:'Shot 1');
    }
    const expand=set.querySelector('.angle-expand'), collapse=set.querySelector('.angle-collapse');
    if(expand)expand.hidden=column;
    if(collapse)collapse.hidden=!column;
    set.querySelectorAll('.angle-nav').forEach(btn=>btn.hidden=column||n<2);
    const dots=set.querySelector('.angle-dots');
    if(dots)dots.hidden=column||n<2;
  }
  function go(next){if(!n)return;i=(next+n)%n;paint()}
  set.querySelector('.angle-nav.prev')?.addEventListener('click',()=>go(i-1));
  set.querySelector('.angle-nav.next')?.addEventListener('click',()=>go(i+1));
  set.querySelector('.angle-date-prev')?.addEventListener('click',()=>visitPhotoDate(set,-1));
  set.querySelector('.angle-date-next')?.addEventListener('click',()=>visitPhotoDate(set,1));
  tabs.forEach(tab=>tab.addEventListener('click',()=>go(Number(tab.dataset.index))));
  set.querySelector('.angle-expand')?.addEventListener('click',()=>{set.dataset.mode='column';paint()});
  set.querySelector('.angle-collapse')?.addEventListener('click',()=>{set.dataset.mode='cycle';paint()});
  set.querySelectorAll('.photo-expand').forEach(btn=>btn.addEventListener('click',e=>{e.stopPropagation();openLightbox(set,Number(btn.dataset.index))}));
  set.querySelector('.angle-frames')?.addEventListener('click',e=>{if(e.target.closest('img'))openLightbox(set,Number(e.target.closest('figure')?.dataset.index||i))});
  set.addEventListener('keydown',e=>{if(document.getElementById('photo-lightbox')?.open)return;if(mode()!=='cycle'||n<2)return;if(e.key==='ArrowLeft'){e.preventDefault();go(i-1)}if(e.key==='ArrowRight'){e.preventDefault();go(i+1)}});
  set._figures=figures;set._go=go;set._index=()=>i;set._setIndex=idx=>{i=idx;paint()};
  paint();
});
function photoDatesFor(set){return (set?.dataset.photoDates||'').split(',').filter(Boolean)}
function visitPhotoDate(set,delta){
  const dates=photoDatesFor(set);if(dates.length<2)return;
  const current=Math.max(0,dates.indexOf(set.dataset.date||''));
  const target=dates[(current+delta+dates.length)%dates.length];
  const url=new URL(location.href);url.searchParams.set('date',target);url.hash='';location.assign(url.toString());
}
const lightbox=document.getElementById('photo-lightbox');
let lbSet=null,lbPhotos=[],lbIndex=0,lbDate='',lbDates=[],lbWoundId='',lbWoundName='',lbWoundDescription='',lbAssessment={};
function lightboxDateLabel(value){
  const date=new Date(`${value}T12:00:00Z`);
  return Number.isNaN(date.valueOf())?value:new Intl.DateTimeFormat(undefined,{weekday:'long',month:'long',day:'numeric',year:'numeric',timeZone:'UTC'}).format(date);
}
function parseAssessment(raw){
  if(!raw)return {};
  if(typeof raw==='object'&&!Array.isArray(raw))return raw;
  try{const data=JSON.parse(raw);return data&&typeof data==='object'&&!Array.isArray(data)?data:{}}catch(e){return {}}
}
function assessmentText(value){return String(value||'').trim()}
function lightboxAssessmentClips(a){
  a=a||{};
  const w=assessmentText(a.width),d=assessmentText(a.depth),l=assessmentText(a.length);
  const sizeSet=!!(w||d||l);
  const part=v=>v||'–';
  const clip=(key,label)=>{const value=assessmentText(a[key]);return {label,value,set:!!value}};
  return [
    {label:'W×D×L',value:sizeSet?`${part(w)} × ${part(d)} × ${part(l)}`:'',set:sizeSet},
    clip('periskin','Periskin'),
    clip('drainage','Drainage'),
    clip('smell','Odor'),
    clip('dressing','Dressing'),
    clip('treatment','Tx'),
  ];
}
function lightboxEsc(value){return String(value).replace(/[&<>"']/g,ch=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]))}
function paintLightboxAssessment(a){
  const host=lightbox?.querySelector('.lightbox-assess');
  if(!host)return;
  const clips=lightboxAssessmentClips(a);
  const any=clips.some(c=>c.set);
  host.className='assess-panel lightbox-assess '+(any?'is-set':'is-empty');
  host.innerHTML=clips.map(c=>`<span class="assess-chip ${c.set?'is-set':'is-empty'}"><small>${lightboxEsc(c.label)}</small>${c.set?`<b>${lightboxEsc(c.value)}</b>`:''}</span>`).join('');
  host.setAttribute('aria-label',`Assessment for ${lbWoundName||'wound'}. `+clips.map(c=>c.set?`${c.label} ${c.value}`:`${c.label} not charted`).join('. '));
}
function photosFromSet(set){
  return [...set.querySelectorAll('.angle-frames figure')].map((fig,i)=>{
    const img=fig.querySelector('img');
    const title=fig.querySelector('strong')?.textContent||`Shot ${i+1}`;
    const note=fig.querySelector('.photo-note')?.textContent||'';
    return {src:img?.currentSrc||img?.src||'',alt:img?.alt||title,title,caption:note||fig.querySelector('small')?.textContent||''};
  });
}
function lightboxPaint(){
  if(!lightbox||!lbPhotos[lbIndex])return;
  const photo=lbPhotos[lbIndex];
  const img=lightbox.querySelector('img');
  img.src=photo.src;
  img.alt=photo.alt;
  lightbox.querySelector('#lightbox-title').textContent=lbWoundName||'Wound photo';
  lightbox.querySelector('#lightbox-date').textContent=lightboxDateLabel(lbDate);
  lightbox.querySelector('#lightbox-wound').textContent=lbWoundDescription||'Wound photo';
  lightbox.querySelector('figcaption strong').textContent=photo.title;
  lightbox.querySelector('figcaption small').textContent=photo.caption||'';
  lightbox.querySelector('.lightbox-status').textContent=lbPhotos.length>1?`Shot ${lbIndex+1} of ${lbPhotos.length} · Previous and next cycle shots.`:'Shot 1';
  const multi=lbPhotos.length>1;
  lightbox.querySelector('.lightbox-prev').hidden=!multi;
  lightbox.querySelector('.lightbox-next').hidden=!multi;
  lightbox.querySelector('.lightbox-date-navigation').hidden=lbDates.length<2;
  paintLightboxAssessment(lbAssessment);
}
function bindLightboxSet(set,index){
  lbSet=set;
  lbWoundId=set.dataset.woundId||lbWoundId;
  lbWoundName=set.dataset.woundName||'Wound photo';
  lbWoundDescription=set.dataset.woundDescription||'';
  lbDate=set.dataset.date||'';
  lbDates=photoDatesFor(set);
  lbAssessment=parseAssessment(set.dataset.assessment);
  lbPhotos=photosFromSet(set);
  lbIndex=Math.max(0,Math.min(Number(index)||0,lbPhotos.length-1));
  if(typeof set._setIndex==='function')set._setIndex(lbIndex);
  lightboxPaint();
}
function openLightbox(set,index){
  bindLightboxSet(set,index);
  if(lightbox&&!lightbox.open)lightbox.showModal();
  lightbox?.querySelector('.lightbox-close')?.focus();
}
function lightboxGo(delta){
  if(!lbPhotos.length)return;
  lbIndex=(lbIndex+delta+lbPhotos.length)%lbPhotos.length;
  if(lbSet&&typeof lbSet._setIndex==='function')lbSet._setIndex(lbIndex);
  lightboxPaint();
}
function findAngleSet(woundId,date){
  if(!woundId||!date)return null;
  return document.querySelector(`.angle-set[data-wound-id="${CSS.escape(woundId)}"][data-date="${CSS.escape(date)}"]`);
}
async function loadLightboxDate(target){
  const existing=findAngleSet(lbWoundId,target);
  if(existing){bindLightboxSet(existing,0);return}
  const r=await fetch(`index.php?action=snapshot&patient=${encodeURIComponent(PATIENT_ID)}`);
  if(!r.ok)return;
  const data=await r.json();
  let photos=[];
  for(const lib of data.libraries||[]){
    for(const wound of lib.wounds||[]){
      if(String(wound.id||'')!==lbWoundId)continue;
      lbWoundName=wound.name||lbWoundName;
      lbWoundDescription=wound.location||lbWoundDescription;
      const shots=wound.updates?.[target]?.photos||[];
      lbAssessment=parseAssessment(wound.updates?.[target]?.assessment||{});
      const rev=lib.revision||1;
      photos=shots.map((p,i)=>{
        const title=(p.angle&&p.angle.trim())||`Shot ${i+1}`;
        return {src:`index.php?action=media&patient=${encodeURIComponent(PATIENT_ID)}&id=${encodeURIComponent(p.id)}&v=${rev}`,alt:`${title} of ${wound.name||'wound'}`,title,caption:p.caption||''};
      });
    }
  }
  if(!photos.length)return;
  lbSet=null;
  lbDate=target;
  lbPhotos=photos;
  lbIndex=0;
  lightboxPaint();
}
function lightboxVisitDate(delta){
  if(lbDates.length<2)return;
  const current=Math.max(0,lbDates.indexOf(lbDate));
  const target=lbDates[(current+delta+lbDates.length)%lbDates.length];
  loadLightboxDate(target);
}
if(lightbox){
  lightbox.querySelector('.lightbox-close')?.addEventListener('click',()=>lightbox.close());
  lightbox.querySelector('.lightbox-prev')?.addEventListener('click',e=>{e.stopPropagation();lightboxGo(-1)});
  lightbox.querySelector('.lightbox-next')?.addEventListener('click',e=>{e.stopPropagation();lightboxGo(1)});
  lightbox.querySelector('.lightbox-date-prev')?.addEventListener('click',e=>{e.preventDefault();e.stopPropagation();lightboxVisitDate(-1)});
  lightbox.querySelector('.lightbox-date-next')?.addEventListener('click',e=>{e.preventDefault();e.stopPropagation();lightboxVisitDate(1)});
  lightbox.querySelector('.lightbox-assess')?.addEventListener('click',e=>{
    e.preventDefault();e.stopPropagation();
    if(!lbWoundId)return;
    lightbox.close();
    showModal('update-'+lbWoundId);
  });
  lightbox.addEventListener('click',e=>{if(e.target===lightbox)lightbox.close()});
  lightbox.addEventListener('keydown',e=>{
    if(!lightbox.open||lbPhotos.length<2)return;
    if(e.key==='ArrowLeft'){e.preventDefault();lightboxGo(-1)}
    if(e.key==='ArrowRight'){e.preventDefault();lightboxGo(1)}
  });
}
const network=document.getElementById('network');
if(network){
  network.classList.add('footer-status');
  network.setAttribute('role','button');
  network.setAttribute('tabindex','0');
  network.setAttribute('aria-haspopup','dialog');
  network.setAttribute('aria-label','Explain connection status');
  document.querySelector('footer')?.append(network);
  const networkInfo=document.createElement('dialog');
  networkInfo.className='network-info';
  networkInfo.innerHTML='<button type="button" class="close" aria-label="Close">×</button><h2>Connection status</h2><p><strong>Online</strong> or <strong>Offline</strong> reflects whether this device has an internet connection.</p><p>A reliable connection is needed to load and save photos, unless you previously synced a complete offline copy to this device.</p>';
  document.body.append(networkInfo);
  const openNetworkInfo=()=>networkInfo.showModal();
  network.addEventListener('click',openNetworkInfo);
  network.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();openNetworkInfo()}});
  networkInfo.querySelector('.close')?.addEventListener('click',()=>networkInfo.close());
  networkInfo.addEventListener('click',e=>{if(e.target===networkInfo)networkInfo.close()});
}
function net(){if(!network)return;network.textContent=navigator.onLine?'Online':'Offline';network.classList.toggle('offline',!navigator.onLine)} addEventListener('online',net);addEventListener('offline',net);net();
if('serviceWorker'in navigator)navigator.serviceWorker.register('index.php?action=service-worker',{scope:'./'});
const PATIENT_ID=new URLSearchParams(location.search).get('patient')||'sample-patient';
const DB='skin-wound-viewer',CACHE='swcv-patient-'+PATIENT_ID;
function db(){return new Promise((ok,no)=>{const r=indexedDB.open(DB,1);r.onupgradeneeded=()=>{const d=r.result;if(!d.objectStoreNames.contains('meta'))d.createObjectStore('meta');if(!d.objectStoreNames.contains('outbox'))d.createObjectStore('outbox',{keyPath:'id'})};r.onsuccess=()=>ok(r.result);r.onerror=()=>no(r.error)})}
async function metaPut(k,v){const d=await db();return new Promise((ok,no)=>{const t=d.transaction('meta','readwrite');t.objectStore('meta').put(v,k);t.oncomplete=ok;t.onerror=()=>no(t.error)})}
async function metaGet(k){const d=await db();return new Promise((ok,no)=>{const r=d.transaction('meta').objectStore('meta').get(k);r.onsuccess=()=>ok(r.result);r.onerror=()=>no(r.error)})}
const syncBtn=document.getElementById('syncButton'),syncInfo=document.getElementById('syncInfo');
function clearCopyButton(){
  if(!syncBtn)return null;
  let frame=syncBtn.closest('.sync-card-frame');
  if(!frame){frame=document.createElement('div');frame.className='sync-card-frame';syncBtn.before(frame);frame.append(syncBtn)}
  let button=frame.querySelector('.sync-clear');
  if(!button){button=document.createElement('button');button.type='button';button.className='sync-clear';button.setAttribute('aria-label','Remove device copy');button.setAttribute('title','Remove device copy');button.textContent='×';button.addEventListener('click',clearCopy);frame.append(button)}
  return button;
}
async function refreshSync(){if(!syncBtn)return;const clear=clearCopyButton();const m=await metaGet(PATIENT_ID).catch(()=>null);if(m?.complete){syncBtn.querySelector('strong').textContent='Available offline';syncBtn.querySelector('span').innerHTML='Updated '+new Date(m.at).toLocaleString()+' · <b>Update offline copy</b>';syncInfo.textContent='';if(clear)clear.hidden=false}else if(clear)clear.hidden=true}
async function clearCopy(){if(!confirm('Remove this patient’s offline copy and pending changes from this device? Server records will remain.'))return;await caches.delete(CACHE);const d=await db();await new Promise(ok=>{const t=d.transaction(['meta','outbox'],'readwrite');t.objectStore('meta').delete(PATIENT_ID);t.objectStore('outbox').clear();t.oncomplete=ok});location.reload()}
async function syncAll(){if(!confirm('Privacy notice: all wound records and full-size photos will be stored in this browser on this device. Browser storage is not encrypted and may be evicted. Do not continue on a shared or public device. Continue?'))return;syncBtn.disabled=true;try{if(navigator.storage?.persist)await navigator.storage.persist();const m=await fetch(`index.php?action=sync-manifest&patient=${encodeURIComponent(PATIENT_ID)}`).then(r=>{if(!r.ok)throw Error('Manifest unavailable');return r.json()});if(navigator.storage?.estimate){const e=await navigator.storage.estimate();if(e.quota-e.usage<m.total_bytes)throw Error('Not enough browser storage for this copy.')}const c=await caches.open(CACHE);for(const req of await c.keys())await c.delete(req);let done=0,bytes=0;syncInfo.textContent=`Syncing 0 / ${m.photo_count} photos…`;const snap=await fetch(m.snapshot_url);if(!snap.ok)throw Error('Snapshot failed');const data=await snap.clone().json();await c.put(m.snapshot_url,snap);for(const l of data.libraries){let day=new Date(l.start_date+'T12:00:00'),today=new Date();while(day<=today){const ds=day.toISOString().slice(0,10),url=`index.php?patient=${encodeURIComponent(PATIENT_ID)}&library=${encodeURIComponent(l.id)}&date=${ds}`;const page=await fetch(url);if(!page.ok)throw Error('Offline page download failed');await c.put(url,page);day.setDate(day.getDate()+1)}}for(const x of m.media){const r=await fetch(x.url);if(!r.ok)throw Error('Photo download failed');await c.put(x.url,r.clone());done++;bytes+=x.bytes;syncInfo.textContent=`Syncing ${done} / ${m.photo_count} photos · ${(bytes/1024).toFixed(1)} KB`;await metaPut(PATIENT_ID,{complete:false,done,total:m.photo_count})}await metaPut(PATIENT_ID,{complete:true,at:Date.now(),revision:m.revision});syncInfo.textContent='Offline copy complete.';await refreshSync()}catch(e){syncInfo.textContent='Sync incomplete: '+e.message;await metaPut(PATIENT_ID,{complete:false,at:Date.now()}).catch(()=>{})}finally{syncBtn.disabled=false}}
if(syncBtn)syncBtn.onclick=syncAll;refreshSync();
const pendingBtn=document.getElementById('reviewPending');
async function outboxAll(){const d=await db();return new Promise((ok,no)=>{const r=d.transaction('outbox').objectStore('outbox').getAll();r.onsuccess=()=>ok(r.result);r.onerror=()=>no(r.error)})}
async function pendingStatus(){if(!pendingBtn)return;const q=await outboxAll().catch(()=>[]);pendingBtn.hidden=!q.length;pendingBtn.textContent=`Review and sync ${q.length} change${q.length===1?'':'s'}`;if(q.length){network.textContent='Pending changes';network.classList.add('offline')}}
async function stage(form){const fd=new FormData(form),fields=[],files=[];for(const [k,v] of fd){if(v instanceof File&&v.size)files.push([k,v]);else fields.push([k,String(v)])}fields.push(['expected_revision',document.documentElement.dataset.revision]);const item={id:crypto.randomUUID(),created_at:Date.now(),fields,files};const d=await db();await new Promise((ok,no)=>{const t=d.transaction('outbox','readwrite');t.objectStore('outbox').add(item);t.oncomplete=ok;t.onerror=()=>no(t.error)});form.closest('dialog')?.close();await pendingStatus();alert('Pending sync — this change is stored on this device, not yet saved on the server.')}
document.addEventListener('submit',e=>{const form=e.target;if(!navigator.onLine&&form instanceof HTMLFormElement&&form.querySelector('[name=op]')?.value!=='logout'){e.preventDefault();stage(form).catch(x=>alert('Could not stage this change: '+x.message))}});
if(pendingBtn)pendingBtn.onclick=async()=>{const q=await outboxAll();if(!q.length)return;if(!confirm(`Review complete. Sync ${q.length} pending change(s) to the server now? Replay stops on a conflict.`))return;const d=await db();for(const item of q){const fd=new FormData();for(const [k,v] of item.fields)fd.append(k,v);for(const [k,v] of item.files)fd.append(k,v,v.name);const r=await fetch('index.php',{method:'POST',body:fd,redirect:'manual'});if(r.status===409){alert('Conflict detected. Pending changes were preserved. Reload the server version or retry after review.');break}if(!r.ok&&r.type!=='opaqueredirect'){alert('Sync stopped. Pending changes were preserved.');break}await new Promise(ok=>{const t=d.transaction('outbox','readwrite');t.objectStore('outbox').delete(item.id);t.oncomplete=ok})}await pendingStatus();if(!(await outboxAll()).length)location.reload()};pendingStatus();
JS;}
