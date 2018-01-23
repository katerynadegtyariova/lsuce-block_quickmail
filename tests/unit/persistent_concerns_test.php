<?php
 
require_once(dirname(__FILE__) . '/unit_testcase_traits.php');

use block_quickmail\persistents\message;
use block_quickmail\persistents\message_recipient;

class block_quickmail_persistent_concerns_testcase extends advanced_testcase {
    
    use unit_testcase_has_general_helpers,
        unit_testcase_sets_up_courses;

    private function create_message()
    {
        return message::create_new([
            'course_id' => 1,
            'user_id' => 1,
            'output_channel' => 'email',
        ]);
    }

    private function create_message_and_recipient()
    {
        $message = $this->create_message();

        $recipient = message_recipient::create_new([
            'message_id' => $message->get('id'),
            'user_id' => 1,
        ]);

        return [$message, $recipient];
    }

    /////////////////////////////////////////////////////////////////////////////////

    public function test_create_new()
    {
        $this->resetAfterTest(true);
 
        $message = $this->create_message();

        $this->assertInstanceOf(message::class, $message);
        $this->assertEquals(1, $message->get('course_id'));
        $this->assertEquals(1, $message->get('user_id'));
        $this->assertEquals('email', $message->get('output_channel'));
    }

    public function test_find_or_null()
    {
        $this->resetAfterTest(true);
 
        $fetched = message::find_or_null(1);

        $this->assertNull($fetched);

        $message = $this->create_message();

        $fetched = message::find_or_null($message->get('id'));

        $this->assertNotNull($fetched);
        $this->assertInstanceOf(message::class, $fetched);
    }

    public function test_get_readable_date()
    {
        $this->resetAfterTest(true);

        $message = $this->create_message();

        $timestamp = $message->get('timecreated');

        $this->assertEquals(date('Y-m-d H:i:s', $timestamp), $message->get_readable_date('timecreated'));
    }

    public function test_supports_soft_deletes()
    {
        $this->resetAfterTest(true);

        list($message, $recipient) = $this->create_message_and_recipient();

        $this->assertTrue($message::supports_soft_deletes());
        $this->assertFalse($recipient::supports_soft_deletes());
    }

    public function test_hard_and_soft_delete()
    {
        $this->resetAfterTest(true);

        $message1 = $this->create_message();
        $message2 = $this->create_message();

        $message1_id = $message1->get('id');
        $message2_id = $message2->get('id');

        $message1->hard_delete();
        $message2->soft_delete();

        global $DB;

        $deleted_message1 = $DB->get_record('block_quickmail_messages', ['id' => $message1_id]);
        $deleted_message2 = $DB->get_record('block_quickmail_messages', ['id' => $message2_id]);

        $this->assertFalse($deleted_message1);
        $this->assertInternalType('object', $deleted_message2);
        $this->assertGreaterThan(0, $message2->get('timedeleted'));
        $this->assertTrue($message2->is_soft_deleted());
    }

    public function test_belongs_to_a_course()
    {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();

        $message = message::create_new([
            'course_id' => $course->id,
            'user_id' => 1,
            'output_channel' => 'email',
        ]);

        $message_course = $message->get_course();

        $this->assertInternalType('object', $message_course);
        $this->assertEquals($course->id, $message_course->id);
        $this->assertTrue($message->is_owned_by_course($course));
        $this->assertTrue($message->is_owned_by_course($course->id));
    }

    public function test_belongs_to_a_message()
    {
        $this->resetAfterTest(true);

        list($message, $recipient) = $this->create_message_and_recipient();

        $recipient_message = $recipient->get_message();

        $this->assertInstanceOf(message::class, $recipient_message);
        $this->assertEquals($message->get('id'), $recipient_message->get('id'));

        $second_recipient = message_recipient::create_for_message($message, [
            'user_id' => 1
        ]);

        $this->assertInstanceOf(message_recipient::class, $second_recipient);
        $this->assertEquals(1, $second_recipient->get('user_id'));
    }

    public function test_belongs_to_a_user()
    {
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();

        $message = message::create_new([
            'course_id' => 1,
            'user_id' => $user->id,
            'output_channel' => 'email',
        ]);

        $message_user = $message->get_user();

        $this->assertInternalType('object', $message_user);
        $this->assertEquals($user->id, $message_user->id);
        $this->assertTrue($message->is_owned_by_user($user));
        $this->assertTrue($message->is_owned_by_user($user->id));
    }

}