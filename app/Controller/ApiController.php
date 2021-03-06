<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

App::uses('AuthComponent', 'Controller/Component','CakeTime', 'Utility');

class ApiController extends AppController{
    
    public $components = array('Auth', 'Session');
    
    public function beforeFilter() {
        parent::beforeFilter();
        $this->layout = 'ajax';
        $this->Auth->allow();
    }
    
    public function v1($method=''){
        if(method_exists($this, $method)){
            CakeLog::write('debug','Method Called => ' . $method);
            CakeLog::write('debug',  print_r($this->request->data,true));
            call_user_method($method, $this);
        }else{
            die(json_encode(array('error'=>'Server Error: Unknown request made!')));
        }
    }
    
    public function check(){
        
    }
    
    
    public function startLogger(){
            $json = array();
            $this->loadModel('Timesheet');
            $post = $this->request->data;
            if($this->request->is(array('post'))){
                $this->Timesheet->create();
                $data = array(
                    'user_id'       =>  $post['id'],
                    'project_id'    =>  $post['pid'],
                    'session_key'   =>  $post['sessionid'],
                    'start'         =>  $post['timestamp']
                );
                $this->timesheet = $this->Timesheet->save($data);
            }
      }
      
      public function saveActivity(){
          if($this->request->is(array('post'))){
              $this->loadModel('Timesheet');
              $this->loadModel('Activity');
                $post = $this->request->data;
                $timeframs = trim($post['timeframe'],'|');
                $timeframs = explode('|', $timeframs);
                
                $endtime = end($timeframs);
                $endtime = json_decode($endtime);
                $end_utc = CakeTime::toServer($endtime->ts,$post['tzid']);
                $timesheet = $this->Timesheet->findBySessionKey($post['sessionid']);
                if($timesheet){
                    $timesheet['Timesheet']['end'] = $endtime->ts;
                    $timesheet['Timesheet']['duration'] = (strtotime($end_utc)-strtotime($timesheet['Timesheet']['start']));
                    $this->Timesheet->save($timesheet['Timesheet']);
                    $this->Activity->saveActivities($timeframs,$timesheet['Timesheet']['id']);
                }
                echo "true";
          }
      }
      
      public function saveOffActivity(){
         
            $this->loadModel('Timesheet');
            $this->loadModel('Activity');
            $post = $this->request->data;
            $str = $post['timeframe'];
            $start = strripos($str,'|');
            $session = trim(substr($str, $start, strlen($str)),'|');
            $arr = substr($str,0, $start);
            $session_id = trim(substr($str, $start, strlen($str)),'|');
            $this->Timesheet->recursive = -1;
            $timesheet = $this->Timesheet->findBySessionKey($session_id);
            
            if($timesheet){
                $timeframes = substr($str,0, $start);
                $timeframs = explode('|', $timeframes);
                $endtime = end($timeframs); $endtime = json_decode($endtime);
                $end_utc = CakeTime::toServer($endtime->ts,$post['tzid']);
                if($endtime->ts > $timesheet['Timesheet']['end']){
                    $timesheet['Timesheet']['end'] = $endtime->ts;
                    $timesheet['Timesheet']['duration'] = (strtotime($end_utc)-strtotime($timesheet['Timesheet']['start']));
                    $this->Timesheet->save($timesheet['Timesheet']);
                }
               
                if(is_array($timeframs)){ 
                    $this->Activity->saveActivities($timeframs,$timesheet['Timesheet']['id']);
                    die("true");
                }
            }
            exit;
      }
      
      public function desksave(){
          $this->loadModel('Timesheet');
            $this->loadModel('Activity');
            $str = '{"id":2,"uid":10,"ts":"2014-10-08 21:40:38","mouseArr":6,"keysArr":0}|{"id":2,"uid":10,"ts":"2014-10-08 21:41:38","mouseArr":0,"keysArr":0}|CA2391E3C2A5499';
            $start = strripos($str,'|');
            $session = trim(substr($str, $start, strlen($str)),'|');
            $arr = substr($str,0, $start);
            $session_id = trim(substr($str, $start, strlen($str)),'|');
            $this->Timesheet->recursive = -1;
            $timesheet = $this->Timesheet->findBySessionKey($session_id);
            
            if($timesheet){
                $timeframes = substr($str,0, $start);
                $timeframs = explode('|', $timeframes);
                $endtime = end($timeframs); $endtime = json_decode($endtime);
                if($endtime->ts > $timesheet['Timesheet']['end']){
                    $timesheet['Timesheet']['end'] = $endtime->ts;
                    $timesheet['Timesheet']['duration'] = (strtotime($endtime->ts)-strtotime($timesheet['Timesheet']['start']));
                    $this->Timesheet->save($timesheet['Timesheet']);
                }
               
                if(is_array($timeframs)){ 
                    $this->Activity->saveActivities($timeframs,$timesheet['Timesheet']['id']);
                    die("true");
                }
            }
            exit;
      }


