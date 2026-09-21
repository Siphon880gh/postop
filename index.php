<?php
declare(strict_types=1);

/* Skin Wound Clinical Viewer — dependency-free, file-backed demo. */
const APP_NAME = 'Skin Wound Clinical Viewer';
const PATIENT_ID = 'sample-patient';
const MAX_UPLOAD = 15728640;
$root = getenv('POSTOP_STORAGE_DIR') ?: __DIR__ . '/storage';
$patientDir = $root . '/patients/' . PATIENT_ID;
$dataFile = $patientDir . '/record.json';

function fail(string $message, int $status = 400): void { http_response_code($status); header('Content-Type: text/plain; charset=utf-8'); exit($message); }
function safe_id(string $value): bool { return (bool)preg_match('/^[a-z0-9][a-z0-9-]{0,63}$/', $value); }
function new_id(string $prefix): string { return $prefix . '-' . strtolower(bin2hex(random_bytes(6))); }
function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
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
    $data=['patient'=>['id'=>PATIENT_ID,'name'=>'Sample Patient','account_number'=>'A-10042','age'=>64,'weight_kg'=>78.2,'gender'=>'Female','diagnosis'=>'Postoperative left heel wound with posterior heel donor site','avatar'=>'IMG-AVATAR-FEMALE.svg'],'revision'=>1,'libraries'=>[
      ['id'=>'left-heel','name'=>'Left Heel Post-op Recovery','type'=>'Postoperative Wound','custom_type'=>'','start_date'=>$start->format('Y-m-d'),'description'=>'Track recovery milestones and dressing observations.','revision'=>1,'notes'=>[['id'=>'note-lib-1','text'=>'Review progress at each dressing change.','created_at'=>now()]],'day_notes'=>[$partial=>[['id'=>'note-day-1','text'=>'Patient reported improved comfort.','created_at'=>now()]]],'wounds'=>[
        ['id'=>'lateral-incision','name'=>'Lateral incision','location'=>'Left lateral heel','active'=>true,'notes'=>[['id'=>'note-wound-1','text'=>'Observe incision edge and surrounding skin.','created_at'=>now()]],'updates'=>[$partial=>['note'=>'Dressing changed; no image required.','photos'=>[]],$complete=>['note'=>'Routine progress image set.','photos'=>$photos]]],
        ['id'=>'donor-site','name'=>'Donor site','location'=>'Posterior heel','active'=>true,'notes'=>[],'updates'=>[$complete=>['note'=>'Clean and dry.','photos'=>[]]]]
      ]],
      ['id'=>'mole-monitor','name'=>'Mole Monitoring','type'=>'Mole Monitoring','custom_type'=>'','start_date'=>$today->modify('-20 days')->format('Y-m-d'),'description'=>'Demo longitudinal comparison library.','revision'=>1,'notes'=>[],'day_notes'=>[],'wounds'=>[['id'=>'medial-site','name'=>'Medial site','location'=>'Left ankle','active'=>true,'notes'=>[],'updates'=>[]]]]
    ]]; atomic_write($dataFile,$data);
}
seed($dataFile,$patientDir);
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
ensure_avatar_assets($patientDir);
function patient_profile(array $d): array {
    $p=is_array($d['patient']??null)?$d['patient']:[];
    $gender=(string)($p['gender']??'Female');
    $avatar=(string)($p['avatar']??(strcasecmp($gender,'male')===0?'IMG-AVATAR-MALE.svg':'IMG-AVATAR-FEMALE.svg'));
    return [
        'id'=>(string)($p['id']??PATIENT_ID),
        'name'=>(string)($p['name']??'Sample Patient'),
        'account_number'=>(string)($p['account_number']??'A-10042'),
        'age'=>(int)($p['age']??64),
        'weight_kg'=>(float)($p['weight_kg']??78.2),
        'gender'=>$gender,
        'diagnosis'=>(string)($p['diagnosis']??'Postoperative left heel wound with posterior heel donor site'),
        'avatar'=>$avatar,
    ];
}
function load_data(): array { global $dataFile; $raw=@file_get_contents($dataFile); $d=$raw===false?null:json_decode($raw,true); if (!is_array($d)) fail('The patient record is unavailable or invalid.',500); $d['patient']=patient_profile($d); return $d; }
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

