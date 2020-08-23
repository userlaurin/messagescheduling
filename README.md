# messagescheduling
Moodle Message scheduling plugin based on PHP
This is a Moodle Messaging Plugin inspired by the user bulk management. It adds a page under /mod/message_scheduling/send_messages.php which lets you schedule messages to users, cohorts or courses. If a user is enrolled in multiple courses/cohorts he will only get one message. The plugin writes the messages into the database and extends a task class that sends the plugins out at the given time. The Moodle 'instantmessage' messaging provider is being used which means that messaging has to be activated in settings.
