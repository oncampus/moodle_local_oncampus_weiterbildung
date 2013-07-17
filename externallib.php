<?php

 error_reporting(0);
 ini_set("display_errors",0);
 
 
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->libdir  . "/externallib.php");
require_once($CFG->libdir  . "/moodlelib.php");
require_once(dirname(dirname(dirname(__FILE__))).'/config.php'); // global moodle config file.
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
  
  			require_once($CFG->dirroot."/lib/weblib.php");
        require_once($CFG->dirroot."/user/lib.php");
        require_once($CFG->dirroot."/user/profile/lib.php"); //required for customfields related function
                                                             //TODO: move the functions somewhere else as
                                                             //they are "user" related
                                                             
 				require_once($CFG->dirroot."/lib/weblib.php");
        require_once($CFG->dirroot."/user/lib.php");
        require_once($CFG->dirroot."/user/profile/lib.php"); //required for customfields related function
                                                             //TODO: move the functions somewhere else as
                                                             //they are "user" related

        global $CFG, $DB;
        require_once($CFG->dirroot."/user/lib.php");
        require_once($CFG->dirroot."/user/profile/lib.php"); //required for customfields related function
                                                             //TODO: move the functions somewhere else as
                                                             //they are "user" related
                                                             
define("MOODLE_COURSEROLE_STUDENT" , 5); 			 // moodle:student
define("MOODLE_COURSEROLE_INSTRUCTOR" , 3);  	// moodle:teacher
define("MOODLE_COURSEROLE_ASSISTANT"  , 4);  	// moodle:non-editing-teachers
define("MOODLE_COURSEROLE_GUEST", 6); // moodle:gast

define("SYNCLOGFILE" , "/opt/www/weiterbildung.oncampus.de/moodle/_syncLog/synclog".date("Y-m-d",date("U")).".txt" );

syncLog("----------------------------------------------------------------------");

class local_oncampus_external extends external_api {
   
public static function isenroled_parameters() {
	
	 return new external_function_parameters(
                array(
                    'request' => new external_multiple_structure(
                            new external_single_structure(
                                    array(
                                        'coursename' => new external_value(PARAM_TEXT, 'Role to assign to the user'),
                                        'username' => new external_value(PARAM_TEXT, 'The user that is going to be enrolled'),
                                        'courserole' => new external_value(PARAM_TEXT, 'The course to enrol the user role in')
                                    )
                            )
                    )
                )
        );
    }
    public static function isenroled($request) {
    	$params = $request;
    	
    	$username = $params["username"];
    	$courseid = $params["coursename"];
    	$role = $params["courserole"];
    	try {
		    	syncLog("requesting '$username' in '$courseid' as a '$role'");
		    	$status = false;
		    	$ret = "none";
		    	$_courseid =  self::inner_get_courseid_by_coursecode($courseid);
		    	syncLog("$courseid hat internel-id: ".$_courseid);
		    	$_userid = self::inner_getMoodleuserid_by_username($username);
		    	syncLog("$username hast internel-id: ".$_userid);
		    	$r = core_enrol_external::get_users_courses($_userid);
		    	 
		    	$ret = trim(strtolower(strip_tags(get_user_roles_in_course($_userid, $_courseid))));
		    	syncLog("role found : $ret ");
		    	$status = false;
		    	
		    	if ($ret == $role) {
		    		$status = true;
		    		syncLog("match!");
		    	}
			} catch (Exception $e) {
				syncLog("Error while parsingincomings arguments!");
				syncLog($e->getMessage());
			}
    	 
    	return array("enroled" => $status, "role_found" => $ret);
    }
    public static function isenroled_returns() {
    		 return  
            new external_single_structure(
                array(
                		'enroled'			 => new external_value(PARAM_BOOL, 'success'),
                		'role_found'	 => new external_value(PARAM_TEXT, 'success')
  
                )
            );
    }
   
  # -----------------------------------------------------------------------------------------------------------------------------------------------------------
  
  /**
     * Enrolment of users
     * Function throw an exception at the first error encountered.
     * @param array $enrolments  An array of user enrolment
     * @return null
     */
	public static function courseuser_delete($enrolments) {
			global $DB, $CFG;
	
			require_once(dirname(dirname(dirname(__FILE__))).'/config.php'); // global moodle config file.

			$params = $enrolments;
			syncLog("courseuser_delete ".$params["username"]." from \"".$params["courseFullname"]."\" [".$params["coursename"]."] ");
			
			 
			
			if (empty($enrol)) {
			#  throw new moodle_exception('manualpluginnotinstalled', 'enrol_manual');
			}
			$enrolment = $params;

			// Ensure the current user is allowed to run this function in the enrolment context
			
			$MoodleCourseId = self::inner_get_courseid_by_coursecode($enrolment["coursename"]);
			$courseShort = $enrolment["coursename"];
			$username = $params["username"];
			
			syncLog("Coursecode \"".$enrolment["coursename"]."\" has moodle courseId: $MoodleCourseId ");
			$context = get_context_instance(CONTEXT_COURSE, $MoodleCourseId);
			# self::validate_context($context);
			
			$enrolment["roleid"] = $enrolment["courseRole"];
			$role = $enrolment["roleid"];
			$enrol_portal = new enrol_portal_plugin();
			$ret = $enrol_portal->unenrol_from_course($courseShort,$username);   

			$success = true;
			if ($ret == false) {
					 $success = false; 
					 syncLog("error on unenroling '$username' from course '$courseShort' ");
			}
			$error = "none";
			return array(
				"success" 	=> $success,
				"error"			=> $error
				);
				
			# $transaction->allow_commit();
	 
	}
	
	 /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
  public static function courseuser_delete_parameters() {
        return new external_function_parameters(
                array(
                    'enrolment' => new external_multiple_structure(
                            new external_single_structure(
                                    array(
                                        'roleid' => new external_value(PARAM_INT, 'Role to assign to the user'),
                                        'username' => new external_value(PARAM_TEXT, 'The user that is going to be enrolled'),
                                        'coursename' => new external_value(PARAM_TEXT, 'The course to enrol the user role in'),
                                        'courseFullname' => new external_value(PARAM_TEXT, 'The course to enrol the user role in')
                                        
                                       # 'timestart' => new external_value(PARAM_INT, 'Timestamp when the enrolment start', VALUE_OPTIONAL),
                                       # 'timeend' => new external_value(PARAM_INT, 'Timestamp when the enrolment end', VALUE_OPTIONAL),
                                       # 'suspend' => new external_value(PARAM_INT, 'set to 1 to suspend the enrolment', VALUE_OPTIONAL)
                                    )
                            )
                    )
                )
        );
    }

 
  
	
	/**
	* Returns description of method result value
	* @return external_description
	*/
	public static function courseuser_delete_returns() {
	
		return new external_single_structure(array (
        	'success'=>new external_value(PARAM_BOOL, 'Success of course creation.'),
        	'error'=>new external_value(PARAM_TEXT, 'Error description.')
		));
		
	}
    
    
     
     
   
  # -------------------------------------------------------------------------------------------------------------------------------------
     