      public function stopLogger(){
            $json = array();
            $this->loadModel('Timesheet');
            $post = $this->request->data;
            if($this->request->is(array('post'))){
                $timesheet = $this->Timesheet->findBySessionKey($post['sessionid']);
                
                $end_utc = CakeTime::toServer($post['timestamp'],$post['tzid']);
                $timesheet['Timesheet']['end'] = $post['timestamp'];
                $timesheet['Timesheet']['complete'] = 1;
                $timesheet['Timesheet']['duration'] = (strtotime($end_utc)-strtotime($timesheet['Timesheet']['start']));
                $this->Timesheet->save($timesheet['Timesheet']);
                    if(isset($post['timeframe'])){
                        $timeframs = trim($post['timeframe'],'|');
                        $timeframs = explode('|', $timeframs);
                        $this->Activity->saveActivities($timeframs,$timesheet['Timesheet']['id']);
                    }
            }
      }
      
      public function saveScreen(){
          $this->loadModel('Timesheet');
          $this->loadModel('Screenshot');
          $this->Timesheet->recursive = -1;
          $post = $this->request->data;
          $timesheet = $this->Timesheet->find('first',array(
                'conditions'=>array(
                    'session_key'=>$post['sessionid'], //C87CEBE471F6C27
                    'project_id' =>$post['pid'],
                    'user_id'    =>$post['id']
                ),
                'order' =>  array('Timesheet.id' => 'desc')
              ));
          $image = 'files'.DS.'sc'.DS.date('d-m-Y').DS.$post['id'].DS;
          $path = WWW_ROOT.$image;
          if(!is_dir($path))
              mkdir($path,0777,true);
          $filename = $path . $_FILES['fileUpload']['name'];
          move_uploaded_file($_FILES['fileUpload']['tmp_name'],$filename);
          $timesheet['Timesheet']['screenshot'] = $image.$_FILES['fileUpload']['name'];
          $screeshot = array(
              'timesheet_id'    =>  $timesheet['Timesheet']['id'],
              'path'            =>  $image,
              'image'           => $_FILES['fileUpload']['name'],
              'slug'            =>  uniqid(rand(3,999)),
              'timestamp'  => CakeTime::toServer($post['timestamp'],$post['tzid'])
              
          );
          $this->Screenshot->create();
          $this->Screenshot->save($screeshot);
          die(json_encode(array('status'=>true)));
      }
    public function test(){
        $this->loadModel('Timesheet');
        $this->Timesheet->recursive = -1;
        $timesheet = $this->Timesheet->find('first',array(
                'conditions'=>array(
                    'session_key'=>'DD48F66F5FF9CFD',
                    'project_id' =>7,
                    'user_id'    =>3,
                ),
                'order' =>  array('Timesheet.id' => 'desc')
         ));
          $timesheet['Timesheet']['screenshot'] = '/test/image/path/image.jpg';
          $this->Timesheet->save($timesheet['Timesheet']);
         print_r($timesheet);
    }
    
        public function refresh(){
            $this->loadModel('Organization');
            $this->loadModel('User');
            $this->loadModel('Timesheet');
            $json=array();$nuser=array();
            if($this->request->is(array('post'))){
                $post = $this->request->data;
                $this->User->recursive = -1;
                $user = $this->User->findById($post['id']);
                $nuser = array(
                        'id'    =>  $user['User']['id'],
                        'name'    =>  $user['User']['first_name'],
                        'email' =>  $user['User']['email'],
                        'time'  => $this->Timesheet->getTimeByUser($user['User']['id']),
                        'image' => 'http://hansoftz.com/domains/logstaff/d/u/grv.jpg'
                    );
                $json['status'] = 'true';
                $json['user'] = $nuser;
                $json['org'] = $this->getUserContent($user);
            }
            CakeLog::write('debug',print_r($json,true));
            die(json_encode($json));
        }
    
