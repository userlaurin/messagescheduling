<?php

namespace mod_messagescheduling\task;
/**
 * Schedule the messages
 */
class send_messages extends \core\task\scheduled_task {
 
    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('send_messages', 'mod_messagescheduling');
    }
    /**
     * Execute the task.
     */
    function send_message($id, $subject, $msg, $courseid, $where) {
        global $DB, $USER;
        mtrace("Message sending started for: ".$id);
        $userto = $DB->get_record('user', array('id'=>$id));
        mtrace('<br>message to id: '.$id.' on course: '.$courseid.' from '.$USER->id.' with the subject: '.$subject.' and the content: '.$msg);
        $message = new \core\message\message();
        $message->component = 'moodle';
        $message->name = 'instantmessage';
        $message->userfrom = $USER;
        $message->userto = $userto;
        $message->subject = $subject;
        $message->fullmessage = $msg;
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->fullmessagehtml = $msg;
        $message->smallmessage = $msg;
        $message->notification = '1';
        $message->replyto = "xucation-techsupport@be-xu.com";
        $message->courseid = $courseid;
        if($messageid = message_send($message)) {
            $DB->set_field('messagescheduling', 'executed', 1, array('id'=>$where));
        }
        else {
            mtrace("<h2>Error!</h2>");
        }
        
    }
    public function execute() {
        mtrace("Send messages started<br>");
        global $DB;
        $all_messages = $DB->get_records('messagescheduling', array('executed'=>0));
        foreach($all_messages as $messages) {
            if($messages->time != 0 && $messages->time < time()) {
                $id = intval($messages->id);
                $to = $messages->to;
                $subject = $messages->subject;
                $msg = $messages->message;
                $courseid = $messages->courseid;
                $this->send_message($to, $subject, $msg, $courseid, $id);
            }
        }
        mtrace("Send messages finished");
        

    }
}

?>