  /**
     * Enrolment of users
     * Function throw an exception at the first error encountered.
     * @param array $enrolments  An array of user enrolment
     * @return null 
     */
public static function courseuser_update($enrolments) {
	
	global $DB, $CFG;
	require_once($CFG->dirroot."/lib/weblib.php");
	require_once($CFG->libdir . '/../enrol/locallib.php');
	require_once(dirname(dirname(dirname(__FILE__))).'/config.php'); // global moodle config file.
	
	$success = false;
	$errors = "-";
	#$params = self::validate_parameters(self::manual_enrol_users_parameters(),
	#       array('enrolments' => $enrolments));
	$params = $enrolments;
	syncLog("courseuser_update '".$params["username"]."' in \"".$params["courseFullname"]."\" [".$params["coursename"]."] as '".$params["roleid"]."' ");
	#$transaction = $DB->start_delegated_transaction(); //rollback all enrolment if an error occurs
	                                             //(except if the DB doesn't support it)
	
	//retrieve the manual enrolment plugin
	# $enrol = enrol_get_plugin('manual');
	 
	$enrolment 		= $params;
	$courseShort 	= $enrolment["coursename"];
	$username 		= $enrolment["username"];
	
	$role = $enrolment["roleid"];
	$new_role = 0;
	if ($role == "student") { 		$new_role = MOODLE_COURSEROLE_STUDENT;}
	if ($role == "instructor") { 	$new_role = MOODLE_COURSEROLE_INSTRUCTOR;}
	if ($role == "assistant") { 	$new_role = MOODLE_COURSEROLE_ASSISTANT;}
	if ($role == "guest") { 			$new_role = MOODLE_COURSEROLE_GUEST;}
	$enrolment['courseid'] = $courses[0]->id;

	$enrol_portal = new enrol_portal_plugin();
	$courseuserstatus = $enrolment["courseuserstatus"];
	syncLog("courseuser status = $courseuserstatus");
	
	#$userId = $DB->get_record('user', array('username'=>$username));
	#$courseId = $DB->get_record('course', array('idnumber'=>$courseShort));
	#$context = context_course::instance($course->id);
	#syncLog("$username userid = ".$userId);
	#syncLog("course '$courseShort' hat courseId: ".$courseId);
	#syncLog("contextId = $contextId "); 
		
		
	#$transaction = $DB->start_delegated_transaction();
	
		try {
			$arg = $enrol_portal->enrol_update_role($courseShort,$username,$new_role, $courseuserstatus);   
			if ($arg == true) { syncLog("enrol_update_role() = true"); }
			if ($arg == false) { syncLog("enrol_update_role() = false"); }
			
			$success = true;
			$errors = "alles ok";
		 syncLog("updates proceed!");
		} catch (Exception $e) {
			$success = false;
			$errors = "Error while enrol_update_role()";
		}
 
	 #$transaction->allow_commit();
	
		$resultarray =  array (
	        'success'=> $success,
        	'errors' => $errors,
        	'id'		 => 12,
        	'username' => "wurst"
		);
	 #syncLog(gettype($resultarray));
		return $resultarray;

}

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function courseuser_update_returns() {
      
      return  
            new external_single_structure(
                array(
                		'success'	 => new external_value(PARAM_BOOL, 'success'),
                	  'errors'	 => new external_value(PARAM_TEXT, 'errors'),
                    'id'       => new external_value(PARAM_INT,  'user id'),
                    'username' => new external_value(PARAM_TEXT, 'user name')
                )
            );
		 
    }
    
	
	
 public static function courseuser_update_parameters() {
        return new external_function_parameters(
                array(
                    'enrolment' => new external_multiple_structure(
                            new external_single_structure(
                                    array(
                                        'roleid' => new external_value(PARAM_INT, 'Role to assign to the user'),
                                        'username' => new external_value(PARAM_TEXT, 'The user that is going to be enrolled'),
                                        'coursename' => new external_value(PARAM_TEXT, 'The course to enrol the user role in'),
                                        'courseFullname' => new external_value(PARAM_TEXT, 'The course to enrol the user role in'),
                                        'courseuserstatus' => new external_value(PARAM_TEXT, 'The course to enrol the user role in')
                                        
                                       # 'timestart' => new external_value(PARAM_INT, 'Timestamp when the enrolment start', VALUE_OPTIONAL),
                                       # 'timeend' => new external_value(PARAM_INT, 'Timestamp when the enrolment end', VALUE_OPTIONAL),
                                       # 'suspend' => new external_value(PARAM_INT, 'set to 1 to suspend the enrolment', VALUE_OPTIONAL)
                                    )
                            )
                    )
                )
        );
    }

   

    
    
     
    
  
    
     
   
   
   
   
   
     
     
   
  # -------------------------------------------------------------------------------------------------------------------------------------
     
  /**
     * Enrolment of users
     * Function throw an exception at the first error encountered.
     * @param array $enrolments  An array of user enrolment
     * @return null
     */
    public static function courseuser_insert($enrolments) {
        global $DB, $CFG;
 
        #require_once($CFG->libdir . '/enrollib.php');
				require_once(dirname(dirname(dirname(__FILE__))).'/config.php'); // global moodle config file.

        #$params = self::validate_parameters(self::manual_enrol_users_parameters(),
         #       array('enrolments' => $enrolments));
				$params = $enrolments;
					syncLog("courseuser_insert ".$params["username"]." in \"".$params["courseFullname"]."\" [".$params["coursename"]."] as '".$params["roleid"]."' ");
        # $transaction = $DB->start_delegated_transaction(); //rollback all enrolment if an error occurs
      
				$enrolment = $params;
				 
      
            // Ensure the current user is allowed to run this function in the enrolment context
            
            $MoodleCourseId = self::inner_get_courseid_by_coursecode($enrolment["coursename"]);
            $courseShort = $enrolment["coursename"];
            $username = $enrolment["username"];
            
            syncLog("Coursecode \"".$enrolment["coursename"]."\" has moodle courseId: $MoodleCourseId ");
            $context = get_context_instance(CONTEXT_COURSE, $MoodleCourseId);
            # self::validate_context($context);
				    
				   
            //check that the user has the permission to manual enrol
            # require_capability('enrol/manual:enrol', $context);
						# $enrolment["roleid"] = $enrolment["courseRole"];
						$role_cmp = $enrolment["roleid"];
						$role = MOODLE_COURSEROLE_STUDENT;
						
						if ($role_cmp == "student") { 		$role = MOODLE_COURSEROLE_STUDENT;}
						if ($role_cmp == "instructor") { 	$role = MOODLE_COURSEROLE_INSTRUCTOR;}
						if ($role_cmp == "assistant") { 	$role = MOODLE_COURSEROLE_ASSISTANT;}
						if ($role_cmp == "guest") { 	$role = MOODLE_COURSEROLE_GUEST;}
						
          
	    $enrolment['courseid'] = $courses[0]->id;
	  
	  	$success = false;
	  	$errors = "no";
	    try {
	    $enrol_portal = new enrol_portal_plugin();
			$arg = $enrol_portal->enrol_to_course($courseShort,$username,$role);   
			$success = true;
			if ($arg == false) { syncLog("enrol_to_course -- error "); 
				$success = false;
			}
			
			
			syncLog("courseuserinsert proceed");
		} catch (Exception $e) {
			
				$success = false;
				
				syncLog("error while courseuserinsert");
		}
        
        # $transaction->allow_commit();
        
        
     #    public static function isenroled($request) {
    #	$params = $request;
    #	
    #	$username = $params["username"];
    #	$courseid = $params["coursename"];
    #	$role = $params["courserole"];
    	
    	                                                       //they are "user" related
                                                             
 
    	if ($role == 5) { $role_2 = "student";}
    	if ($role == 3) { $role_2 = "teacher";}
    	if ($role == 4) { $role_2 = "non-editing-teacher";}
    	
    	$reqArgs = ( array (
    		"username" => $username,
    		"coursename" => $courseShort,
    		"courserole"		 => $role_2
    		));
    		
    	$check = self::isenroled($reqArgs);
    	syncLog("checking '$username' in '$courseShort' ?");
    	if ($check["enroled"] == true) {
    		syncLog("found, ok!");
    		$success = true;
    	} else {
    		syncLog("NOT found, ERROR!");
    		$success = false;
    	}
    	
       $errors = "illi".rand(10,99);
        $res =   array (
						"success" =>  $success ,
						"errors"		=> $errors
				);
			 
				return $res;
    }
    