        public function refreshtest(){
            $this->loadModel('Organization');
            $this->loadModel('User');
            $this->loadModel('Timesheet');
            $json=array();$nuser=array();
            
                $post = $this->request->data;
                $this->User->recursive = -1;
                $user = $this->User->findById(3);
                
                $nuser = array(
                        'id'    =>  $user['User']['id'],
                        'name'    =>  $user['User']['first_name'],
                        'email' =>  $user['User']['email'],
                        'time'  => $this->Timesheet->getTimeByUser($user['User']['id']),
                        'image' => 'http://hansoftz.com/domains/logstaff/d/u/grv.jpg'
                    );
                $json['status'] = 'true';
                $json['user'] = $nuser;
                $json['org'] = $this->getUserContent($user);
            
            die(print_r($json));
        }
        
        private function login(){
            $this->loadModel('Organization');
            $this->loadModel('Timesheet');
            $json = array();
            if ($this->request->is(array('post'))){
                $post = $this->request->data;
                $this->loadModel('User');
                $this->User->recursive = -1;
                $user = $this->User->find('first', array(
                    'conditions' => array('User.username' => $post['id'])
                ));
                if(!$user){
                    $json['status']='false';
                    $json['msg']="Invalid Username!";
                    die(json_encode($json));
                }
                if($user['User']['password'] !== AuthComponent::password($post['pswd'])) {
                     $json['status']='false';
                     $json['msg']="Invalid Username or Password!";
                     die(json_encode($json));
                }else{
                    
                    die(json_encode(array('status'=>'true','id'=>$user['User']['id'])));
                    
                    $nuser = array(
                        'id'    =>  $user['User']['id'],
                        'name'  =>  $user['User']['first_name'],
                        'email' =>  $user['User']['email'],
                        'time'  => $this->Timesheet->getTimeByUser($user['User']['id']),
                        'image' => 'http://hansoftz.com/domains/logstaff/d/u/grv.jpg'
                    );
                    $json['org'] = $this->getUserContent($user);
                    $json['user'] = $nuser;
					
					if(($json['org'])){
						 $json['status'] ="true"; 	
						 $json['msg']="Login Successfull! Loading Organisations.";
					}else{
						$json['status'] ="false"; 	
						$json['status'] ="User has no organisation."; 	
					}
                    
                }
                die(json_encode($json));
              }
              die(json_encode(array('error'=>'Not a valid action')));
      }
      
      public function getUserContent($user){
            $response = array();
            
            $organisations = $this->Organization->findWithUserId($user['User']['id']);
           
            foreach($organisations as $key=>$org){
                
                $projectsResult = $this->Organization->findOrgUsrProject($org['Organization']['id'],$user['User']['id']);
                $projects=array();
                foreach($projectsResult as $prj){
                    $projects[] = array(
                        'id'        => $prj['Project']['id'],
                        'title'     => $prj['Project']['title'],
                        'time'      => $this->Timesheet->getTimeByProject($prj['Project']['id'],$user['User']['id'])
                    );
                }
                
                $response[] = array(
                    'id'    =>  $org['Organization']['id'],
                    'name'  =>  $org['Organization']['title'],
                    'projects'=>$projects
                );
                
            }
            $this->Organization->Behaviors->load('Containable');
            $this->Organization->recursive = 2;
            return $response;
      }
   
      public function jvm2date($timestamp){
          return gmdate('Y-m-d h:i:s',$timestamp/1000);
      }


      public function testme(){
          
         
          App::uses('CakeTime', 'Utility');
	  $time = CakeTime::convert(strtotime('2015-05-15 04:45:52'), new DateTimeZone('Asia/Kolkata'));
          echo "To Server " . CakeTime::toServer('2015-05-15 04:45:52','UTC','h:i:s') . '<br/>';
	  echo "Converted to " . date('h:i:s',$time);
          
          
//          $this->loadModel('Timesheet');
//          $this->loadModel('Activity');
//          $activity = $this->Activity->findByTimesheetId(103);
//          if($activity){
//                $acts = ($activity['Activity']['gzip'])?gzuncompress($activity['Activity']['acts']):$activity['Activity']['acts'];
//                print_r($acts); exit;
//          }
      }

      

}
?>
