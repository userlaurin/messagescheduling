<?php
require_once('../../config.php');
$PAGE->set_url('/mod/messagescheduling/send_message.php');
$PAGE->set_title('Send Message');
$PAGE->set_heading('Send Message');
require_login();

global $USER, $DB;
$user_object = $DB->get_record('user', array('id'=>$USER->id));
function pre_r($array) {
    echo'<pre>';
    print_r($array);
    echo"</pre>";
  }
echo$OUTPUT->header();
?>
<style>
fieldset {
    margin: auto;
    text-align: center;
}
input[type=text]{
    padding: 5px 10px;
    min-width: 400px;
}
#scheduledmessages>table {
    margin: auto;
}
#scheduledmessages tr:nth-of-type(1) {
    border: 3px solid black;
}
#scheduledmessages tr:not(:nth-of-type(1)) td {
    border: 1px solid black;
}
#scheduledmessages td, #scheduledmessages th {
    padding: 10px;
}
</style>
<?php
switch($_GET['form']) {
    case 1:
        echo"<script>
        window.onload = function() {
            document.getElementById('courseidselection').checked = true;
            document.getElementById('cohortidselection').checked = false;
            document.getElementById('useridselection').checked = false;}
        </script>";
        break;
    case 2:
        echo"<script>
        window.onload = function() {
            document.getElementById('courseidselection').checked = false;
            document.getElementById('cohortidselection').checked = true;
            document.getElementById('useridselection').checked = false;}
        </script>";
        break;
    case 3:
        echo"<script>
        window.onload = function() {
            document.getElementById('courseidselection').checked = false;
            document.getElementById('cohortidselection').checked = false;
            document.getElementById('useridselection').checked = true;}
        </script>";
        break;
}
$allscheduledmessages = $DB->get_records('messagescheduling', array('executed'=>0));
if(count($allscheduledmessages)>0) {
    echo"<div id='scheduledmessages'><h2 style='text-align: center;'>All scheduled messages</h2><table><tr><th>ID</th><th>User ID</th><th>Message</th><th>Subject</th><th>Course ID</th><th>Time</th></tr>";
    foreach($allscheduledmessages as $message){
        echo"<tr><td>".$message->id."</td><td>".$message->to."</td><td>".$message->subject."</td><td>".$message->message."</td><td>".$message->courseid."</td><td>".gmdate("Y-m-d\TH:i:s\Z", $message->time)."</td></tr>";
    } 
    echo"</table></div>";
}
echo"<div>
    <form id='general_selection'>
        <fieldset>
            <h2>Schedule message to: </h2>
            <input type='radio' name='filter_by' id='courseidselection' value='courseid' onclick='if(this.checked){ window.location.href=`/mod/messagescheduling/send_message.php?form=1`}' style='margin: 5px;'>Course<br>
            <input type='radio' name='filter_by' id='cohortidselection' value='user_cohort_id' onclick='if(this.checked){window.location.href = `/mod/messagescheduling/send_message.php?form=2`}' style='margin: 5px;'>Cohort<br>
            <input type='radio' name='filter_by' id='useridselection' value='username' onclick='if(this.checked){window.location.href = `/mod/messagescheduling/send_message.php?form=3`}' style='margin: 5px;'>User<br>
        </fieldset>
    </form>