 public static function courseuser_insert_parameters() {
        return new external_function_parameters(
                array(
                    'enrolment' => new external_multiple_structure(
                            new external_single_structure(
                                    array(
                                        'roleid' => new external_value(PARAM_INT, 'Role to assign to the user'),
                                        'username' => new external_value(PARAM_TEXT, 'The user that is going to be enrolled'),
                                        'coursename' => new external_value(PARAM_TEXT, 'The course to enrol the user role in'),
                                        'courseFullname' => new external_value(PARAM_TEXT, 'The course to enrol the user role in')
                                        
                                       # 'timestart' => new external_value(PARAM_INT, 'Timestamp when the enrolment start', VALUE_OPTIONAL),
                                       # 'timeend' => new external_value(PARAM_INT, 'Timestamp when the enrolment end', VALUE_OPTIONAL),
                                       # 'suspend' => new external_value(PARAM_INT, 'set to 1 to suspend the enrolment', VALUE_OPTIONAL)
                                    )
                            )
                    )
                )
        );
    }

   

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function courseuser_insert_returns() {
       
       return  
            new external_single_structure(
                array(
                		'success'	 => new external_value(PARAM_BOOL, 'success'),
                	  'errors'	 => new external_value(PARAM_TEXT, 'errors')
                   
                )
            );
       
       
    }
    
    
     
    
  
    
    
    
    
	 # --------------------------------------------------------------------------------------------------------------------------------------------   



/**
	 * 
	 * Returns welcome message
	 * @return string welcome message
	 */
	public static function updatecourse($course) {
	
		global $USER,$CFG,$DB;
		 
   
	$params = self::validate_parameters(self::updatecourse_parameters(), array('course'=>$course));
 
	$courseID 		= $course[0]["idnumber"];
	$idNumber 		= $course[0]["idnumber"];
	$shortname 		= $course[0]["shortname"];
	$fullname 		= $course[0]["fullname"];
	$startdate		= $course[0]["coursestart"];
	$visible   		= $course[0]["visible"];
	
	$courseid=0;
	$success=false;
	$error=''; 
	syncLog("courseID $courseID ");
	
	 $targetID = $DB->get_field('course', 'id', array('idnumber'=>$courseID));
	 
	if (!is_numeric($targetID) || $targetID <=1) {
		# todo error
		syncLog("update-course error - course $idNumber not found / targetID = $targetID ");
	} else {
		syncLog("targetID $targetID ");
		
		
		$args = array(
			  	'id'				=> $targetID,
			    'shortname' => $shortname,
			    'fullname' 	=> $fullname,
			    'visible'		=> $visible
					);
					
		if ($startdate > 0) {
			$args["startdate"] = $startdate;
			syncLog("startdate $startdate ");
		}
		
		
		$DB->update_record('course', $args);
		
		$success = true;
		
	}
		return array(
	        'success'=>$success,
    	    'courseid'=>$courseid,
        	'error'=>$error
		);

	}

	/**
	 * Returns description of method result value
	 * @return external_description
	 */
	public static function updatecourse_returns() {
		return new external_single_structure(array (
        	'success'=>new external_value(PARAM_BOOL, 'Success of course creation.'),
        	'courseid'=>new external_value(PARAM_INT, 'The new courseid.'),
        	'error'=>new external_value(PARAM_TEXT, 'Error description.')
		));
	}
	 
	                     
	public static function updatecourse_parameters() {
		return new external_function_parameters(
			 array(
	        'course' => new external_multiple_structure(
	            new external_single_structure(
	                array(
		                'idnumber' => new external_value(PARAM_TEXT, 'IDNumber of course. By default it is ""', VALUE_DEFAULT, ''),
		                'shortname' => new external_value(PARAM_TEXT, 'IDNumber of course. By default it is ""', VALUE_DEFAULT, ''),
		                'fullname' => new external_value(PARAM_TEXT, 'IDNumber of course. By default it is ""', VALUE_DEFAULT, ''),
		                'summary' => new external_value(PARAM_TEXT, 'IDNumber of course. By default it is ""', VALUE_DEFAULT, ''),
		                'lang' => new external_value(PARAM_TEXT, 'IDNumber of course. By default it is ""', VALUE_DEFAULT, ''),
		                'coursestart' => new external_value(PARAM_TEXT, 'IDNumber of course. By default it is ""', VALUE_DEFAULT, ''),
		                'visible' => new external_value(PARAM_TEXT, 'IDNumber of course. By default it is ""', VALUE_DEFAULT, '')
		              
		               
									)
								)
							)
					)
			 );			
	}
	
	
 # --------------------------------------------------------------------------------------------------------------------------------------------   
	
	
	
  
                             
	/**
	 * 
	 * Returns welcome message
	 * @return string welcome message
	 */
	public static function deletecourse($course) {
	
		global $USER,$CFG,$DB;
		
		
		//Parameter validation
		//REQUIRED
		$idnumber = array();
		 
		 /*
		$params = self::validate_parameters(self::createcourse_parameters(),
		array( "course" => array ( 
               
                'idnumber' => $idnumber
                 )
		));
  */
   
	 $params = self::validate_parameters(self::deletecourse_parameters(), array('course'=>$course));
 
	$courseID = $params[0]["idnumber"];

	#$course = new stdClass();
	#$course->idnumber = $CourseID;
	
	$params = self::validate_parameters(self::deletecourse_parameters(), array('course'=>$course));
	$buildcourse = $course[0];
	$params = $buildcourse;
  $courseID = $params["idnumber"];
  $targetID = $DB->get_field('course', 'id', array('idnumber'=>$courseID));
  $targetCategory = $DB->get_field('course', 'category', array('idnumber'=>$courseID));
  
  
	$courseid=0;
	$success=false;
	$error=''; 
		
		
  if (is_numeric($targetID) && $targetID >= 1) {
  	#$delete = new stdClass();
  	#$delete->id = $targetID();
  	$targetCategory = $DB->get_field('course', 'category', array('idnumber'=>$courseID));
  	$result = delete_course($targetID, true);
  	if ($result) {
  		syncLog(" \"$courseID\" deleted!");
  		$success = true;
  		
  		$courseCount = $DB->get_field('course_categories','coursecount', array ('id' => $targetCategory) );
  		$newCourseCount = $courseCount -1;
  		#$DB->set_field('course_categories', 'path', '/' . $categoryid, array('id'=>$categoryid));
  		$DB->set_field('course_categories' , 'coursecount' ,$newCourseCount , array ('id' => $targetCategory));
  		syncLog("new coursecount for course_category $categoryid is $newCourseCount ");
  		
  	} else {
  		#syncLog("ERROR on coursedelete \"$courseID\" ");
  		$error = "no course with this idnumber found";
  	}
  	
  } else {
  	syncLog("ERROR on deleting Course \"$courseID\" [$targetID] - no course found");
  		$error = "no course with this idnumber found";
  }
   


		return array(
	        'success'=>$success,
    	    'courseid'=>$courseid,
        	'error'=>$error
		);

	}



	public static function inner_get_courseid_by_coursecode($code) {
		global $DB;
		 $targetID = $DB->get_field('course', 'id', array('idnumber'=>$code));
		 return $targetID;
		}

