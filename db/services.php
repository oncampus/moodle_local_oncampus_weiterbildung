<?php

// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Web service local plugin template external functions and service definitions.
 *
 * @package    localwstemplate
 * @copyright  2011 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
  
// We defined the web service functions to install.
$functions = array(


						'local_oncampus_isenroled' => array(
		                'classname'   => 'local_oncampus_external',
		                'methodname'  => 'isenroled',
		                'classpath'   => 'local/oncampus/externallib.php',
		                'description' => 'delivers status about enrolment of a user in a course',
		                'type'        => 'write' 
       	  ),  
       	  
       	  
								'local_oncampus_courseuser_update' => array(
		                'classname'   => 'local_oncampus_external',
		                'methodname'  => 'courseuser_update',
		                'classpath'   => 'local/oncampus/externallib.php',
		                'description' => 'updates a courseuser',
		                'type'        => 'write' 
       	  ),  
       	  
								'local_oncampus_courseuser_delete' => array(
		                'classname'   => 'local_oncampus_external',
		                'methodname'  => 'courseuser_delete',
		                'classpath'   => 'local/oncampus/externallib.php',
		                'description' => 'deletes a courseuser',
		                'type'        => 'write' 
       	  ),  
								'local_oncampus_courseuser_insert' => array(
		                'classname'   => 'local_oncampus_external',
		                'methodname'  => 'courseuser_insert',
		                'classpath'   => 'local/oncampus/externallib.php',
		                'description' => 'inserts a courseuser',
		                'type'        => 'write'
       	  ),  
         	'local_oncampus_courseuser_erbsensuppe' => array(
                'classname'   => 'local_oncampus_external',
                'methodname'  => 'courseuser_erbsensuppe',
                'classpath'   => 'local/oncampus/externallib.php',
                'description' => 'inserts a courseuser',
                'type'        => 'write'
         ), 
				  'local_oncampus_updatecourse' => array(
                'classname'   => 'local_oncampus_external',
                'methodname'  => 'updatecourse',
                'classpath'   => 'local/oncampus/externallib.php',
                'description' => 'Updating Course',
                'type'        => 'write'
         ), 
        'local_oncampus_createcourse' => array(
                'classname'   => 'local_oncampus_external',
                'methodname'  => 'createcourse',
                'classpath'   => 'local/oncampus/externallib.php',
                'description' => 'Creates new course from backupfile.',
                'type'        => 'write'
         ), 
        'local_oncampus_deletecourse' => array(
                'classname'   => 'local_oncampus_external',
                'methodname'  => 'deletecourse',
                'classpath'   => 'local/oncampus/externallib.php',
                'description' => 'Deletes a Course',
                'type'        => 'write'
         ),
        'local_oncampus_delete_users' => array(
                'classname'   => 'local_oncampus_external',
                'methodname'  => 'delete_users',
                'classpath'   => 'local/oncampus/externallib.php',
                'description' => 'Deletes users by username.',
                'type'        => 'write'
        ),
        'local_oncampus_update_users' => array(
                'classname'   => 'local_oncampus_external',
                'methodname'  => 'update_users',
                'classpath'   => 'local/oncampus/externallib.php',
                'description' => 'Updates users by username.',
                'type'        => 'write'
        ),
         'local_oncampus_create_users' => array(
                'classname'   => 'local_oncampus_external',
                'methodname'  => 'create_users',
                'classpath'   => 'local/oncampus/externallib.php',
                'description' => 'Create users by username.',
                'type'        => 'write'
        ),
        'local_oncampus_get_moodleuserid' => array(
                'classname'   => 'local_oncampus_external',
                'methodname'  => 'get_moodleuserid',
                'classpath'   => 'local/oncampus/externallib.php',
                'description' => 'delivers moodle-userid by given username',
                'type'        => 'write'
        )
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
        'oncampus' => array(
                'functions' => array (
                'local_oncampus_courseuser_update',
                'local_oncampus_courseuser_insert',
                'local_oncampus_courseuser_delete',
                'local_oncampus_updatecourse',
                'local_oncampus_createcourse',
                'local_oncampus_deletecourse',
                'local_oncampus_delete_users',
                'local_oncampus_update_users',
                'local_oncampus_create_users', 
                'local_oncampus_get_moodleuserid'
                ),
                'restrictedusers' => 0,
                'enabled'=>1,
        )
);