$authed=($_SESSION['auth']??false)===true;
if($_SERVER['REQUEST_METHOD']==='POST'){
    $op=$_POST['op']??'';
    if($op==='login'){if(hash_equals('admin',(string)($_POST['username']??''))&&hash_equals('password',(string)($_POST['password']??''))){session_regenerate_id(true);$_SESSION=['auth'=>true,'csrf'=>bin2hex(random_bytes(24)),'flash'=>'Welcome back.'];header('Location: index.php');exit;}$_SESSION['login_error']='Incorrect username or password.';header('Location: index.php');exit;}
    if(!$authed) fail('Authentication required.',401);
    if(!hash_equals((string)($_SESSION['csrf']??''),(string)($_POST['csrf']??'')))fail('Invalid CSRF token.',403);
    if($op==='logout'){$_SESSION=[];session_destroy();header('Clear-Site-Data: "cache", "storage"');header('Location: index.php');exit;}
    $d=load_data(); if(isset($_POST['expected_revision'])&&(int)$_POST['expected_revision']!==(int)$d['revision'])fail('Conflict: the server record changed. Reload the server version or review and retry the pending change.',409); $libId=(string)($_POST['library_id']??''); if($libId!==''&&!safe_id($libId))fail('Invalid library ID.');
    $redirect='index.php?patient='.PATIENT_ID;
    try {
      if($op==='library_save'){$name=trim((string)($_POST['name']??''));$type=(string)($_POST['type']??'');$date=(string)($_POST['start_date']??'');$allowed=['Pressure Injury','Mole Monitoring','Postoperative Wound','Custom'];if($name===''||!in_array($type,$allowed,true)||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)||$date>gmdate('Y-m-d')||($type==='Custom'&&trim((string)($_POST['custom_type']??''))===''))fail('Name, valid type, and a non-future starting date are required.');if($libId===''){$libId=new_id('library');$d['libraries'][]=['id'=>$libId,'name'=>$name,'type'=>$type,'custom_type'=>trim((string)($_POST['custom_type']??'')),'start_date'=>$date,'description'=>trim((string)($_POST['description']??'')),'revision'=>1,'notes'=>[],'day_notes'=>[],'wounds'=>[]];}else{$l=&library($d,$libId);foreach(['name'=>$name,'type'=>$type,'custom_type'=>trim((string)($_POST['custom_type']??'')),'start_date'=>$date,'description'=>trim((string)($_POST['description']??''))] as $k=>$v)$l[$k]=$v;$l['revision']++;} $redirect.='&library='.$libId;}
      elseif($op==='library_delete'){$idx=null;foreach($d['libraries'] as $i=>$l)if($l['id']===$libId)$idx=$i;if($idx===null)fail('Library not found.',404);remove_tree("$patientDir/libraries/$libId","$patientDir/libraries");array_splice($d['libraries'],$idx,1);}
      elseif($op==='wound_save'){$l=&library($d,$libId);$wid=(string)($_POST['wound_id']??'');$name=trim((string)($_POST['name']??''));if($name==='')fail('Wound name is required.');$payload=['name'=>$name,'location'=>trim((string)($_POST['location']??'')),'active'=>isset($_POST['active'])];if($wid===''){$wid=new_id('wound');$l['wounds'][]=['id'=>$wid]+$payload+['notes'=>[],'updates'=>[]];}else{$w=&wound($l,$wid);foreach($payload as $k=>$v)$w[$k]=$v;}$l['revision']++;$redirect.='&library='.$libId;}
      elseif($op==='wound_delete'){$l=&library($d,$libId);$wid=(string)$_POST['wound_id'];$idx=null;foreach($l['wounds'] as $i=>$w)if($w['id']===$wid)$idx=$i;if($idx===null)fail('Wound not found.',404);remove_tree("$patientDir/libraries/$libId/wounds/$wid","$patientDir/libraries/$libId/wounds");array_splice($l['wounds'],$idx,1);$l['revision']++;$redirect.='&library='.$libId;}
      elseif($op==='update_save'){$l=&library($d,$libId);$w=&wound($l,(string)$_POST['wound_id']);$date=(string)$_POST['date'];if($date<$l['start_date']||$date>gmdate('Y-m-d'))fail('Date is outside this library timeline.');$old=$w['updates'][$date]['photos']??[];$w['updates'][$date]=['note'=>trim((string)($_POST['note']??'')),'photos'=>$old];$l['revision']++;$redirect.='&library='.$libId.'&date='.$date;}
      elseif($op==='update_delete'){$l=&library($d,$libId);$w=&wound($l,(string)$_POST['wound_id']);$date=(string)$_POST['date'];unset($w['updates'][$date]);remove_tree("$patientDir/libraries/$libId/wounds/{$w['id']}/$date","$patientDir/libraries/$libId/wounds/{$w['id']}");$l['revision']++;$redirect.='&library='.$libId.'&date='.$date;}
      elseif(in_array($op,['note_add','note_edit','note_delete'],true)){$l=&library($d,$libId);$scope=(string)$_POST['scope'];if($scope==='library')$notes=&$l['notes'];elseif($scope==='day'){$date=(string)$_POST['date'];if($date<$l['start_date']||$date>gmdate('Y-m-d'))fail('Invalid note date.');$l['day_notes'][$date]??=[];$notes=&$l['day_notes'][$date];}elseif($scope==='wound'){$w=&wound($l,(string)$_POST['wound_id']);$notes=&$w['notes'];}else fail('Invalid note scope.');$nid=(string)($_POST['note_id']??'');if($op==='note_add'){$text=trim((string)$_POST['text']);if($text==='')fail('Note cannot be empty.');$notes[]=['id'=>new_id('note'),'text'=>$text,'created_at'=>now()];}else{$found=null;foreach($notes as $i=>$n)if($n['id']===$nid)$found=$i;if($found===null)fail('Note not found.',404);if($op==='note_delete')array_splice($notes,$found,1);else{$text=trim((string)$_POST['text']);if($text==='')fail('Note cannot be empty.');$notes[$found]['text']=$text;$notes[$found]['updated_at']=now();}}$l['revision']++;$redirect.='&library='.$libId.(isset($date)?'&date='.$date:'');}
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
if($action==='sync-manifest'){$d=load_data();$media=[];$bytes=0;foreach($d['libraries'] as $l)foreach($l['wounds'] as $w)foreach($w['updates'] as $u)foreach($u['photos'] as $p){$url='index.php?action=media&id='.$p['id'].'&v='.$l['revision'];$media[]=['id'=>$p['id'],'url'=>$url,'bytes'=>$p['bytes']??0];$bytes+=$p['bytes']??0;}header('Content-Type: application/json');echo json_encode(['patient_id'=>PATIENT_ID,'revision'=>$d['revision'],'generated_at'=>now(),'snapshot_url'=>'index.php?action=snapshot&v='.$d['revision'],'media'=>$media,'photo_count'=>count($media),'total_bytes'=>$bytes]);exit;}
if($action==='replay'){fail('Use the online forms to review and apply pending changes.',409);}

$csrf=$_SESSION['csrf']??'';$loginError=$_SESSION['login_error']??'';unset($_SESSION['login_error']);$flash=$_SESSION['flash']??'';unset($_SESSION['flash']);
if(!$authed): ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><title>Sign in · <?=APP_NAME?></title><style><?=css()?></style></head><body><a class="skip-link" href="#main">Skip to main content</a><main id="main" class="login" tabindex="-1"><section class="login-card"><div class="brandmark" aria-hidden="true">+</div><p class="eyebrow">Clinical recordkeeping demo</p><h1><?=APP_NAME?></h1><p class="muted">Sign in to review longitudinal wound records.</p><?php if($loginError):?><div class="alert" role="alert" id="login-error"><?=h($loginError)?></div><?php endif?><form method="post"<?=$loginError?' aria-describedby="login-error"':''?>><input type="hidden" name="op" value="login"><label>Username<input name="username" autocomplete="username" required></label><label>Password<input name="password" type="password" autocomplete="current-password" required></label><button type="submit">Sign in</button></form><button type="button" class="demo" id="fillDemo" aria-label="Fill demo credentials"><strong>Demo-only credentials</strong><code>admin</code> / <code>password</code></button></section></main><footer>Demo wound-photo recordkeeping app — not for diagnosis or emergency use.</footer><script>document.getElementById('fillDemo').addEventListener('click',()=>{const f=document.querySelector('form');const u=f.querySelector('[name=username]');const p=f.querySelector('[name=password]');u.value='admin';p.value='password';u.focus()});</script></body></html><?php exit;endif;
$d=load_data();$profile=patient_profile($d);$patient=isset($_GET['patient']);$view=(($_GET['view']??'')==='gallery')?'gallery':'day';$selectedId=(string)($_GET['library']??($d['libraries'][0]['id']??''));$selected=null;foreach($d['libraries'] as $l)if($l['id']===$selectedId)$selected=$l;if($selected){$date=(string)($_GET['date']??'');if($date===''||$date<$selected['start_date']||$date>gmdate('Y-m-d'))$date=latest_relevant_date($selected);}else{$date=gmdate('Y-m-d');}
?><!doctype html><html lang="en" data-revision="<?=h($d['revision'])?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#164e63"><link rel="manifest" href="index.php?action=manifest"><title><?=APP_NAME?></title><style><?=css()?></style></head><body><a class="skip-link" href="#main">Skip to main content</a><header class="topbar"><a class="wordmark" href="index.php"><span aria-hidden="true">+</span><?=APP_NAME?></a><div class="top-actions"><button type="button" id="reviewPending" class="ghost small" hidden>Review and sync 0 changes</button><button type="button" id="editMode" class="edit-mode" aria-pressed="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm17.71-10.21a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg><span>Edit Mode</span><small>Off</small></button><span id="network" class="status" role="status" aria-live="polite">Online</span><form method="post"><input type="hidden" name="op" value="logout"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><button class="ghost" type="submit">Log out</button></form></div></header><?php if($flash):?><div class="toast" role="status"><?=h($flash)?></div><?php endif?><main id="main" class="app" tabindex="-1">
<?php if(!$patient):?><section class="pagehead"><div><p class="eyebrow">Workspace</p><h1>Patients</h1><p class="muted">One non-identifying demonstration record.</p></div></section><?=patient_picker($d)?>
<?php else:?><nav class="crumb" aria-label="Breadcrumb"><a href="index.php">Patients</a><span aria-hidden="true">/</span><strong><?=h($profile['name'])?></strong></nav><?=patient_facts_compact($profile)?><div class="layout"><aside class="sidebar"><div class="side-title"><div><p class="eyebrow">Progress libraries</p><h2><?=h($profile['name'])?></h2></div><button type="button" class="iconbtn" onclick="showModal('library-new')" aria-label="Add progress library">+</button></div><div class="library-list"><?php foreach($d['libraries'] as $l):?><a class="library-item <?=$l['id']===$selectedId?'selected':''?>" <?=$l['id']===$selectedId?'aria-current="page"':''?> href="?patient=<?=PATIENT_ID?>&library=<?=h($l['id'])?>&view=<?=h($view)?>"><span><strong><?=h($l['name'])?></strong><small><?=h($l['type']==='Custom'?$l['custom_type']:$l['type'])?> · <?=h($l['start_date'])?></small></span><?php if(count($l['notes'])):?><span class="note-badge library-note-badge"><span aria-hidden="true">Notes</span><b aria-hidden="true"><?=count($l['notes'])?></b><span class="sr-only"><?=count($l['notes'])?> notes</span></span><?php endif?></a><?php endforeach?></div><button type="button" id="syncButton" class="sync-card" data-count="<?=sync_count($d)?>"><strong>Sync all to this device</strong><span>Private offline copy · <b><?=sync_count($d)?> photos</b></span></button><div id="syncInfo" class="muted tiny" role="status" aria-live="polite"></div></aside>
<section class="content"><?php if(!$selected):?><div class="empty"><h2>No progress library</h2><p>Add a library to begin.</p></div><?php else:$notes=$selected['notes'];?><article class="library-head <?=count($notes)?'noted':''?>"><div><span class="type"><?=h($selected['type']==='Custom'?$selected['custom_type']:$selected['type'])?></span><h1><?=h($selected['name'])?></h1><p><?=h($selected['description'])?></p><small>Tracking since <?=h(date('M j, Y',strtotime($selected['start_date'])))?> · Revision <?=h($selected['revision'])?></small></div><div class="head-buttons"><?=notes_trigger('notes-library',count($notes))?><button type="button" class="ghost" onclick="showModal('library-edit')">Edit library</button><form method="post" onsubmit="return confirm('Delete this library and all its records?')"><input type="hidden" name="op" value="library_delete"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="library_id" value="<?=h($selectedId)?>"><button class="danger ghost" type="submit">Delete library</button></form></div></article>
<?=view_switch($selected,$date,$view),timeline($selected,$date,$view)?>
<?=$view==='gallery'?gallery_view($selected,$date,$csrf):day_view($selected,$date,$csrf)?>
<div class="section-title"><h2>Wound list</h2><button type="button" class="ghost" onclick="showModal('wound-new')">Add wound</button></div><div class="manage-list"><?php foreach($selected['wounds'] as $w):?><div><span><strong><?=h($w['name'])?></strong><small><?=h($w['location'])?> · <?=$w['active']?'Active':'Inactive — history retained'?></small></span><span class="row"><button type="button" class="ghost small" onclick="showModal('wound-<?=h($w['id'])?>')">Edit <?=h($w['name'])?></button><form method="post" onsubmit="return confirm('Delete this wound, its history, and photos?')"><?=hidden($csrf,$selectedId,$w['id'])?><input type="hidden" name="op" value="wound_delete"><button class="danger ghost small" type="submit">Delete <?=h($w['name'])?></button></form></span></div><?php endforeach?></div>
<?=modals($selected,$csrf,$date)?><?=photo_lightbox()?><?php endif?></section></div><?php endif?></main><footer>Demo wound-photo recordkeeping app — not for diagnosis or emergency use.</footer><script><?=js()?></script></body></html>
<?php
function hidden(string $csrf,string $lib,string $w='',string $date=''):string{return '<input type="hidden" name="csrf" value="'.h($csrf).'"><input type="hidden" name="library_id" value="'.h($lib).'">'.($w?'<input type="hidden" name="wound_id" value="'.h($w).'">':'').($date?'<input type="hidden" name="date" value="'.h($date).'">':'');}
function sync_count(array $d):int{$n=0;foreach($d['libraries'] as $l)foreach($l['wounds'] as $w)foreach($w['updates'] as $u)$n+=count($u['photos']);return $n;}
function app_url(string $library,string $date,string $view='day'):string{return '?patient='.PATIENT_ID.'&library='.rawurlencode($library).'&date='.rawurlencode($date).'&view='.rawurlencode($view);}
function photo_count_on_date(array $l,string $ds):int{$n=0;foreach($l['wounds'] as $w)$n+=count($w['updates'][$ds]['photos']??[]);return $n;}
function latest_relevant_date(array $l):string{
    $today=gmdate('Y-m-d');$start=$l['start_date'];$latestPhoto=null;$latestUpdate=null;
    foreach($l['wounds'] as $w){foreach(($w['updates']??[]) as $ds=>$u){
        if(!is_string($ds)||$ds<$start||$ds>$today)continue;
        if(!empty($u['photos'])&&($latestPhoto===null||$ds>$latestPhoto))$latestPhoto=$ds;
        if($latestUpdate===null||$ds>$latestUpdate)$latestUpdate=$ds;
    }}
    return $latestPhoto??$latestUpdate??($start>$today?$start:$today);
}
function photo_days(array $l):array{
    $days=[];
    foreach($l['wounds'] as $w){foreach(($w['updates']??[]) as $ds=>$u){
        $photos=$u['photos']??[];if(!$photos)continue;
        $days[$ds][]=['wound'=>$w,'note'=>(string)($u['note']??''),'photos'=>$photos];
    }}
    krsort($days);return $days;
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
    $src='index.php?action=avatar';
    return '<span class="'.$class.'"><img src="'.$src.'" alt="Portrait placeholder for '.h($p['name']).'"></span>';
}
function notes_trigger(string $id,int $count,string $kind='Notes'):string{
    $has=$count>0;
    $label=$has?$kind:'No Notes';
    $aria=$has?($count===1?'1 note':$count.' notes'):'No notes';
    if($kind==='Day notes')$aria=$has?($count===1?'1 day note':$count.' day notes'):'No day notes';
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
    return '<dl class="patient-meta"><div class="patient-diagnosis"><dt>Dx</dt><dd>'.h($p['diagnosis']).'</dd></div><div><dt>Account number</dt><dd>'.h($p['account_number']).'</dd></div><div><dt>Age</dt><dd>'.$age.' year'.($age===1?'':'s').'</dd></div><div><dt>Gender</dt><dd>'.h($p['gender']).'</dd></div><div><dt>Body weight</dt><dd>'.weight_control((float)$p['weight_kg']).'</dd></div></dl>';
}
function patient_facts_compact(array $p):string{
    $age=(int)$p['age'];
    return '<div class="patient-facts">'.avatar_markup($p,'avatar compact-avatar').'<span><strong>Account number</strong> '.h($p['account_number']).'</span><span><strong>Age</strong> '.$age.' year'.($age===1?'':'s').'</span><span><strong>Gender</strong> '.h($p['gender']).'</span><span class="patient-facts-weight"><strong>Body weight</strong> '.weight_control((float)$p['weight_kg']).'</span></div>';
}
function patient_picker(array $d):string{
    $p=patient_profile($d);
    $n=count($d['libraries']);
    $href='?patient='.h($p['id']);
    $libs=$n.' progress librar'.($n===1?'y':'ies');
    return '<article class="patient-card" aria-labelledby="patient-name-'.h($p['id']).'"><a class="patient-open" href="'.$href.'">'.avatar_markup($p).'<span class="patient-name"><strong id="patient-name-'.h($p['id']).'">'.h($p['name']).'</strong><small>'.$libs.'</small></span></a>'.patient_facts($p).'<a class="patient-go" href="'.$href.'">Open record <span aria-hidden="true">→</span></a></article>';
}
function view_switch(array $l,string $date,string $view):string{
    $day=app_url($l['id'],$date,'day');$gal=app_url($l['id'],$date,'gallery');
    return '<nav class="view-switch" aria-label="Library views"><a href="'.$day.'"'.($view==='day'?' aria-current="page"':'').'>Day record</a><a data-gallery-tab href="'.$gal.'"'.($view==='gallery'?' aria-current="page"':'').'>Photo gallery</a></nav>';
}
function photo_gallery(array $photos,array $w,array $l,string $date,string $csrf):string{
    $n=count($photos);if($n===0)return '';
    $uid='angles-'.h($w['id']).'-'.h($date);
    $o='<div class="angle-set" data-mode="cycle" data-count="'.$n.'" data-date="'.h($date).'">';
    $o.='<div class="angle-stage">';
    if($n>1)$o.='<button type="button" class="ghost angle-nav prev" aria-label="Previous shot of '.h($w['name']).'">Previous</button>';
    $o.='<div class="angle-frames" id="'.$uid.'">';
    foreach($photos as $i=>$p){
        $heading=photo_heading($p,$i);
        $o.='<figure'.($i===0?' class="is-current"':' hidden').' data-index="'.$i.'"><img src="index.php?action=media&id='.h($p['id']).'&v='.$l['revision'].'" alt="'.h($heading).' of '.h($w['name']).'"><figcaption><span class="photo-label"><strong>'.h($heading).'</strong>'.($p['caption']!==''?'<small>'.h($p['caption']).'</small>':'').'</span><span class="photo-view-actions"><button type="button" class="ghost small photo-label-edit edit-only" onclick="showModal(\'photo-'.h($p['id']).'\')" aria-label="Edit name or description for '.h($heading).'">'.pencil_icon().'<span class="sr-only">Edit '.h($heading).'</span></button><button type="button" class="ghost small photo-expand" data-index="'.$i.'" aria-label="Expand '.h($heading).' of '.h($w['name']).'">Expand</button></span></figcaption></figure>';
    }
    $o.='</div>';
    $o.='<span class="next-photo-control"><button type="button" class="ghost angle-advance" aria-label="Next available photo" title="Next available photo">›</button><details class="next-options"><summary aria-label="Choose what the right chevron advances">Options</summary><div role="group" aria-label="Right chevron action"><button type="button" data-next-behavior="photo" aria-pressed="true">Next available photo <small>Default</small></button><button type="button" data-next-behavior="date" aria-pressed="false">Next date</button></div></details></span>';
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
function wound_card(array $selected,array $w,string $date,string $csrf):string{
    $u=$w['updates'][$date]??null;$photos=$u['photos']??[];$hasPhotos=count($photos)>0;$noteCount=count($w['notes']);
    $classes='wound-card '.($hasPhotos?'has-photos':'missing').($noteCount?' noted':'');
    if($u&&$hasPhotos)$state='✓ '.photo_word(count($photos));
    elseif($u)$state='○ No photos added';
    else $state='○ Not updated on this date';
    $o='<article class="'.$classes.'"><div class="wound-title"><div><p class="state">'.$state.'</p><h3>'.h($w['name']).'</h3><small>'.h($w['location']).'</small></div>'.notes_trigger('notes-'.h($w['id']),$noteCount).'</div>';
    if($u){
        $o.='<p>'.($u['note']!==''?h($u['note']):'<span class="muted">No update note</span>').'</p>';
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
function gallery_view(array $selected,string $date,string $csrf):string{
    $days=photo_days($selected);
    $o='<div id="photo-gallery-card" class="day-head" tabindex="-1"><div><p class="eyebrow">Photo gallery</p><h2>Days and wounds with pictures</h2><p class="muted">Only dates and wounds that have photos are listed.</p></div></div>';
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
            if($e['note']!=='')$o.='<p class="update-note">'.h($e['note']).'</p>';
            $o.=photo_gallery($e['photos'],$w,$selected,$ds,$csrf);
            $o.='<div class="row acc-actions"><button type="button" onclick="showModal(\'photo-add-'.h($w['id']).'-'.h($ds).'\')">Add photo</button><a class="ghost button-link" href="'.app_url($selected['id'],$ds,'day').'">Open day record</a></div></details>';
        }
        $o.='</div></details>';
    }
    return $o.'</div>';
}
function timeline(array $l,string $date,string $view='day'):string{$start=new DateTimeImmutable($l['start_date']);$end=new DateTimeImmutable('today');$cur=new DateTimeImmutable($date);$month=$cur->format('Y-m');$first=maxdate($start,new DateTimeImmutable($month.'-01'));$last=mindate($end,new DateTimeImmutable($month.'-01 last day of this month'));$out='<nav class="timeline" aria-label="Date timeline"><div class="month-nav"><a href="'.app_url($l['id'],$cur->modify('-1 month')->format('Y-m-d'),$view).'">Previous month</a><strong>'.$cur->format('F Y').'</strong><a href="'.app_url($l['id'],$cur->modify('+1 month')->format('Y-m-d'),$view).'">Next month</a><a href="'.app_url($l['id'],gmdate('Y-m-d'),$view).'">Today</a></div><div class="date-row">';for($x=$first;$x<=$last;$x=$x->modify('+1 day')){$ds=$x->format('Y-m-d');$photos=photo_count_on_date($l,$ds);$nn=count($l['day_notes'][$ds]??[]);$label=$x->format('D j').($photos?', '.photo_word($photos):', no photos').($nn?", $nn notes":'');$current=$ds===$date;$out.='<a aria-label="'.h($label).'"'.($current?' aria-current="date"':'').' class="date-chip '.($photos?'has-photos':'idle').' '.($current?'current':'').'" href="'.app_url($l['id'],$ds,$view).'"><small>'.$x->format('D').'</small><b>'.$x->format('j').'</b>'.($photos?'<span class="photo-mark" aria-hidden="true">'.$photos.'</span>':'').($nn?'<span class="note-badge date-note"><span aria-hidden="true">Notes</span><b aria-hidden="true">'.$nn.'</b><span class="sr-only">'.$nn.' notes</span></span>':'').'</a>';}$out.='</div></nav>';return $out;}
function maxdate(DateTimeImmutable $a,DateTimeImmutable $b):DateTimeImmutable{return $a>$b?$a:$b;}function mindate(DateTimeImmutable $a,DateTimeImmutable $b):DateTimeImmutable{return $a<$b?$a:$b;}
function dialog_start(string $id,string $title):string{return '<dialog id="'.$id.'" aria-labelledby="'.$id.'-title"><button type="button" class="close" onclick="this.closest(\'dialog\').close()" aria-label="Close">×</button><h2 id="'.$id.'-title">'.h($title).'</h2>';}
function photo_lightbox():string{return '<dialog id="photo-lightbox" class="photo-lightbox" aria-labelledby="lightbox-title"><button type="button" class="close lightbox-close" aria-label="Close expanded photo">×</button><h2 id="lightbox-title">Expanded photo</h2><div class="lightbox-stage"><button type="button" class="ghost lightbox-prev">Previous</button><figure><img alt=""><figcaption><strong></strong><small></small></figcaption></figure><button type="button" class="ghost lightbox-next">Next</button></div><p class="lightbox-status" aria-live="polite"></p></dialog>';}
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
function modals(array $l,string $csrf,string $date):string{$o=dialog_start('library-new','Add progress library').library_form($csrf).'</dialog>'.dialog_start('library-edit','Edit progress library').library_form($csrf,$l).'</dialog>';$o.=note_modal('notes-library','Library notes',$l['notes'],$csrf,$l,'library').note_modal('notes-day','Day notes',$l['day_notes'][$date]??[],$csrf,$l,'day',$date);$o.=dialog_start('wound-new','Add wound').wound_form($csrf,$l).'</dialog>';foreach($l['wounds'] as $w){$o.=dialog_start('wound-'.h($w['id']),'Edit '.$w['name']).wound_form($csrf,$l,$w).'</dialog>'.note_modal('notes-'.$w['id'],$w['name'].' notes',$w['notes'],$csrf,$l,'wound','',$w['id']);$addDates=[];if(!empty($w['active'])){$u=$w['updates'][$date]??null;$o.=dialog_start('update-'.h($w['id']),($u?'Edit':'Add').' update for '.$w['name']).'<form method="post">'.hidden($csrf,$l['id'],$w['id'],$date).'<input type="hidden" name="op" value="update_save"><label>Update note<textarea name="note">'.h($u['note']??'').'</textarea></label><button type="submit">Save update</button></form></dialog>';if($u)$addDates[$date]=true;}foreach(($w['updates']??[]) as $ds=>$upd){foreach(($upd['photos']??[]) as $p)$o.=photo_edit_dialog($csrf,$l,$w,$ds,$p);if(!empty($upd['photos']))$addDates[$ds]=true;}foreach($addDates as $ds=>$_)$o.=photo_add_dialog($csrf,$l,$w,$ds);}return $o;}
function library_form(string $csrf,?array $l=null):string{return '<form method="post"><input type="hidden" name="op" value="library_save"><input type="hidden" name="csrf" value="'.h($csrf).'"><input type="hidden" name="library_id" value="'.h($l['id']??'').'"><label>Name<input name="name" value="'.h($l['name']??'').'" required></label><label>Type<select name="type" required>'.implode('',array_map(fn($x)=>'<option '.(($l['type']??'')===$x?'selected':'').'>'.$x.'</option>',['Pressure Injury','Mole Monitoring','Postoperative Wound','Custom'])).'</select></label><label>Custom type (required when Custom)<input name="custom_type" value="'.h($l['custom_type']??'').'"></label><label>Starting date<input type="date" name="start_date" max="'.gmdate('Y-m-d').'" value="'.h($l['start_date']??gmdate('Y-m-d')).'" required></label><label>Description<textarea name="description">'.h($l['description']??'').'</textarea></label><button type="submit">Save library</button></form>';}
function wound_form(string $csrf,array $l,?array $w=null):string{return '<form method="post">'.hidden($csrf,$l['id']).'<input type="hidden" name="op" value="wound_save"><input type="hidden" name="wound_id" value="'.h($w['id']??'').'"><label>Name<input name="name" value="'.h($w['name']??'').'" required></label><label>Location / description<input name="location" value="'.h($w['location']??'').'"></label><label class="check"><input type="checkbox" name="active" '.(($w['active']??true)?'checked':'').'> Active (inactive history remains retained)</label><button type="submit">Save wound</button></form>';}
function css():string{return <<<'CSS'
:root{--ink:#163238;--muted:#4d6166;--brand:#0f5c69;--brand2:#164e63;--soft:#eef5f3;--line:#d7e3e0;--control:#5e7370;--focus:#0f5c69;--danger:#a12a38;--paper:#fff;--shadow:0 12px 30px rgba(22,50,56,.08)}*{box-sizing:border-box}html{background:#f4f7f6;color:var(--ink);font:15px/1.5 ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;scroll-padding-top:80px}body{margin:0;min-height:100vh}.skip-link{position:absolute;left:12px;top:-48px;z-index:100;background:#fff;color:var(--ink);padding:.55rem .9rem;border-radius:8px;font-weight:800;box-shadow:var(--shadow)}.skip-link:focus,.skip-link:focus-visible{top:12px}a{color:var(--brand);text-decoration:none}button,input,select,textarea{font:inherit}button{border:0;border-radius:9px;background:var(--brand);color:white;font-weight:750;padding:.68rem 1rem;cursor:pointer;min-height:32px}button:hover{filter:brightness(.94)}button:disabled{opacity:.35;cursor:not-allowed}button:focus-visible,a:focus-visible,input:focus-visible,textarea:focus-visible,select:focus-visible{outline:3px solid var(--focus);outline-offset:2px}.ghost{background:white;color:var(--brand);border:1px solid var(--line)}.danger{color:var(--danger)}.small{padding:.38rem .65rem;font-size:.83rem}.topbar{height:68px;padding:0 max(24px,calc((100vw - 1500px)/2));display:flex;align-items:center;justify-content:space-between;background:#fff;border-bottom:1px solid var(--line);position:sticky;top:0;z-index:10}.wordmark{display:flex;gap:11px;align-items:center;color:var(--ink);font-size:1.05rem;font-weight:800}.wordmark span,.brandmark{display:grid;place-items:center;background:var(--brand2);color:#fff;font-size:1.5rem;border-radius:10px;width:36px;height:36px}.top-actions,.row{display:flex;gap:.5rem;align-items:center;flex-wrap:wrap}.top-actions form,.row form,.head-buttons form,.photo-actions form{margin:0}.status{border-radius:99px;background:#e7f6ed;color:#17633a;padding:.35rem .7rem;font-size:.8rem;font-weight:750}.status.offline{background:#fff2dc;color:#8b4a0c}.app{max-width:1500px;margin:auto;padding:28px}.pagehead{display:flex;justify-content:space-between;margin:20px 0 26px}.pagehead h1,.library-head h1{font-size:2rem;margin:.15rem 0}.eyebrow{margin:0;color:var(--brand);font-size:.72rem;font-weight:850;letter-spacing:.11em;text-transform:uppercase}.muted{color:var(--muted)}.tiny{font-size:.78rem}.patient-card{max-width:720px;background:#fff;border:1px solid var(--line);box-shadow:var(--shadow);border-radius:16px;padding:20px 22px;display:grid;gap:14px;color:var(--ink)}.patient-open{display:flex;gap:16px;align-items:center;color:var(--ink)}.patient-name{display:flex;flex-direction:column;flex:1;min-width:0}.patient-name strong{font-size:1.2rem}.patient-go{justify-self:start;font-weight:800}.patient-meta{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px 18px;margin:0;padding:14px 0 4px;border-top:1px solid var(--line)}.patient-meta>div{min-width:0}.patient-meta dt{margin:0;color:var(--muted);font-size:.72rem;font-weight:850;letter-spacing:.08em;text-transform:uppercase}.patient-meta dd{margin:.2rem 0 0;font-weight:750;font-size:1.05rem;color:var(--ink)}.patient-facts{display:flex;flex-wrap:wrap;gap:8px 18px;align-items:center;margin:-8px 0 18px;padding:12px 14px;background:#fff;border:1px solid var(--line);border-radius:12px;color:var(--ink)}.patient-facts>span{display:flex;align-items:baseline;gap:.45rem;flex-wrap:wrap}.patient-facts strong{color:var(--muted);font-size:.72rem;letter-spacing:.08em;text-transform:uppercase}.weight-control{display:flex;align-items:center;gap:10px;flex-wrap:wrap}.weight-value{font-weight:800;font-variant-numeric:tabular-nums}.unit-toggle{display:inline-flex;border:1px solid var(--line);border-radius:999px;overflow:hidden;background:#eef4f2}.unit-btn{min-height:32px;padding:.28rem .7rem;border:0;border-radius:0;background:transparent;color:var(--muted);font-size:.8rem;font-weight:800;box-shadow:none}.unit-btn:hover{filter:none;background:#e0ece8}.unit-btn[aria-pressed="true"]{background:var(--brand);color:#fff}.unit-btn[aria-pressed="true"]:hover{filter:brightness(.94);background:var(--brand)}.library-item small,.manage-list small,.patient-name small{display:block;color:var(--muted);margin-top:3px;overflow-wrap:anywhere}.avatar{width:88px;height:110px;display:block;border-radius:12px;background:#07090b;overflow:hidden;flex:none}.avatar img{display:block;width:100%;height:100%;object-fit:cover}.compact-avatar{width:44px;height:54px;border-radius:9px}.patient-facts{align-items:center}.crumb{display:flex;gap:9px;margin:0 0 22px}.layout{display:grid;grid-template-columns:290px minmax(0,1fr);gap:28px}.sidebar{align-self:start;position:sticky;top:96px}.side-title{display:flex;justify-content:space-between;align-items:start;margin-bottom:14px}.side-title h2{margin:.25rem 0}.iconbtn{border-radius:50%;font-size:1.4rem;padding:0;width:38px;height:38px}.library-list{display:flex;flex-direction:column;gap:8px}.library-item{display:flex;justify-content:space-between;gap:6px;color:var(--ink);padding:13px;border:1px solid transparent;border-radius:11px}.library-item:hover{background:#fff}.library-item.selected{background:#fff;border-color:#b5d1cb;box-shadow:0 5px 18px rgba(22,50,56,.06)}.library-item em{align-self:start;background:#dff2ec;color:#17644f;border-radius:99px;font-size:.66rem;font-style:normal;padding:3px 6px}.sync-card{width:100%;text-align:left;margin-top:18px;padding:14px;background:#e5f0f4;color:var(--brand2);border:1px solid #bad1da}.sync-card span{display:block;font-weight:400;font-size:.78rem;margin-top:4px}.content{min-width:0}.library-head{background:#fff;border:1px solid var(--line);border-radius:16px;padding:22px;display:flex;justify-content:space-between;gap:20px;box-shadow:var(--shadow)}.noted{border-left:5px solid #bb7c20}.library-head p{color:var(--muted);margin:.4rem 0}.type{display:inline-block;background:#e6f1ef;color:#225f55;border-radius:99px;padding:4px 9px;font-size:.7rem;font-weight:800;text-transform:uppercase}.head-buttons{display:flex;gap:7px;align-items:start}.timeline{margin:22px 0;background:#fff;border:1px solid var(--line);border-radius:14px;overflow:hidden}.month-nav{padding:12px 15px;border-bottom:1px solid var(--line);display:grid;grid-template-columns:auto 1fr auto auto;gap:18px;align-items:center}.month-nav strong{text-align:center}.date-row{display:flex;align-items:flex-start;overflow-x:auto;gap:7px;padding:13px;scrollbar-width:thin}.date-chip{min-width:52px;min-height:48px;align-self:flex-start;text-align:center;border:1px solid var(--line);border-radius:9px;padding:7px 5px;color:var(--muted);background:#f3f5f4}.date-chip small,.date-chip b{display:block}.date-chip b{font-size:1.05rem}.date-chip.idle{background:#ecefed;color:#7a8b8e;border-color:#d5dddb}.date-chip.has-photos{background:#d8efe8;color:#0f4f44;border-color:#8fc4b8}.date-chip.current{outline:3px solid var(--brand);outline-offset:1px}.date-chip .photo-mark{position:absolute;top:3px;right:3px;min-width:1.15rem;height:1.15rem;padding:0 4px;border-radius:999px;background:#0f5c69;color:#fff;font-size:.58rem;font-weight:800;line-height:1.15rem}.date-chip em{font-size:.6rem;font-style:normal;font-weight:800;display:block}.day-head,.section-title{display:flex;align-items:center;justify-content:space-between;margin:25px 0 12px}.day-head h2,.section-title h2{margin:.15rem 0}.empty{display:grid;place-items:center;text-align:center;min-height:250px;background:#fff;border:1px dashed #aec3bf;border-radius:14px}.empty.compact{min-height:0;display:flex;gap:8px;align-items:center;justify-content:start;padding:14px 17px;margin-bottom:12px;color:var(--muted)}.wound-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px}.wound-card{background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px;min-width:0}.wound-card.missing{background:#e6eaea;color:#5a6b6e;border-style:dashed;border-color:#c5d0ce}.wound-card.has-photos{grid-column:1/-1;border-color:#9ec9be}.wound-grid:has(.has-photos) .wound-card.missing{grid-column:1/-1}.wound-title{display:flex;justify-content:space-between;align-items:start;gap:12px}.wound-title h3{margin:.15rem 0}.state{font-size:.72rem;text-transform:uppercase;font-weight:850;letter-spacing:.06em;color:#20705e;margin:0}.missing .state{color:#59696c}.no-photo{background:#f3f5f4;border:1px dashed #b8c5c3;border-radius:9px;padding:22px;text-align:center;color:var(--muted);margin:12px 0}.angle-set{margin:14px 0}.angle-toolbar{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin:10px 0 0}.angle-status{margin:0;font-weight:800;color:#1a4f4a}.angle-stage{display:grid;grid-template-columns:auto minmax(0,1fr) auto;gap:10px;align-items:center}.angle-frames{min-width:0}.angle-frames figure{margin:0;border:1px solid var(--line);border-radius:12px;overflow:hidden;background:#fff}.angle-set[data-mode="column"] .angle-frames{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px}.angle-set[data-mode="column"] .angle-stage{grid-template-columns:minmax(0,1fr)}.angle-frames img{display:block;width:100%;height:min(38vh,320px);object-fit:contain;background:#080a0c;cursor:zoom-in}.angle-set[data-mode="column"] .angle-frames img{height:160px}.angle-frames figcaption{padding:10px 12px;display:flex;align-items:flex-start;justify-content:space-between;gap:8px}.angle-frames figcaption span{min-width:0}.angle-frames figcaption small{display:block;color:var(--muted)}.photo-expand{flex:none}.angle-nav{min-width:44px;min-height:44px;padding:.55rem .7rem}.angle-dots{display:flex;flex-wrap:wrap;gap:6px;justify-content:center;margin-top:10px}.angle-dots[hidden]{display:none}.angle-dots button{background:#eef3f2;color:#35555a;border:1px solid var(--line);font-weight:750;padding:.4rem .7rem;min-height:36px}.angle-dots button[aria-selected="true"]{background:var(--brand);color:#fff;border-color:var(--brand)}.photo-manage{margin:8px 0 4px;border:1px solid var(--line);border-radius:10px;background:#f8fbfa}.photo-manage summary{cursor:pointer;padding:10px 12px;font-weight:750;color:var(--muted)}.photo-manage summary:focus-visible{outline:3px solid var(--focus);outline-offset:2px}.photo-manage ul{list-style:none;margin:0;padding:0 12px 12px}.photo-manage li{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;padding:8px 0;border-top:1px solid var(--line)}.photo-manage small{display:block;font-weight:500}.photo-actions{display:flex;justify-content:space-between;flex-wrap:wrap;gap:6px}.photo-actions button{padding:.45rem .55rem;font-size:.78rem;min-width:32px;min-height:32px}.manage-list{background:#fff;border:1px solid var(--line);border-radius:13px}.manage-list>div{padding:13px 15px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--line)}.manage-list>div:last-child{border-bottom:0}.toast,.alert{padding:12px 16px;border-radius:9px}.toast{position:fixed;right:25px;top:80px;background:#173f43;color:#fff;z-index:20;box-shadow:var(--shadow)}.alert{background:#fff0f1;color:#8a2430;border:1px solid #e8bbc0;margin:13px 0}.login{min-height:calc(100vh - 56px);display:grid;place-items:center;padding:24px;background:radial-gradient(circle at 20% 10%,#dcedea 0,transparent 35%),radial-gradient(circle at 90% 70%,#dfecef 0,transparent 33%)}.login-card{width:min(440px,100%);padding:36px;background:#fff;border:1px solid var(--line);border-radius:20px;box-shadow:0 24px 70px rgba(25,61,65,.13)}.login-card h1{margin:.4rem 0;font-size:2rem}.login-card form,dialog form{display:grid;gap:13px}.demo{display:block;width:100%;text-align:left;background:#eff5f4;color:var(--ink);font-weight:400;padding:13px;border-radius:9px;margin-top:18px;min-height:0}.demo:hover{filter:none;background:#e4eeec}.demo strong{display:block}.brandmark{margin-bottom:18px}label{display:grid;gap:5px;font-weight:700;color:#29484e}input,select,textarea{width:100%;border:1px solid var(--control);border-radius:8px;padding:.68rem;background:#fff;color:var(--ink)}textarea{min-height:82px;resize:vertical}.check{display:flex;align-items:center}.check input{width:auto}dialog{width:min(570px,calc(100% - 28px));max-height:88vh;overflow:auto;border:1px solid var(--line);border-radius:16px;padding:27px;color:var(--ink);box-shadow:0 30px 90px rgba(0,0,0,.25)}dialog::backdrop{background:rgba(10,29,31,.55)}dialog h2{margin-top:0}.close{float:right;background:transparent;color:var(--muted);font-size:1.4rem;padding:.2rem}.photo-lightbox{width:100vw;max-width:100vw;height:100vh;max-height:100vh;margin:0;border:0;border-radius:0;padding:16px 20px 20px;background:#11181a;color:#e8f0ee;box-shadow:none;overflow:hidden}.photo-lightbox::backdrop{background:#0a1012}.photo-lightbox h2{margin:4px 48px 10px 0;font-size:1.15rem;color:#e8f0ee}.photo-lightbox .close{color:#c5d4d1}.lightbox-stage{display:grid;grid-template-columns:auto minmax(0,1fr) auto;gap:12px;align-items:center;height:calc(100vh - 108px)}.photo-lightbox figure{margin:0;height:100%;display:grid;grid-template-rows:minmax(0,1fr) auto;min-width:0}.photo-lightbox img{width:100%;height:100%;max-height:calc(100vh - 160px);object-fit:contain;background:#07090b;border-radius:10px}.photo-lightbox figcaption{padding:10px 4px 0;display:flex;flex-direction:column;gap:2px}.photo-lightbox figcaption small{color:#9eb0ad}.lightbox-status{margin:8px 0 0;font-weight:750;color:#b7c9c5}.lightbox-prev,.lightbox-next{min-width:44px;min-height:44px;background:#1c2a2c;color:#e8f0ee;border-color:#314244}.note-list{display:flex;flex-direction:column;gap:0;margin-bottom:18px;border:1px solid var(--line);border-radius:12px;overflow:visible}.note-empty{margin:0;padding:14px 12px;color:var(--muted);text-align:center}.note-row{padding:8px 10px;border-bottom:1px solid var(--line);background:#fff}.note-row:last-child{border-bottom:0}.note-read{display:flex;align-items:flex-start;gap:8px}.note-body{flex:1;min-width:0}.note-text{margin:0;font-weight:600;line-height:1.35}.note-body time{display:block;margin-top:2px;color:var(--muted);font-size:.75rem}.note-tools{display:flex;align-items:center;gap:2px;flex:none}.note-delete-form,.dialog .note-delete-form{display:flex;margin:0;padding:0;gap:0}.note-icon-wrap{display:inline-flex;align-items:center;gap:4px}.note-icon{width:36px;height:36px;min-height:36px;padding:0;display:grid;place-items:center;background:transparent;color:var(--brand);border-radius:8px;box-shadow:none}.note-icon:hover{filter:none;background:#eef5f3}.note-icon.note-delete{color:var(--danger)}.note-icon.note-delete:hover{background:#fdecee}.note-icon svg{width:18px;height:18px;display:block}.note-tip{display:none;background:#173f43;color:#fff;font-size:.72rem;font-weight:800;line-height:1;padding:5px 8px;border-radius:6px;white-space:nowrap}.note-icon-wrap.is-open .note-tip{display:inline-block}.note-icon-wrap.is-open .note-tip:has(+ .note-delete){background:#a12a38}.note-edit-form{display:none;gap:8px;margin-top:8px}.note-row.is-editing .note-edit-form{display:grid}.note-row.is-editing .note-read{display:none}.note-edit-form textarea{min-height:72px}.sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}footer{text-align:center;color:var(--muted);font-size:.75rem;padding:20px}
.note-badge{display:inline-flex;align-items:center;gap:.32rem;border:1px solid #bcded4;border-radius:999px;background:#e5f5ef;color:#17644f;font-size:.66rem;font-style:normal;font-weight:800;line-height:1;padding:3px 4px 3px 7px;white-space:nowrap}.note-badge b{display:grid;place-items:center;min-width:1.35rem;height:1.35rem;border-radius:50%;background:#fff;color:#17644f;font-size:.64rem;line-height:1}.library-note-badge{align-self:center;flex:none}.notes-btn{display:inline-flex;align-items:center;gap:8px;flex:none;min-height:40px;padding:.48rem .72rem .48rem .95rem;border-radius:999px;font-weight:850;letter-spacing:.04em;line-height:1;box-shadow:0 6px 16px rgba(22,50,56,.08)}.notes-btn span{white-space:nowrap}.notes-btn.has-notes{background:#7a4314;color:#fff6e8;border:2px solid #7a4314}.notes-btn.has-notes:hover{filter:brightness(1.06)}.notes-btn.has-notes b{display:grid;place-items:center;min-width:1.55rem;height:1.55rem;border-radius:50%;background:#fff6e8;color:#7a4314;font-size:.82rem;line-height:1}.notes-btn.no-notes{background:#f4ead6;color:#5c3a0e;border:2px solid #b07a2c;padding-right:.95rem}.notes-btn.no-notes:hover{filter:brightness(.97);background:#efe1c4}.wound-card.missing .notes-btn{opacity:1}.date-row{padding-bottom:42px}.date-chip{position:relative}.date-chip small,.date-chip>b{display:block}.date-chip>b{font-size:1.05rem}.date-note{position:absolute;z-index:1;right:50%;bottom:-28px;transform:translateX(50%);box-shadow:0 1px 2px rgba(22,50,56,.12)}
.view-switch{display:flex;gap:4px;width:fit-content;max-width:100%;margin:18px 0 0;padding:4px;background:#fff;border:1px solid var(--line);border-radius:12px}.view-switch a{padding:.5rem .95rem;border-radius:8px;color:var(--muted);font-weight:750}.view-switch a:hover{color:var(--ink)}.view-switch a[aria-current="page"]{background:var(--brand);color:#fff}.view-switch a[aria-current="page"]:hover{color:#fff}.button-link{display:inline-flex;align-items:center;justify-content:center;border:1px solid var(--line);border-radius:9px;background:#fff;color:var(--brand);font-weight:750;padding:.68rem 1rem;min-height:32px}.photo-accordion{display:grid;gap:10px}.photo-accordion details{background:#fff;border:1px solid var(--line);border-radius:14px}.photo-accordion summary{cursor:pointer;list-style:none;display:flex;justify-content:space-between;align-items:center;gap:12px;padding:16px 18px;scroll-margin-top:140px}.photo-accordion summary:focus-visible{outline:3px solid var(--focus);outline-offset:2px}.photo-accordion summary::-webkit-details-marker{display:none}.photo-accordion summary small{display:block;font-weight:600;color:var(--muted);margin-top:2px}.photo-accordion .chev{flex:none;color:var(--brand);font-size:.85rem;line-height:1;width:auto;height:auto;border:0;transform:none}.photo-accordion .chev::before{content:'▸'}.photo-accordion details[open]>summary .chev{transform:none}.photo-accordion details[open]>summary .chev::before{content:'▾'}.photo-accordion .day-panel{padding:0 14px 16px}.wound-acc{border:1px solid var(--line);border-radius:10px;margin-top:10px;background:#f8fbfa}.wound-acc summary{padding:12px 14px}.wound-acc .update-note{margin:0 14px 8px}.wound-acc .angle-set{margin:8px 14px 12px}.acc-actions{margin:0 14px 14px}
@media(max-width:980px){.app{padding:20px}.layout{grid-template-columns:240px minmax(0,1fr);gap:18px}.wound-grid{grid-template-columns:1fr}.library-head{display:block}.head-buttons{margin-top:16px;flex-wrap:wrap}.sidebar{top:86px}}@media(max-width:700px){.topbar{height:auto;min-height:68px;padding:10px 14px;flex-wrap:wrap;gap:8px}.wordmark{font-size:.9rem;max-width:calc(100% - 8px)}.status{font-size:.72rem;padding:.3rem .55rem}.app{padding:14px}.layout{display:block}.sidebar{position:static}.library-list{flex-direction:row;overflow-x:auto}.library-item{min-width:230px}.sync-card{margin-bottom:17px}.library-head{padding:17px}.month-nav{grid-template-columns:1fr 1fr 1fr}.month-nav strong{order:-1;grid-column:1/-1}.day-head{align-items:end;flex-wrap:wrap;gap:8px}.wound-card{padding:14px}.manage-list>div{align-items:start;flex-wrap:wrap;gap:8px}.toast{left:14px;right:14px}.top-actions{width:100%;justify-content:flex-end}.login-card{padding:25px}.patient-meta{grid-template-columns:1fr}.view-switch{width:100%;position:static;top:auto}.angle-stage{grid-template-columns:1fr 1fr}.angle-frames{grid-column:1/-1;order:-1}.angle-nav{width:100%}.angle-frames img{height:min(40vh,280px)}.photo-lightbox{padding:12px}.lightbox-stage{grid-template-columns:1fr 1fr;height:calc(100vh - 118px)}.photo-lightbox figure{grid-column:1/-1;order:-1}.lightbox-prev,.lightbox-next{width:100%}}@media(min-width:701px) and (max-width:1180px){.app{padding-left:20px;padding-right:20px}.layout{grid-template-columns:minmax(220px,245px) minmax(0,1fr);gap:18px}.library-head{display:block}.library-head h1{font-size:1.65rem}.head-buttons{margin-top:14px;flex-wrap:wrap}.wound-grid{grid-template-columns:1fr}.month-nav{gap:10px}.date-row{flex-wrap:wrap;overflow:visible}.date-chip{min-width:52px}.library-item small{overflow-wrap:anywhere}}
@media(prefers-reduced-motion:reduce){*,*::before,*::after{animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important;scroll-behavior:auto!important}}
.edit-mode{display:inline-flex;align-items:center;gap:.42rem;background:#fff;color:var(--brand);border:1px solid var(--line);padding:.38rem .65rem}.edit-mode svg{width:1rem;height:1rem;fill:currentColor}.edit-mode small{font-size:.7rem;font-weight:850;color:var(--muted)}.edit-mode[aria-pressed="true"]{background:#e5f3ef;border-color:#74aea3;color:#15574f}.edit-mode[aria-pressed="true"] small{color:inherit}.edit-only{display:none!important}body.is-editing .edit-only{display:inline-flex!important}.photo-manage.edit-only{display:none!important}body.is-editing .photo-manage.edit-only{display:block!important}.photo-view-actions{display:flex;gap:6px;align-items:center;flex:none}.photo-label-edit{width:32px;min-width:32px;min-height:32px;padding:.38rem}.photo-label-edit svg{width:15px;height:15px;display:block}
.photo-accordion .chev{display:grid;place-items:center;width:44px;height:44px;border:1px solid var(--line);border-radius:9px;background:#edf5f2;font-size:1.65rem;line-height:1;color:var(--brand)}.photo-accordion .chev::before{content:'›'}.photo-accordion details[open]>summary .chev::before{content:'⌄'}.next-photo-control{display:flex;align-items:center;gap:6px}.angle-advance{min-width:48px;min-height:48px;padding:0;font-size:2rem;line-height:1}.next-options{position:relative}.next-options summary{display:grid;place-items:center;min-width:38px;min-height:38px;cursor:pointer;list-style:none;border:1px solid var(--line);border-radius:8px;background:#fff;color:var(--brand);font-size:0}.next-options summary::-webkit-details-marker{display:none}.next-options summary::before{content:'⌄';font-size:1.3rem;line-height:1}.next-options summary:focus-visible{outline:3px solid var(--focus);outline-offset:2px}.next-options>div{position:absolute;right:0;z-index:4;width:max-content;min-width:190px;margin-top:6px;padding:6px;background:#fff;border:1px solid var(--line);border-radius:10px;box-shadow:var(--shadow)}.next-options button{display:flex;width:100%;justify-content:space-between;gap:12px;background:transparent;color:var(--ink);padding:.55rem .65rem;text-align:left}.next-options button:hover{background:#edf5f2;filter:none}.next-options button[aria-pressed="true"]{color:var(--brand);font-weight:850}.next-options small{color:var(--muted);font-size:.68rem}.next-options button[aria-pressed="true"] small{color:inherit}
.patient-meta .patient-diagnosis{grid-column:1/-1}.patient-diagnosis dd{max-width:54rem}#photo-gallery-card{scroll-margin-top:88px}
CSS;}
function js():string{return <<<'JS'
const showModal=id=>document.getElementById(id)?.showModal();
const editMode=document.getElementById('editMode');
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
  set.querySelector('.angle-advance')?.addEventListener('click',()=>{
    if(rightChevronBehavior==='date'){
      const url=nextDateUrl(set.dataset.date||'');
      if(url)location.assign(url);
      else if(status)status.textContent='No later date in this timeline';
      return;
    }
    advancePhoto(set);
  });
  tabs.forEach(tab=>tab.addEventListener('click',()=>go(Number(tab.dataset.index))));
  set.querySelector('.angle-expand')?.addEventListener('click',()=>{set.dataset.mode='column';paint()});
  set.querySelector('.angle-collapse')?.addEventListener('click',()=>{set.dataset.mode='cycle';paint()});
  set.querySelectorAll('.photo-expand').forEach(btn=>btn.addEventListener('click',e=>{e.stopPropagation();openLightbox(set,Number(btn.dataset.index))}));
  set.querySelector('.angle-frames')?.addEventListener('click',e=>{if(e.target.closest('img'))openLightbox(set,Number(e.target.closest('figure')?.dataset.index||i))});
  set.addEventListener('keydown',e=>{if(document.getElementById('photo-lightbox')?.open)return;if(mode()!=='cycle'||n<2)return;if(e.key==='ArrowLeft'){e.preventDefault();go(i-1)}if(e.key==='ArrowRight'){e.preventDefault();go(i+1)}});
  set._figures=figures;set._go=go;set._index=()=>i;set._setIndex=idx=>{i=idx;paint()};
  paint();
});
let rightChevronBehavior='photo';
function nextDateUrl(date){
  const chip=[...document.querySelectorAll('.date-chip')].find(x=>{
    const d=new URL(x.href,location.href).searchParams.get('date');
    return d&&d>date;
  });
  return chip?.href||'';
}
function advancePhoto(currentSet){
  const photos=[...document.querySelectorAll('.angle-set')].flatMap(set=>(set._figures||[]).map((_,index)=>({set,index})));
  if(photos.length<2)return;
  const current=photos.findIndex(photo=>photo.set===currentSet&&photo.index===currentSet._index());
  const next=photos[(Math.max(current,0)+1)%photos.length];
  next.set._setIndex(next.index);
  if(next.set!==currentSet)next.set.scrollIntoView({block:'center',behavior:'smooth'});
}
function setRightChevronBehavior(behavior){
  rightChevronBehavior=behavior==='date'?'date':'photo';
  document.querySelectorAll('.angle-advance').forEach(button=>{
    const label=rightChevronBehavior==='date'?'Next date':'Next available photo';
    button.setAttribute('aria-label',label);button.title=label;
  });
  document.querySelectorAll('[data-next-behavior]').forEach(button=>{
    const selected=button.dataset.nextBehavior===rightChevronBehavior;
    button.setAttribute('aria-pressed',String(selected));
    if(selected)button.closest('details')?.removeAttribute('open');
  });
}
document.querySelectorAll('[data-next-behavior]').forEach(button=>button.addEventListener('click',()=>setRightChevronBehavior(button.dataset.nextBehavior)));
const lightbox=document.getElementById('photo-lightbox');
let lbSet=null,lbFigures=[],lbIndex=0;
function lightboxPaint(){
  if(!lightbox||!lbFigures[lbIndex])return;
  const fig=lbFigures[lbIndex];
  const srcImg=fig.querySelector('img');
  const title=fig.querySelector('strong')?.textContent||'Photo';
  const cap=fig.querySelector('small')?.textContent||'';
  const img=lightbox.querySelector('img');
  img.src=srcImg.currentSrc||srcImg.src;
  img.alt=srcImg.alt;
  lightbox.querySelector('#lightbox-title').textContent=title;
  lightbox.querySelector('figcaption strong').textContent=title;
  lightbox.querySelector('figcaption small').textContent=cap;
  lightbox.querySelector('.lightbox-status').textContent=lbFigures.length>1?`Shot ${lbIndex+1} of ${lbFigures.length}`:'Shot 1';
  const multi=lbFigures.length>1;
  lightbox.querySelector('.lightbox-prev').hidden=!multi;
  lightbox.querySelector('.lightbox-next').hidden=!multi;
}
function openLightbox(set,index){
  lbSet=set;
  lbFigures=[...set.querySelectorAll('.angle-frames figure')];
  lbIndex=Math.max(0,Number(index)||0);
  if(typeof set._setIndex==='function')set._setIndex(lbIndex);
  lightboxPaint();
  lightbox?.showModal();
  lightbox?.querySelector('.lightbox-close')?.focus();
}
function lightboxGo(delta){
  if(!lbFigures.length)return;
  lbIndex=(lbIndex+delta+lbFigures.length)%lbFigures.length;
  if(lbSet&&typeof lbSet._setIndex==='function')lbSet._setIndex(lbIndex);
  lightboxPaint();
}
if(lightbox){
  lightbox.querySelector('.lightbox-close')?.addEventListener('click',()=>lightbox.close());
  lightbox.querySelector('.lightbox-prev')?.addEventListener('click',()=>lightboxGo(-1));
  lightbox.querySelector('.lightbox-next')?.addEventListener('click',()=>lightboxGo(1));
  lightbox.addEventListener('click',e=>{if(e.target===lightbox)lightbox.close()});
  lightbox.addEventListener('keydown',e=>{
    if(!lightbox.open||lbFigures.length<2)return;
    if(e.key==='ArrowLeft'){e.preventDefault();lightboxGo(-1)}
    if(e.key==='ArrowRight'){e.preventDefault();lightboxGo(1)}
  });
}
const network=document.getElementById('network'); function net(){if(!network)return;network.textContent=navigator.onLine?'Online':'Offline';network.classList.toggle('offline',!navigator.onLine)} addEventListener('online',net);addEventListener('offline',net);net();
if('serviceWorker'in navigator)navigator.serviceWorker.register('index.php?action=service-worker',{scope:'./'});
const DB='skin-wound-viewer',CACHE='swcv-patient-sample-patient';
function db(){return new Promise((ok,no)=>{const r=indexedDB.open(DB,1);r.onupgradeneeded=()=>{const d=r.result;if(!d.objectStoreNames.contains('meta'))d.createObjectStore('meta');if(!d.objectStoreNames.contains('outbox'))d.createObjectStore('outbox',{keyPath:'id'})};r.onsuccess=()=>ok(r.result);r.onerror=()=>no(r.error)})}
async function metaPut(k,v){const d=await db();return new Promise((ok,no)=>{const t=d.transaction('meta','readwrite');t.objectStore('meta').put(v,k);t.oncomplete=ok;t.onerror=()=>no(t.error)})}
async function metaGet(k){const d=await db();return new Promise((ok,no)=>{const r=d.transaction('meta').objectStore('meta').get(k);r.onsuccess=()=>ok(r.result);r.onerror=()=>no(r.error)})}
const syncBtn=document.getElementById('syncButton'),syncInfo=document.getElementById('syncInfo');
async function refreshSync(){if(!syncBtn)return;const m=await metaGet('sample-patient').catch(()=>null);if(m?.complete){syncBtn.querySelector('strong').textContent='Available offline';syncBtn.querySelector('span').innerHTML='Updated '+new Date(m.at).toLocaleString()+' · <b>Update offline copy</b>';syncInfo.innerHTML='<button type="button" class="danger ghost small" id="clearCopy">Clear device copy</button>';document.getElementById('clearCopy').onclick=clearCopy}}
async function clearCopy(){if(!confirm('Remove this patient’s offline copy and pending changes from this device? Server records will remain.'))return;await caches.delete(CACHE);const d=await db();await new Promise(ok=>{const t=d.transaction(['meta','outbox'],'readwrite');t.objectStore('meta').delete('sample-patient');t.objectStore('outbox').clear();t.oncomplete=ok});location.reload()}
async function syncAll(){if(!confirm('Privacy notice: all wound records and full-size photos will be stored in this browser on this device. Browser storage is not encrypted and may be evicted. Do not continue on a shared or public device. Continue?'))return;syncBtn.disabled=true;try{if(navigator.storage?.persist)await navigator.storage.persist();const m=await fetch('index.php?action=sync-manifest').then(r=>{if(!r.ok)throw Error('Manifest unavailable');return r.json()});if(navigator.storage?.estimate){const e=await navigator.storage.estimate();if(e.quota-e.usage<m.total_bytes)throw Error('Not enough browser storage for this copy.')}const c=await caches.open(CACHE);for(const req of await c.keys())await c.delete(req);let done=0,bytes=0;syncInfo.textContent=`Syncing 0 / ${m.photo_count} photos…`;const snap=await fetch(m.snapshot_url);if(!snap.ok)throw Error('Snapshot failed');const data=await snap.clone().json();await c.put(m.snapshot_url,snap);for(const l of data.libraries){let day=new Date(l.start_date+'T12:00:00'),today=new Date();while(day<=today){const ds=day.toISOString().slice(0,10),url=`index.php?patient=sample-patient&library=${encodeURIComponent(l.id)}&date=${ds}`;const page=await fetch(url);if(!page.ok)throw Error('Offline page download failed');await c.put(url,page);day.setDate(day.getDate()+1)}}for(const x of m.media){const r=await fetch(x.url);if(!r.ok)throw Error('Photo download failed');await c.put(x.url,r.clone());done++;bytes+=x.bytes;syncInfo.textContent=`Syncing ${done} / ${m.photo_count} photos · ${(bytes/1024).toFixed(1)} KB`;await metaPut('sample-patient',{complete:false,done,total:m.photo_count})}await metaPut('sample-patient',{complete:true,at:Date.now(),revision:m.revision});syncInfo.textContent='Offline copy complete.';await refreshSync()}catch(e){syncInfo.textContent='Sync incomplete: '+e.message;await metaPut('sample-patient',{complete:false,at:Date.now()}).catch(()=>{})}finally{syncBtn.disabled=false}}
if(syncBtn)syncBtn.onclick=syncAll;refreshSync();
const pendingBtn=document.getElementById('reviewPending');
async function outboxAll(){const d=await db();return new Promise((ok,no)=>{const r=d.transaction('outbox').objectStore('outbox').getAll();r.onsuccess=()=>ok(r.result);r.onerror=()=>no(r.error)})}
async function pendingStatus(){if(!pendingBtn)return;const q=await outboxAll().catch(()=>[]);pendingBtn.hidden=!q.length;pendingBtn.textContent=`Review and sync ${q.length} change${q.length===1?'':'s'}`;if(q.length){network.textContent='Pending changes';network.classList.add('offline')}}
async function stage(form){const fd=new FormData(form),fields=[],files=[];for(const [k,v] of fd){if(v instanceof File&&v.size)files.push([k,v]);else fields.push([k,String(v)])}fields.push(['expected_revision',document.documentElement.dataset.revision]);const item={id:crypto.randomUUID(),created_at:Date.now(),fields,files};const d=await db();await new Promise((ok,no)=>{const t=d.transaction('outbox','readwrite');t.objectStore('outbox').add(item);t.oncomplete=ok;t.onerror=()=>no(t.error)});form.closest('dialog')?.close();await pendingStatus();alert('Pending sync — this change is stored on this device, not yet saved on the server.')}
document.addEventListener('submit',e=>{const form=e.target;if(!navigator.onLine&&form instanceof HTMLFormElement&&form.querySelector('[name=op]')?.value!=='logout'){e.preventDefault();stage(form).catch(x=>alert('Could not stage this change: '+x.message))}});
if(pendingBtn)pendingBtn.onclick=async()=>{const q=await outboxAll();if(!q.length)return;if(!confirm(`Review complete. Sync ${q.length} pending change(s) to the server now? Replay stops on a conflict.`))return;const d=await db();for(const item of q){const fd=new FormData();for(const [k,v] of item.fields)fd.append(k,v);for(const [k,v] of item.files)fd.append(k,v,v.name);const r=await fetch('index.php',{method:'POST',body:fd,redirect:'manual'});if(r.status===409){alert('Conflict detected. Pending changes were preserved. Reload the server version or retry after review.');break}if(!r.ok&&r.type!=='opaqueredirect'){alert('Sync stopped. Pending changes were preserved.');break}await new Promise(ok=>{const t=d.transaction('outbox','readwrite');t.objectStore('outbox').delete(item.id);t.oncomplete=ok})}await pendingStatus();if(!(await outboxAll()).length)location.reload()};pendingStatus();
JS;}