	/**
	 * Returns description of method result value
	 * @return external_description
	 */
	public static function deletecourse_returns() {
		return new external_single_structure(array (
        	'success'=>new external_value(PARAM_BOOL, 'Success of course creation.'),
        	'courseid'=>new external_value(PARAM_INT, 'The new courseid.'),
        	'error'=>new external_value(PARAM_TEXT, 'Error description.')
		));
	}
	 
	                     
	public static function deletecourse_parameters() {
		return new external_function_parameters(
			 array(
	        'course' => new external_multiple_structure(
	            new external_single_structure(
	                array(
		                'idnumber' => new external_value(PARAM_TEXT, 'IDNumber of course. By default it is ""', VALUE_DEFAULT, '')
		               
									)
								)
							)
					)
			 );			
	}
	
	
 # --------------------------------------------------------------------------------------------------------------------------------------------   
	
	                       
	public static function createcourse_parameters() {
		return new external_function_parameters(
			 array(
	        'course' => new external_multiple_structure(
	            new external_single_structure(
	                array(
		                'categoryname' => new external_value(PARAM_TEXT, 'Name of course category. By default it is "Miscellaneous"', VALUE_DEFAULT, 'Miscellaneous'),
		                'fullname' => new external_value(PARAM_TEXT, 'Fullname of course. By default it is "New course"', VALUE_DEFAULT, 'New course'),
		                'shortname' => new external_value(PARAM_TEXT, 'Shortname of course. By default it is "New course"', VALUE_DEFAULT, 'New course'),
		                'summary' => new external_value(PARAM_TEXT, 'Shortname of course. By default it is "New course"', VALUE_DEFAULT, 'New course'),
		                'idnumber' => new external_value(PARAM_TEXT, 'IDNumber of course. By default it is ""', VALUE_DEFAULT, ''),
		                'startdate' => new external_value(PARAM_TEXT, 'IDNumber of course. By default it is ""', VALUE_DEFAULT, ''),
		                'backupfile' => new external_value(PARAM_TEXT, 'Backupfile for course. By default it is "default.mbz"', VALUE_DEFAULT, 'default.mbz'),
		                'visible' => new external_value(PARAM_TEXT, 'Backupfile for course. By default it is "default.mbz"', VALUE_DEFAULT, 'default.mbz'),
		                'lang' => new external_value(PARAM_TEXT, 'Backupfile for course. By default it is "default.mbz"', VALUE_DEFAULT, 'default.mbz'),
		                'ir' => new external_value(PARAM_TEXT, 'Backupfile for course. By default it is "default.mbz"', VALUE_DEFAULT, 'default.mbz'),
		                'coursetemplate_filetype' => new external_value(PARAM_TEXT, 'Backupfile for course. By default it is "default.mbz"', VALUE_DEFAULT, 'default.mbz')
		                
		                
									)
								)
							)
					)
			 );			
	}

                               
	/**
	 * 
	 * Returns welcome message
	 * @return string welcome message
	 */
	public static function createcourse($course) {
	
	$categoryname = 'Miscellaneous';
	$fullname= 'New course';
	$shortname= 'New course';
	$idnumber='';
	$backupfile='default.mbz'; 
		
		
		 
		global $USER,$CFG,$DB;

		//Parameter validation
		//REQUIRED
		
		 /*
		$params = self::validate_parameters(self::createcourse_parameters(),
		array( "course" => array ( 
                'categoryname' => $categoryname,
                'fullname' => $fullname,
                'shortname' => $shortname,
                'idnumber' => $idnumber,
                'backupfile' => $backupfile  )
		));
   */
   
	  $params = self::validate_parameters(self::createcourse_parameters(), array('course'=>$course));
	
	$buildcourse = $course[0];
	
	$params = $buildcourse;
  $template = $params["backupfile"];
 
	 
	 
		//Context validation
		//OPTIONAL but in most web service it should present
		$context = get_context_instance(CONTEXT_USER, $USER->id);
		self::validate_context($context);
	  syncLog("creating course \"".$buildcourse["fullname"]."\"  ");
	  syncLog("lang = ".$buildcourse["lang"]);
	  
		//Capability checking
		//OPTIONAL but in most web service it should present
		if (!has_capability('moodle/course:create', $context)) {
			# throw new moodle_exception('cannotcreatecourse');
		}

		$courseid=0;
		$success=false;
		$error='';


				$restore_idnumber			=	$params['idnumber'];
				$restore_shortname		=	$params['shortname'];
				$restore_fullname			=	$params['fullname'];
				$restore_startdate		=	$params['startdate'];
				$restore_visible			=	$params['visible'];
				$restore_lang					=	$params['lang'];
				$restore_categoryid		=	$params["categoryname"];
				$restore_ir 					= $params["ir"];
				
				$restore_coursetemplate_filetype = $params['coursetemplate_filetype'];
				
				syncLog("restore__idnumber :".$restore_idnumber);
				syncLog("received IR:".$restore_ir." for coursetemplate_filetype: ".$restore_coursetemplate_filetype);
				
	
		 
									$coursetemplatedir=$CFG->dataroot.'/coursetemplates/';
									$coursetemplatefile=$coursetemplatedir."ir".$params['ir'].".".$restore_coursetemplate_filetype;
									$ir_token = str_pad($params["ir"],3,"0", STR_PAD_LEFT);
									$check1 = $coursetemplatedir."ir".$ir_token.".mbz";
									$check2 = $coursetemplatedir."ir".$ir_token.".zip";
									syncLog("mbz,zip or default ?");
									syncLog("check1 : $check1");
									syncLog("check2 : $check2 ");
									$found = false;
									$default = $coursetemplatedir."course-default-".$restore_lang.".zip";
									
									if (is_file($check1)) {
										$found = true;
										syncLog("[mbz] $check1 found!");
										$coursetemplatefile = $check1;
										$restore_coursetemplate_filetype = "mbz";
									}
									if ($found == false && is_file($check2)) {
										$found = true;
										syncLog("[zip} $check2 found!");
										$coursetemplatefile = $check2;
										$restore_coursetemplate_filetype = "zip";
									}
									if ($found == false) {
										
										$coursetemplatefile = $default;
										synclog("no .zip|.mbz found, using default ==> [zip] $default");
										$restore_coursetemplate_filetype = "zip";
									}
										
										
								 	#$coursetemplatefile = $default;
								 	#$restore_coursetemplate_filetype = "zip";
									
									
									
									
									
									if ($restore_coursetemplate_filetype == "mbz") {
										if (is_file($coursetemplatefile)) {
											try {
											
												// extract course template file
												$rand = $USER->id;
												while (strlen($rand) < 10) {
												$rand = '0' . $rand;
												}
												$rand .= rand();
												check_dir_exists($CFG->dataroot . '/temp/backup');
												$zp=new zip_packer();
												$to=$CFG->dataroot . '/temp/backup/'.$rand;
												syncLog("tmp-dir = $to ");
												
												$extracted=$zp->extract_to_pathname($coursetemplatefile,$to);
												
												
												
												// get category id or create course category if not exists
												$restore_categoryname=$params['categoryname'];
												
												$categoryid = $DB->get_field('course_categories', 'id', array('name'=>$restore_categoryname));
												if (!$categoryid) {
												$categoryid = $DB->insert_record('course_categories', (object)array(
												'name' => $restore_categoryname,
												'parent' => 0,
												'visible' => 1
												));
												$DB->set_field('course_categories', 'path', '/' . $categoryid, array('id'=>$categoryid));
												}
												
												 
												if ($restore_idnumber=='') {
												$course_exists=false;
												} else {
												$course_exists = $DB->get_field('course', 'id', array('idnumber'=>$restore_idnumber));
												
												}
												if (!$course_exists) {
												syncLog("course does not exists -- ".$restore_idnumber);
												
												syncLog("restore_fullname = $restore_fullname ");
												syncLog("restore_shortname = $restore_shortname ");
												syncLog("restore_categoryid = $restore_categoryid ");
												try {
												$courseid = restore_dbops::create_new_course($restore_fullname, $restore_shortname, $categoryid);
												} catch (Exception $e) {
												syncLog($e->getMessage());
												}
												
												// Restore backup into course
												$controller = new restore_controller($rand, $courseid,
												backup::INTERACTIVE_NO, backup::MODE_SAMESITE, $USER->id,
												backup::TARGET_NEW_COURSE);
												$controller->get_logger()->set_next(new output_indented_logger(backup::LOG_INFO, false, true));
												$controller->execute_precheck();
												$controller->execute_plan();
												
												// Set shortname and fullname back!
												$DB->update_record('course', (object)array(
												'id' => $courseid,
												'idnumber' => $restore_idnumber,
												'shortname' => $restore_shortname,
												'fullname' => $restore_fullname,
												'startdate' => $restore_startdate,
												'visible'		=> $restore_visible,
												'lang'		  => $restore_lang
												));
												
												$success=true;
												} else {
												syncLog("course with same idnumber allready exists");
												$error.='course with same idnumber allready exists.';
												}
												
												$res = rrmdir($to);
												syncLog($res);
											} catch (Exception $e) {
												$error.='error while restore.';
												syncLog("ERROR while unzipping!");
											}
											
										} else {
											$error.='course template file does not exist.';
										}
									} 
									
									
									
									
										if ($restore_coursetemplate_filetype == "zip") {
											
									
				try {
					//ob_start();
					
					$restore_user_id=1; // ID des Users der den Restore durchführt
					$restore_user_id = 2;
					
					check_dir_exists($CFG->dataroot . '/temp/backup');
					$uniqid = uniqid();
					
			
					
					$from=$coursetemplatefile;
					$to=$CFG->dataroot . '/temp/backup/'.$uniqid;
					syncLog("Entpackte Backup Datei");
					syncLog("from: $from");
					syncLog("to: $to");
					$zp=new zip_packer();
					$extracted=$zp->extract_to_pathname($from,$to);		

					$coursefile= $CFG->dataroot . '/temp/backup/'.$uniqid.'/moodle.xml';
					if (file_exists($coursefile)) {
						$coursefile_content = file_get_contents($coursefile);

						$course_info = new SimpleXMLElement($coursefile_content);
						//var_dump($course_info);
						$old_course_id=$course_info["id"];
						$old_course_idnumber=$course_info->idnumber;
						$old_shortname=$course_info->shortname;
						$old_fullname=$course_info->fullname;
						$old_category_id=(string)$course_info->category["id"];
						$old_category_name=$course_info->category->name;
						
						
					
						if (trim($old_course_idnumber)=='') {
							$old_course_idnumber=uniqid();
							syncLog('Leere idnumber auf neuen Wert gesetzt: '.$old_course_idnumber);
						}						
						
					 
					
					#$restore_categoryid		=	$params["categoryname"];
							$categoryid = $DB->get_field('course_categories', 'id', array('name'=>$restore_categoryid));
							if (!$categoryid) {
								syncLog('Kategorie mit dem Namen '.$restore_categoryid.' nicht gefunden');
								$categoryid = $DB->insert_record('course_categories', (object)array(
						        'name' => $restore_categoryid,
						        'parent' => 0,
						        'visible' => 1
								));
								$DB->set_field('course_categories', 'path', '/' . $categoryid, array('id'=>$categoryid));
								syncLog('Kategorie mit dem Namen '.$restore_categoryid.' wurde angelegt');	
								syncLog('ID der neuen Kategorie '.$categoryid);
							} else {
								syncLog('Kategorie mit den Namen '.$restore_categoryid. ' gefunden');
							syncLog('ID der Kategorie: '.$categoryid);	
							}
									
						/*
						$categoryid = $DB->get_field('course_categories', 'id', array('name'=>$restore_categoryname));
												if (!$categoryid) {
												$categoryid = $DB->insert_record('course_categories', (object)array(
												'name' => $restore_categoryid,
												'parent' => 0,
												'visible' => 1
												));
												#$DB->set_field('course_categories', 'path', '/' . $categoryid, array('id'=>$categoryid));
												}
					 */
					 
						$restore_course=true;
						syncLog('Ueberpruefen ob Kurs mit idnumber '.$old_course_idnumber. ' existiert');
						$course_exists = $DB->get_field('course', 'id', array('idnumber'=>$old_course_idnumber));
						if ($course_exists) {
							
							syncLog('Kurs existiert bereits');
							$modified = $DB->get_field('course', 'timemodified', array('id'=>$course_exists));
							if ($old_timemodified>$modified) {
								syncLog('Kurs aus Backup ist neuer -> Kurs wird ueberschrieben');
								$target=backup::TARGET_EXISTING_DELETING;		
							} else {
								syncLog('Kurs aus Backup nicht neuer -> keine Wiederherstellung');
								$restore_course=false;								
							}
						} else {
							syncLog('Kurs existiert noch nicht');
							$target=backup::TARGET_NEW_COURSE;
						}
						if (intval($old_course_id)==1) {
						syncLog('Kurs mit ID=1 nicht wiederherstellen');
							$restore_course=false;
						}	

						
						 
						if ($restore_course) {
						syncLog('Wiederherstellung durchfuehren');
							
							#$restore_category_id=$categoryid;
							#$restore_shortname=$old_shortname;
							#$restore_fullname=$old_fullname;	
							#$restore_idnumber=$old_course_idnumber;						
							#syncLog('Kurs erstellen');
							#syncLog('idnumber : '.$restore_idnumber);
							#syncLog('shortname : '.$restore_shortname);
							#syncLog('fullname : '.$restore_fullname);
							syncLog('category-id : '.$restore_categoryid);
							
							if ($course_exists) {
								$restore_courseid=$course_exists;
								syncLog('Kurs mit der ID '.$restore_courseid.' wird ueberschrieben');
							} else {
								$restore_courseid = restore_dbops::create_new_course($restore_fullname, $restore_shortname, $categoryid);
								syncLog('Kurs angelegt - neue ID: '.$restore_courseid);
							}
							syncLog('Backup einspielen ');
							$controller = new restore_controller($uniqid, $restore_courseid,backup::INTERACTIVE_NO, backup::MODE_IMPORT, $restore_user_id,$target);
							$controller->get_logger()->set_next(new output_indented_logger(backup::LOG_INFO, false, true));
							
							$controller->set_status(backup::STATUS_REQUIRE_CONV);
							$controller->convert();
							$controller->execute_precheck();
							
							/*
							 if (!$controller->execute_precheck()) {
            					$precheckresults = $controller->get_precheck_results();
            					print_r($precheckresults);
							 }
							*/
							
							$controller->execute_plan();
							syncLog('Backup eingespielt ');
							
							syncLog('Kursnamen und ID nach Backup zuruecksetzen ');
							$DB->update_record('course', (object)array(
						    'id' => $restore_courseid,
							'idnumber' => $restore_idnumber,
						    'shortname' => $restore_shortname,
						    'fullname' => $restore_fullname
							));
							syncLog('ok');
							$success = true;
						}
						
						
						
						
						
					} else {
						syncLog("ERROR missing '$coursefile'");
					 
					}							
					
					
					syncLog('Wiederherstellung der Datei '.$file.' abgeschlossen');
					
					syncLog('Temp-Verzeichnis loeschen');
					rrmdir($to);
					syncLog('Temp-Verzeichnis geloescht');					
					
					
					
					} catch (Exception $e) {
				syncLog("Error: couold not import coursebackup!");
				syncLog("Exception: ".$e->getMessage());
					 
				}
			

											
										} // endif =="zip"
									
									
									
		// return $params['categoryname'] . ':' . $params['fullname'] . ':'. $params['shortname'] . ':'. $params['idnumber'] . ':'. $coursetemplatedir . $params['backupfile'] . ':'.$USER->firstname ;

		return array(
	        'success'=>$success,
    	    'courseid'=>$courseid,
        	'error'=>$error
		);

	}