</div>";
require_once("$CFG->libdir/formslib.php");
if($_GET['form'] == 1) {
    class courseidselection extends moodleform {
        function definition() {
            global $CFG, $DB;
            $mform = $this->_form; // Don't forget the underscore! 
            $allcourses = $DB->get_records('course');
            $courses=array(); 
            foreach ($allcourses as $course => $val) {
                if($val->id != '') {
                    $courses[] =  $mform->createElement('advcheckbox', $val->id,'', $val->fullname, array('group' => 1, 'id'=>'courseid_'.$val->id), array(0,1));
                }
            }
            $mform->addGroup($courses, 'courses', 'Select Course', array('<br>'), false);
            $this->add_checkbox_controller(1);
            $mform->addElement('editor', 'message_course', 'Write Message');
            $mform->addElement('text', 'message_subject', 'Add subject');
            $mform->addElement('date_time_selector', 'schedule_course', 'Select Time');
            $this->add_action_buttons(false, 'Schedule Email to Course/s', array('id'=>'course_submit'));
        }     
    }
    $courseidselection = new courseidselection('/mod/messagescheduling/send_message.php?form=1');
    if ($result = $courseidselection->get_data()) {
        //In this case you process validated data. $mform->get_data() returns data posted in form.
        $allcourses = $DB->get_records('course');
        $neededstudentsids = array();
        foreach ($allcourses as $course => $value) {
            $id = $value->id;
            if($result->$id == 1) {
                $context = get_context_instance(CONTEXT_COURSE, $id); 
                $students = get_role_users(5 , $context);
                foreach ($students as $student => $val) {
                    if(in_array_r($val->id, $neededstudentsids) == false) {
                        $push = array($val->id, $value->id);
                        //pre_r($push);
                        $neededstudentsids[] = $push;
                    }
                }
            }
        }
        for($i =0; $i<count($neededstudentsids); $i++) {
            create_message($neededstudentsids[$i][0], $result->message_subject, $result->message_course['text'], $neededstudentsids[$i][1], $result->schedule_course);
        }
        echo"<h2 style='text-align: center'>Successfully scheduled messages</h2>";
    } else {
        echo"<div id='courseidselection'>";
        $courseidselection->display();
    }
}
echo"</div>";
class cohortidselection extends moodleform {
    function definition() {
        global $CFG, $DB;
    
        $mform = $this->_form; // Don't forget the underscore! 
    
        
        $allcohorts = $DB->get_records('cohort');
        $cohorts=array(); 
        foreach ($allcohorts as $cohort => $val1) {
            if($val1->id != '') {
                $cohorts[] =  $mform->createElement('advcheckbox', $val1->id,'', $val1->name, array('group' => 2, 'id'=>'cohortid_'.$val1->id), array(0,1));
            }
        }
        $mform->addGroup($cohorts, 'cohorts', 'Select Cohort', array('<br>'), false);
        $this->add_checkbox_controller(2);
        $mform->addElement('editor', 'message_cohort', 'Write Message');
        $mform->addElement('text', 'message_subject_cohort', 'Add subject');
        $mform->addElement('date_time_selector', 'schedule_cohort', 'Select Time');
        $this->add_action_buttons(false, 'Schedule Email to Cohort/s', array('id'=>'cohort_submit'));
    }     
}
if($_GET['form'] == 2) {
    $cohortidselection = new cohortidselection('/mod/messagescheduling/send_message.php?form=2');
    if ($result2 = $cohortidselection->get_data()) {
        //In this case you process validated data. $mform->get_data() returns data posted in form.
        $allcohorts = $DB->get_records('cohort');
        $neededstudentsids1 = array();
        foreach ($allcohorts as $cohort => $value) {
            $id = $value->id;
            if($result2->$id == 1) {
                $students = $DB->get_records('cohort_members', array('cohortid'=>$id));
                foreach ($students as $student => $val) {
                    if(in_array_r($val->userid, $neededstudentsids) == false) {
                        $neededstudentsids[] = $val->userid;
                    }
                }
            }
        }
        for($i =0; $i<count($neededstudentsids); $i++) {
            create_message($neededstudentsids[$i], $result2->message_subject_cohort, $result2->message_cohort['text'], 1, $result2->schedule_cohort);
        }
        echo"<h2 style='text-align: center'>Successfully scheduled messages</h2>";
    } else {
        echo"<div id='cohortidselection'>";
        $cohortidselection->display();
    }
}
echo"</div>";
class useridselection extends moodleform {
    function definition() {
        global $CFG, $DB;
    
        $mform = $this->_form; // Don't forget the underscore! 
    
        
        $allusers = $DB->get_records('user');
        $users=array(); 
        foreach ($allusers as $user) {
            if($user->id != '') {
                $users[] =  $mform->createElement('advcheckbox', $user->id,'', $user->firstname.' '.$user->lastname, array('group' => 3, 'id'=>'userid_'.$user->id), array(0,1));
            }
        }
        $mform->addGroup($users, 'users', 'Select Users', array('<br>'), false);
        $this->add_checkbox_controller(3);
        $mform->addElement('editor', 'message_users', 'Write Message');
        $mform->addElement('text', 'message_subject_user', 'Add subject');
        $mform->addElement('date_time_selector', 'schedule_user', 'Select Time');
        $this->add_action_buttons(false, 'Schedule Email to User/s', array('id'=>'user_submit'));
    }     
}
if($_GET['form'] == 3) {
$useridselection = new useridselection('/mod/messagescheduling/send_message.php?form=3');
    if ($result1 = $useridselection->get_data()) {
        //In this case you process validated data. $mform->get_data() returns data posted in form.
        $userstomessage = array();
        foreach($result1 as $key=>$value) {
            if($value == 1) {
                create_message($key, $result1->message_subject_user, $result1->message_users['text'], 1, $result1->schedule_user);
            }
        }
        echo"<h2 style='text-align: center'>Successfully scheduled messages</h2>";
    } else {
        echo"<div id='useridselection'>";
        $useridselection->display();
    }
}
echo"</div>";


echo$OUTPUT->footer();

function create_message($to, $subject, $message, $courseid, $time) {
    global $DB;
    $max = $DB->get_field_sql("SELECT max(`id`) FROM `mdl_messagescheduling`");
    $max = intval($max)+1;
    $sql = "INSERT INTO `mdl_messagescheduling`(`id`, `to`, `message`, `subject`, `courseid`, `time`, `executed`) VALUES ('".$max."', '".$to."', '".$message."', '".$subject."', '".$courseid."', '".$time."', '0')";
    $DB->execute($sql);
}

function in_array_r($needle, $haystack) {
    foreach ($haystack as $key => $subArr) {
        if ($needle != $subArr[0]) {
            return $key;
        }
    }
    return false;
}
?>