	/**
	 * Returns description of method result value
	 * @return external_description
	 */
	public static function createcourse_returns() {
		return new external_single_structure(array (
        	'success'=>new external_value(PARAM_BOOL, 'Success of course creation.'),
        	'courseid'=>new external_value(PARAM_INT, 'The new courseid.'),
        	'error'=>new external_value(PARAM_TEXT, 'Error description.')
		));
	}
	
	
	
	
	
 # --------------------------------------------------------------------------------------------------------------------------------------------   
	
	
	
		
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function delete_users_parameters() {
        return new external_function_parameters(
            array(
                'usernames' => new external_multiple_structure(new external_value(PARAM_TEXT, 'usernames')),
            )
        );
    }
 
    /**
     * Delete users
     * @param array $userids
     * @return null
     */
    public static function delete_users($usernames) {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot."/user/lib.php");
				 
        // Ensure the current user is allowed to run this function
        $context = get_context_instance(CONTEXT_SYSTEM);
        require_capability('moodle/user:delete', $context);
        self::validate_context($context);

        $params = self::validate_parameters(self::delete_users_parameters(), array('usernames'=>$usernames));

        $transaction = $DB->start_delegated_transaction();
				
				$success = false;
        $username = $params['usernames'][0];
        
        	syncLog("deleting user ".$username);
        	try {
           $user = $DB->get_record('user', array('username'=>$username, 'deleted'=>0), '*', MUST_EXIST);

          } catch (Exception $e) {
          	syncLog("ERROR: no user with username = $username ");
          }
          
	           try {
	            $t = user_delete_user($user);
	            if (!$t) {$success = false;} else { $success = true;}
	         
	          } catch (Exception $e) {
	          $success = false;
	          }
         
        

        $transaction->allow_commit();
				 
				$result = array ( "success" => $success );
			 
        return $result;
    }

   /**
     * Returns description of method result value
     * @return external_description
     */
    public static function delete_users_returns() {
         return  
            new external_single_structure(
                array(
                		'success'	 => new external_value(PARAM_BOOL, 'success')
   
                ));
    }    
	
    
 # --------------------------------------------------------------------------------------------------------------------------------------------   
	
	    
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function update_users_parameters() {
       
        return new external_function_parameters(
            array(
                'users' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id'    => new external_value(PARAM_NUMBER, 'ID of the user', VALUE_OPTIONAL),
                            'username'    => new external_value(PARAM_RAW, 'Username policy is defined in Moodle security config', VALUE_REQUIRED, '',NULL_NOT_ALLOWED),
                            'password'    => new external_value(PARAM_RAW, 'Plain text password consisting of any characters', VALUE_OPTIONAL, '',NULL_NOT_ALLOWED),
                            'firstname'   => new external_value(PARAM_NOTAGS, 'The first name(s) of the user', VALUE_OPTIONAL, '',NULL_NOT_ALLOWED),
                            'lastname'    => new external_value(PARAM_NOTAGS, 'The family name of the user', VALUE_OPTIONAL),
                            'email'       => new external_value(PARAM_EMAIL, 'A valid and unique email address', VALUE_OPTIONAL, '',NULL_NOT_ALLOWED),
                            'auth'        => new external_value(PARAM_PLUGIN, 'Auth plugins include manual, ldap, imap, etc', VALUE_OPTIONAL, '', NULL_NOT_ALLOWED),
                            'idnumber'    => new external_value(PARAM_RAW, 'An arbitrary ID code number perhaps from the institution', VALUE_OPTIONAL),
                            'lang'        => new external_value(PARAM_SAFEDIR, 'Language code such as "en", must exist on server', VALUE_OPTIONAL, '', NULL_NOT_ALLOWED),
                            'theme'       => new external_value(PARAM_PLUGIN, 'Theme name such as "standard", must exist on server', VALUE_OPTIONAL),
                            'timezone'    => new external_value(PARAM_TIMEZONE, 'Timezone code such as Australia/Perth, or 99 for default', VALUE_OPTIONAL),
                            'mailformat'  => new external_value(PARAM_INTEGER, 'Mail format code is 0 for plain text, 1 for HTML etc', VALUE_OPTIONAL),
                            'emailstop'		=> new external_value(PARAM_INTEGER, 'emailstop', VALUE_OPTIONAL),
                            'description' => new external_value(PARAM_TEXT, 'User profile description, no HTML', VALUE_OPTIONAL),
                            'city'        => new external_value(PARAM_NOTAGS, 'Home city of the user', VALUE_OPTIONAL),
                            'country'     => new external_value(PARAM_ALPHA, 'Home country code of the user, such as AU or CZ', VALUE_OPTIONAL),
                            'customfields' => new external_multiple_structure(
                                new external_single_structure(
                                    array(
                                        'type'  => new external_value(PARAM_ALPHANUMEXT, 'The name of the custom field'),
                                        'value' => new external_value(PARAM_RAW, 'The value of the custom field')
                                    )
                                ), 'User custom fields (also known as user profil fields)', VALUE_OPTIONAL),
                            'preferences' => new external_multiple_structure(
                                new external_single_structure(
                                    array(
                                        'type'  => new external_value(PARAM_ALPHANUMEXT, 'The name of the preference'),
                                        'value' => new external_value(PARAM_RAW, 'The value of the preference')
                                    )
                                ), 'User preferences', VALUE_OPTIONAL),
                        )
                    )
                )
            )
        );
    }

    /**
     * Update users
     * @param array $users
     * @return null
     */
    public static function update_users($users) {
    
        global $CFG, $DB;
      

        // Ensure the current user is allowed to run this function
        #$context = get_context_instance(CONTEXT_SYSTEM);
        #require_capability('moodle/user:update', $context);
        #self::validate_context($context);

        $params = self::validate_parameters(self::update_users_parameters(), array('users'=>$users));

        $transaction = $DB->start_delegated_transaction();

				$success = false;
				$errors = "no";
        foreach ($params['users'] as $user) {
        	
        	// get user id
        	$userid = $DB->get_field_sql("select id from {user} where username='".$user["username"]."' "); 
        	$user["id"] = $userid;
        	if (!is_numeric($userid)) {
        		 throw new moodle_exception('error on updating user -- "'.$user["username"].'" not found! "'.$userid.'" comes from DB', 'error');
        	}
        		syncLog("updating-user: ".$user["username"]);
        		
            oc_user_update_user($user);
             $success = true;
            //update user custom fields
            if(!empty($user['customfields'])) {

                foreach($user['customfields'] as $customfield) {
                    $user["profile_field_".$customfield['type']] = $customfield['value']; //profile_save_data() saves profile file
                                                                                            //it's expecting a user with the correct id,
                                                                                            //and custom field to be named profile_field_"shortname"
                }
                profile_save_data((object) $user);  
            }

            //preferences
            if (!empty($user['preferences'])) {
                foreach($user['preferences'] as $preference) {
                    set_user_preference($preference['type'], $preference['value'],$user['id']);
                }
            }
        }

				syncLog("udpate proceed: ".$errors);
        $transaction->allow_commit();
		
				$result = array(
						"success" => $success,
						"errors"	=> $errors
						);
			
			  
        return $result;
    }

   /**
     * Returns description of method result value
     * @return external_description
     */
    public static function update_users_returns() {
        return  
            new external_single_structure(
                array(
                		'success'	 => new external_value(PARAM_BOOL, 'success'),
                	  'errors'	 => new external_value(PARAM_TEXT, 'errors')
                   
                )
            );
    }
    
    
 # --------------------------------------------------------------------------------------------------------------------------------------------   
	
	    
	 /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function create_users_parameters() {
        global $CFG;

        return new external_function_parameters(
            array(
                'users' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'username'    => new external_value(PARAM_USERNAME, 'Username policy is defined in Moodle security config. Must be lowercase.'),
                            'password'    => new external_value(PARAM_RAW, 'Plain text password consisting of any characters'),
                            'firstname'   => new external_value(PARAM_NOTAGS, 'The first name(s) of the user'),
                            'lastname'    => new external_value(PARAM_NOTAGS, 'The family name of the user'),
                            'email'       => new external_value(PARAM_EMAIL, 'A valid and unique email address'),
                       			'city'        => new external_value(PARAM_NOTAGS, 'Home city of the user', VALUE_OPTIONAL),
                       			'emailstop'		=> new external_value(PARAM_INTEGER, 'emailstop', VALUE_OPTIONAL),
                       			'lang'        => new external_value(PARAM_TEXT, 'Language code such as "en", must exist on server', VALUE_DEFAULT, $CFG->lang, NULL_NOT_ALLOWED),
                       			'emailstop'		=> new external_value(PARAM_INTEGER, 'emailstop', VALUE_OPTIONAL)
                       			
                       			 
                         #   'emailstop'		=> new external_value(PARAM_INTEGER, 'emailstop', VALUE_OPTIONAL),
                         #   'auth'        => new external_value(PARAM_PLUGIN, 'Auth plugins include manual, ldap, imap, etc', VALUE_DEFAULT, 'manual', NULL_NOT_ALLOWED),
                         #   'idnumber'    => new external_value(PARAM_RAW, 'An arbitrary ID code number perhaps from the institution', VALUE_DEFAULT, ''),
                         #   'lang'        => new external_value(PARAM_SAFEDIR, 'Language code such as "en", must exist on server', VALUE_DEFAULT, $CFG->lang, NULL_NOT_ALLOWED),
                         #   'theme'       => new external_value(PARAM_PLUGIN, 'Theme name such as "standard", must exist on server', VALUE_OPTIONAL),
                         #   'timezone'    => new external_value(PARAM_TIMEZONE, 'Timezone code such as Australia/Perth, or 99 for default', VALUE_OPTIONAL),
                         #   'mailformat'  => new external_value(PARAM_INTEGER, 'Mail format code is 0 for plain text, 1 for HTML etc', VALUE_OPTIONAL),
                         #   'description' => new external_value(PARAM_TEXT, 'User profile description, no HTML', VALUE_OPTIONAL),
                         #   'city'        => new external_value(PARAM_NOTAGS, 'Home city of the user', VALUE_OPTIONAL),
                         #   'country'     => new external_value(PARAM_ALPHA, 'Home country code of the user, such as AU or CZ', VALUE_OPTIONAL),
                         #   'preferences' => new external_multiple_structure(
                         #       new external_single_structure(
                         #           array(
                         #               'type'  => new external_value(PARAM_ALPHANUMEXT, 'The name of the preference'),
                         #               'value' => new external_value(PARAM_RAW, 'The value of the preference')
                         #           )
                         #       ), 'User preferences', VALUE_OPTIONAL),
                         #   'customfields' => new external_multiple_structure(
                         #       new external_single_structure(
                         #           array(
                         #               'type'  => new external_value(PARAM_ALPHANUMEXT, 'The name of the custom field'),
                         #               'value' => new external_value(PARAM_RAW, 'The value of the custom field')
                         #           )
                         #       ), 'User custom fields (also known as user profil fields)', VALUE_OPTIONAL)
                        )
                    )
                )
            )
        );
    }

    /**
     * Create one or more users
     *
     * @param array $users  An array of users to create.
     * @return array An array of arrays
     */
    public static function create_users($users) {
    
    	 
        global $CFG, $DB;
      

        // Ensure the current user is allowed to run this function
        $context = get_context_instance(CONTEXT_SYSTEM);
        self::validate_context($context);
        require_capability('moodle/user:create', $context);

        // Do basic automatic PARAM checks on incoming data, using params description
        // If any problems are found then exceptions are thrown with helpful error messages
        # $params = self::validate_parameters(self::create_users_parameters(), array('users'=>$users));

				$params["users"] = $users;
			 
        $availableauths  = get_plugin_list('auth');
        unset($availableauths['mnet']);       // these would need mnethostid too
        unset($availableauths['webservice']); // we do not want new webservice users for now

        $availablethemes = get_plugin_list('theme');
        $availablelangs  = get_string_manager()->get_list_of_translations();

        $transaction = $DB->start_delegated_transaction();

        $userids = array();
      
      	$success = false;
      	$errors = " ";
        foreach ($params['users'] as $user) {
        		syncLog("try to create user : ".$user["firstname"]." ".$user["lastname"]." [".$user["username"]."] (".$user["email"].") ");
        	
            // Make sure that the username doesn't already exist
            if ($DB->record_exists('user', array('username'=>$user['username'], 'mnethostid'=>$CFG->mnet_localhost_id))) {
               #  throw new invalid_parameter_exception('Username already exists: '.$user['username']);
             $errors .= "username allready exists! ";
             syncLog("ERROR: username '".$user["username"]."' allready exists!");
            }

            // Make sure auth is valid
            #if (empty($availableauths[$user['auth']])) {
            #    # throw new invalid_parameter_exception('Invalid authentication type: '.$user['auth']);
            #}
 

            // Make sure lang is valid
            #if (!empty($user['theme']) && empty($availablethemes[$user['theme']])) { //theme is VALUE_OPTIONAL,
            #                                                                         // so no default value.
            #                                                                         // We need to test if the client sent it
            #                                                                         // => !empty($user['theme'])
            #    throw new invalid_parameter_exception('Invalid theme: '.$user['theme']);
            #}

            $user['confirmed'] = true;
            $user['mnethostid'] = $CFG->mnet_localhost_id;

            // Start of user info validation.
            // Lets make sure we validate current user info as handled by current GUI. see user/editadvanced_form.php function validation()
            if (!validate_email($user['email'])) {
               # throw new invalid_parameter_exception('Email address is invalid: '.$user['email']);
            } else if ($DB->record_exists('user', array('email'=>$user['email'], 'mnethostid'=>$user['mnethostid']))) {
                // todo: Fehler bei bereits vorhandenen EMailadressen entschärfen
                #  throw new invalid_parameter_exception('Email address already exists: '.$user['email']);
                  syncLog("WARNING: emailadress allready in use (".$user["username"]." : ".$user["email"].") ");
            }
            // End of user info validation.

            // create the user data now!
            $user['id'] = oc_user_create_user($user);
					  syncLog("SUCCESS user '".$user["username"]."' created, id=".$user["id"]);
            // custom fields
            
            if(!empty($user['customfields'])) {
                foreach($user['customfields'] as $customfield) {
                    $user["profile_field_".$customfield['type']] = $customfield['value']; //profile_save_data() saves profile file
                                                                                            //it's expecting a user with the correct id,
                                                                                            //and custom field to be named profile_field_"shortname"
                }
                profile_save_data((object) $user);
            }
						
						
            //preferences
            if (!empty($user['preferences'])) {
                foreach($user['preferences'] as $preference) {
                    set_user_preference($preference['type'], $preference['value'],$user['id']);
                }
            }

            #$userids[] = array('id'=>$user['id'], 'username'=>$user['username']);
            $successId = $user["id"];
            $success = true;
            
        }

        $transaction->allow_commit();

				$ret = array(
						"success"		=> $success,
						"errors"		=> $errors,
						"id"				=> $successId,
						"username"	=> $user["username"]."-".rand(10,99)
					);
						
        return $ret;
    }

   /**
     * Returns description of method result value
     * @return external_description
     */
    public static function create_users_returns() {
        return  
            new external_single_structure(
                array(
                		'success'	 => new external_value(PARAM_BOOL, 'success'),
                	  'errors'	 => new external_value(PARAM_TEXT, 'errors'),
                    'id'       => new external_value(PARAM_INT,  'user id'),
                    'username' => new external_value(PARAM_TEXT, 'user name')
                )
            );
    } 
    
    
    
 # --------------------------------------------------------------------------------------------------------------------------------------------   
	    
    
    public static function get_moodleuserid_parameters() {
        global $CFG;

        return new external_function_parameters(
            array(
                'users' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'username'    => new external_value(PARAM_TEXT, 'Username policy is defined in Moodle security config. Must be lowercase.')
                           
                        )
                    )
                )
            )
        );
    }

    /**
     * Create one or more users
     *
     * @param array $users  An array of users to create.
     * @return array An array of arrays
     */
    public static function get_moodleuserid($users) {
    	
        global $CFG, $DB;
       

        // Ensure the current user is allowed to run this function
        #$context = get_context_instance(CONTEXT_SYSTEM);
       # require_capability('moodle/user:update', $context);
        #self::validate_context($context);

     #   $users = self::validate_parameters(self::get_moodleuserid(), array('users'=>$users));

        #$transaction = $DB->start_delegated_transaction();
			 
        
        	$checkUsername = $users["username"];
        	syncLog("checking moodleuserid for ".$checkUsername);
        	// get user id
        	#$userid = self::inner_getMoodleuserid_by_username($user["username"]);
        	$userid = $DB->get_field_sql("select id from {user} where username='".$checkUsername."' "); 
         
        	if (!is_numeric($userid)) {
        		 throw new moodle_exception('User is unknown in moodle!', 'error');
        		 $userid = 0;
        	} else {
        		
        	}
            
     
				$result = array("id" => $userid );
				
        return $result;
    }

		public static function  inner_getMoodleuserid_by_username($username) {
				  global $CFG, $DB;
       	  require_once($CFG->dirroot."/user/lib.php");
        	require_once($CFG->dirroot."/user/profile/lib.php"); //required for customfields related function
                                                             //TODO: move the functions somewhere else as
                                                             //they are "user" related
					$userid = $DB->get_field_sql("select id from {user} where username='$username' "); 
					return $userid;
					
		}
   /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_moodleuserid_returns() {
        return  new external_single_structure(
                array(
                    'id'       => new external_value(PARAM_INT, 'user id')
                )
        );
    }
    
 # --------------------------------------------------------------------------------------------------------------------------------------------   
	
	
	
public static function courseuser_erbsensuppe() {}
public static function courseuser_erbsensuppe_parameters() {

 return new external_function_parameters(
                array(
                    'enrolment' => new external_multiple_structure(
                            new external_single_structure(
                                    array(
                                        'roleid' => new external_value(PARAM_INT, 'Role to assign to the user'),
                                        'username' => new external_value(PARAM_TEXT, 'The user that is going to be enrolled'),
                                        'coursename' => new external_value(PARAM_TEXT, 'The course to enrol the user role in'),
                                        'courseFullname' => new external_value(PARAM_TEXT, 'The course to enrol the user role in')
                                        
                                       # 'timestart' => new external_value(PARAM_INT, 'Timestamp when the enrolment start', VALUE_OPTIONAL),
                                       # 'timeend' => new external_value(PARAM_INT, 'Timestamp when the enrolment end', VALUE_OPTIONAL),
                                       # 'suspend' => new external_value(PARAM_INT, 'set to 1 to suspend the enrolment', VALUE_OPTIONAL)
                                    )
                            )
                    )
                )
        );
       
      } 
public static function courseuser_erbsensuppe_returns() {}

	


  
 
}


 
 // umgebogene Funktionen aus dem Moodle2-Core:
 function oc_user_create_user($user) {
    global $DB;

    // set the timecreate field to the current time
    if (!is_object($user)) {
            $user = (object)$user;
    }
    
    # 	$SyncUser->password
    

    //check username
    if ($user->username !== textlib::strtolower($user->username)) {
        throw new moodle_exception('usernamelowercase');
    } else {
        if ($user->username !== clean_param($user->username, PARAM_USERNAME)) {
            throw new moodle_exception('invalidusername');
        }
    }

    // save the password in a temp value for later
    

    $user->timecreated = time();
    $user->timemodified = $user->timecreated;

    // insert the user into the database
    $newuserid = $DB->insert_record('user', $user);

		$u = $DB->set_field('user', 'password' , $user->password, array('id' => $newuserid));
		
    // trigger user_created event on the full database user row
    $newuser = $DB->get_record('user', array('id' => $newuserid));

    // create USER context for this user
    get_context_instance(CONTEXT_USER, $newuserid);

    // update user password if necessary
    if (isset($userpassword)) {
        $authplugin = get_auth_plugin($newuser->auth);
        $authplugin->user_update_password($newuser, $userpassword);
    }

    events_trigger('user_created', $newuser);

    add_to_log(SITEID, 'user', get_string('create'), '/view.php?id='.$newuser->id,
        fullname($newuser));

    return $newuserid;

}

  
  
  
  function oc_user_update_user($user) {
    global $DB;

    // set the timecreate field to the current time
    if (!is_object($user)) {
            $user = (object)$user;
    }

    //check username
    if (isset($user->username)) {
        if ($user->username !== textlib::strtolower($user->username)) {
            throw new moodle_exception('usernamelowercase');
        } else {
            if ($user->username !== clean_param($user->username, PARAM_USERNAME)) {
                throw new moodle_exception('invalidusername');
            }
        }
    }

    // unset password here, for updating later
    if (isset($user->password)) {

        $u = $DB->set_field('user', 'password' , $user->password, array('id' => $user->id));
    }

    $user->timemodified = time();
    $DB->update_record('user', $user);

    // trigger user_updated event on the full database user row
    $updateduser = $DB->get_record('user', array('id' => $user->id));

    // if password was set, then update its hash
    if (isset($passwd)) {
        $authplugin = get_auth_plugin($updateduser->auth);
        if ($authplugin->can_change_password()) {
            $authplugin->user_update_password($updateduser, $passwd);
        }
    }

    events_trigger('user_updated', $updateduser);

    add_to_log(SITEID, 'user', get_string('update'), '/view.php?id='.$updateduser->id,
        fullname($updateduser));

}

 
 
  function syncLog($mess) {
 	$mess = utf8_decode($mess);
 		$handle = fopen(SYNCLOGFILE,"a+");
 		$prefix = date("H:i:s",date("U"))." ";
 		fwrite ($handle, $prefix.$mess."\r\n");
 		fclose($handle);
 }
 
 
   
 
function rrmdir($dir) {
	if (is_dir($dir)) {
		$objects = scandir($dir);
		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
			}
		}
		reset($objects);
		rmdir($dir);
	}
}	